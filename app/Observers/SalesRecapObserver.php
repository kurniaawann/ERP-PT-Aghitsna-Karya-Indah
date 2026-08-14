<?php

namespace App\Observers;

use App\Models\Inventory\ItemStockOut;
use App\Models\Report\ExpenseRecap;
use App\Models\Report\SalesRecap;
use App\Models\Report\TransactionCategory;
use App\Models\User;
use App\Services\Finance\PaymentProofService;
use App\Services\Finance\RecapExpenseService;
use App\Services\Report\TransactionCategoryService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Observer untuk model SalesRecap.
 *
 * Menangani auto-management data terkait saat event SalesRecap fire:
 * - created: Buat ItemStockOut records
 * - updated: Recreate stock outs + auto-create ExpenseRecap saat status Lunas
 * - deleted: Hapus stock out records + hapus file payment proof
 */
class SalesRecapObserver
{
    /**
     * Handle the SalesRecap "created" event.
     *
     * Membuat stock out records untuk setiap item yang dari stock.
     */
    public function created(SalesRecap $salesRecap): void
    {
        $this->createStockOuts($salesRecap);
        $this->flushInventoryCache();
        $this->flushSalesRecapOptionsCache();
    }

    /**
     * Handle the SalesRecap "updated" event.
     *
     * Recreate stock outs untuk menjaga konsistensi, dan auto-create
     * ExpenseRecap saat status berubah menjadi Lunas.
     */
    public function updated(SalesRecap $salesRecap): void
    {
        // Selalu recreate stock outs untuk menjaga konsistensi
        $this->createStockOuts($salesRecap);
        $this->flushInventoryCache();
        $this->flushSalesRecapOptionsCache();

        // Auto-create expense recap saat status berubah ke Lunas
        if ($salesRecap->wasChanged('status') && $salesRecap->status === 'Lunas') {
            $this->createExpenseRecap($salesRecap);
        }
    }

    /**
     * Handle the SalesRecap "deleting" event.
     *
     * Mem-flush cache kategori milik user yang ExpenseRecap-nya akan ikut
     * terhapus (cascade) SEBELUM record dihapus. Dilakukan di sini (bukan di
     * "deleted") karena observer di-resolve ulang per event, sehingga data
     * antar-hook tidak bisa dibawa lewat property instance. Sales recap tidak
     * punya created_by sehingga bisa dihapus oleh user lain.
     */
    public function deleting(SalesRecap $salesRecap): void
    {
        $ownerIds = ExpenseRecap::where('sales_recap_id', $salesRecap->getKey())
            ->whereNotNull('created_by')
            ->pluck('created_by')
            ->unique()
            ->values()
            ->all();

        foreach ($ownerIds as $ownerId) {
            app(TransactionCategoryService::class)->flushCache($ownerId);
        }
    }

    /**
     * Handle the SalesRecap "deleted" event.
     *
     * Menghapus stock out records dan file payment proof terkait.
     */
    public function deleted(SalesRecap $salesRecap): void
    {
        ItemStockOut::where('id_sales_recap', $salesRecap->getKey())->delete();
        $this->flushInventoryCache();
        $this->flushSalesRecapOptionsCache();

        foreach ($salesRecap->paymentProofs as $proof) {
            app(PaymentProofService::class)->delete($proof->file_path);
            $proof->delete();
        }

        // Cache kategori milik user yang menginisiasi delete juga di-invalidate.
        app(TransactionCategoryService::class)->flushCache();
    }

    /**
     * Membuat stock out records dari items rekap penjualan.
     */
    private function createStockOuts(SalesRecap $salesRecap): void
    {
        $salesRecapId = $salesRecap->getKey();
        $items = is_string($salesRecap->items) ? json_decode($salesRecap->items, true) : $salesRecap->items;

        ItemStockOut::where('id_sales_recap', $salesRecapId)->delete();

        if ($items && is_array($items)) {
            foreach ($items as $item) {
                if ($this->isFromStock($item['from_stock'] ?? null) && ! empty($item['id_item'])) {
                    ItemStockOut::create([
                        'id_stock_out' => $this->generateStockOutId(),
                        'id_item' => $item['id_item'],
                        'quantity' => $item['quantity'] ?? 0,
                        'id_sales_recap' => $salesRecapId,
                        'date' => $salesRecap->date ?? now(),
                        'project_name' => $salesRecap->name_proyek,
                    ]);
                }
            }
        }
    }

    /**
     * Auto-create ExpenseRecap saat status Lunas.
     */
    public function createExpenseRecap(SalesRecap $salesRecap): void
    {
        $salesRecapId = $salesRecap->getKey();

        $exists = ExpenseRecap::where('sales_recap_id', $salesRecapId)->exists();

        if ($exists) {
            return;
        }

        $incomeCategory = $this->resolveIncomeCategory();

        if (! $incomeCategory) {
            Log::warning('Auto expense recap skipped: tidak ada kategori INCOME aktif', [
                'sales_recap_id' => $salesRecapId,
            ]);

            return;
        }

        $invoiceNumber = app(RecapExpenseService::class)->generateIncomeInvoiceNumber();

        ExpenseRecap::create([
            'transaction_category_id' => $incomeCategory->id,
            'invoice_number' => $invoiceNumber,
            'transaction_date' => $salesRecap->date ?? now(),
            'description' => $salesRecap->name_proyek ?? 'Penjualan - '.$salesRecapId,
            'income_amount' => $salesRecap->total_selling,
            'expense_amount' => null,
            'money_source' => null,
            'sales_recap_id' => $salesRecapId,
            'created_by' => $incomeCategory->created_by,
            'notes' => 'Auto-generated from sales recap',
        ]);

        app(TransactionCategoryService::class)->flushCache($incomeCategory->created_by);
    }

    /**
     * Pilih kategori INCOME untuk uang masuk otomatis.
     *
     * Kategori dicari berdasarkan user yang memicu (created_by), bukan global,
     * karena daftar kategori transaksi ditampilkan per-user. Jika user belum
     * punya kategori UANG_MASUK, dibuat otomatis satu kali saja (bukan setiap
     * rekap penjualan lunas) dengan created_by user yang memicu. Karena kolom
     * code bersifat unique global, sebelum create dicek dulu apakah kode
     * tersedia; jika kode UANG_MASUK sudah terpakai (oleh user lain), kode
     * di-increment menjadi UANG_MASUK1, UANG_MASUK2, dst. sampai ketemu yang
     * kosong (atau saat create gagal karena kode bentrok).
     */
    private function resolveIncomeCategory(): ?TransactionCategory
    {
        $userId = auth()->id() ?? User::orderBy('created_at')->value('id');

        $incomeCategory = TransactionCategory::where('created_by', $userId)
            ->module(TransactionCategory::MODULE_EXPENSE_RECAP)
            ->where('type', TransactionCategory::TYPE_INCOME)
            ->whereRaw("code REGEXP '^UANG_MASUK[0-9]*$'")
            ->orderBy('id')
            ->first();

        if ($incomeCategory) {
            return $incomeCategory;
        }

        $baseCode = 'UANG_MASUK';
        $suffix = 1;

        while (true) {
            $code = $suffix === 1 ? $baseCode : $baseCode.$suffix;
            $suffix++;

            if (TransactionCategory::where('code', $code)->exists()) {
                continue;
            }

            try {
                $incomeCategory = TransactionCategory::create([
                    'name' => 'Uang Masuk',
                    'code' => $code,
                    'type' => TransactionCategory::TYPE_INCOME,
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_by' => $userId,
                ]);
                break;
            } catch (QueryException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }

        app(TransactionCategoryService::class)->flushCache();

        return $incomeCategory;
    }

    /**
     * Generate unique stock out ID (format: SOUT-YYYYMMDD-XXXX).
     */
    private function generateStockOutId(): string
    {
        $date = date('Ymd');
        $count = ItemStockOut::whereDate('created_at', date('Y-m-d'))->count() + 1;
        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);

        do {
            $id = 'SOUT-'.$date.'-'.$sequence;
            $sequence = str_pad((int) $sequence + 1, 4, '0', STR_PAD_LEFT);
        } while (ItemStockOut::where('id_stock_out', $id)->exists());

        return $id;
    }

    /**
     * Cek apakah item berasal dari stock.
     *
     * @param  mixed  $value
     */
    private function isFromStock($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Invalidate semua cache inventory terkait stock-out dan stock report.
     */
    private function flushInventoryCache(): void
    {
        try {
            Cache::forget('inventory:stock-outs:all');
        } catch (\Exception $e) {
            Log::warning('Cache DELETE error [inventory:stock-outs:all]: '.$e->getMessage());
        }
    }

    /**
     * Invalidate cache opsi SalesRecap di halaman Bukti Pembayaran.
     */
    private function flushSalesRecapOptionsCache(): void
    {
        try {
            Cache::forget('finance:sales-recap-options');
        } catch (\Exception $e) {
            Log::warning('Cache DELETE error [finance:sales-recap-options]: '.$e->getMessage());
        }
    }
}
