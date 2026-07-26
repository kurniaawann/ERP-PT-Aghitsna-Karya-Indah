<?php

namespace App\Observers;

use App\Models\Report\SalesRecap;
use App\Models\Report\ExpenseRecap;
use App\Models\Report\TransactionCategory;
use App\Models\Inventory\ItemStockOut;
use App\Services\Finance\PaymentProofService;
use Illuminate\Support\Facades\Cache;

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
     *
     * @param  \App\Models\Report\SalesRecap $salesRecap
     * @return void
     */
    public function created(SalesRecap $salesRecap): void
    {
        $this->createStockOuts($salesRecap);
        $this->flushInventoryCache();
    }

    /**
     * Handle the SalesRecap "updated" event.
     *
     * Recreate stock outs untuk menjaga konsistensi, dan auto-create
     * ExpenseRecap saat status berubah menjadi Lunas.
     *
     * @param  \App\Models\Report\SalesRecap $salesRecap
     * @return void
     */
    public function updated(SalesRecap $salesRecap): void
    {
        // Selalu recreate stock outs untuk menjaga konsistensi
        $this->createStockOuts($salesRecap);
        $this->flushInventoryCache();

        // Auto-create expense recap saat status berubah ke Lunas
        if ($salesRecap->wasChanged('status') && $salesRecap->status === 'Lunas') {
            $this->createExpenseRecap($salesRecap);
        }
    }

    /**
     * Handle the SalesRecap "deleted" event.
     *
     * Menghapus stock out records dan file payment proof terkait.
     *
     * @param  \App\Models\Report\SalesRecap $salesRecap
     * @return void
     */
    public function deleted(SalesRecap $salesRecap): void
    {
        ItemStockOut::where('id_sales_recap', $salesRecap->getKey())->delete();
        $this->flushInventoryCache();

        foreach ($salesRecap->paymentProofs as $proof) {
            app(PaymentProofService::class)->delete($proof->file_path);
            $proof->delete();
        }
    }

    /**
     * Membuat stock out records dari items rekap penjualan.
     *
     * @param  \App\Models\Report\SalesRecap $salesRecap
     * @return void
     */
    private function createStockOuts(SalesRecap $salesRecap): void
    {
        $salesRecapId = $salesRecap->getKey();
        $items = is_string($salesRecap->items) ? json_decode($salesRecap->items, true) : $salesRecap->items;

        ItemStockOut::where('id_sales_recap', $salesRecapId)->delete();

        if ($items && is_array($items)) {
            foreach ($items as $item) {
                if ($this->isFromStock($item['from_stock'] ?? null) && !empty($item['id_item'])) {
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
     *
     * @param  \App\Models\Report\SalesRecap $salesRecap
     * @return void
     */
    private function createExpenseRecap(SalesRecap $salesRecap): void
    {
        $salesRecapId = $salesRecap->getKey();

        $exists = ExpenseRecap::where('sales_recap_id', $salesRecapId)->exists();

        if (!$exists) {
            $incomeCategory = TransactionCategory::where('code', 'UANG_MASUK')->first();

            if ($incomeCategory) {
                ExpenseRecap::create([
                    'transaction_category_id' => $incomeCategory->id,
                    'invoice_number' => $salesRecapId,
                    'transaction_date' => $salesRecap->date ?? now(),
                    'description' => $salesRecap->name_proyek ?? 'Penjualan - ' . $salesRecapId,
                    'income_amount' => $salesRecap->total_selling,
                    'expense_amount' => null,
                    'money_source' => null,
                    'sales_recap_id' => $salesRecapId,
                    'notes' => 'Auto-generated from sales recap',
                ]);
            }
        }
    }

    /**
     * Generate unique stock out ID (format: SOUT-YYYYMMDD-XXXX).
     *
     * @return string
     */
    private function generateStockOutId(): string
    {
        $date = date('Ymd');
        $count = ItemStockOut::whereDate('created_at', date('Y-m-d'))->count() + 1;
        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);

        do {
            $id = 'SOUT-' . $date . '-' . $sequence;
            $sequence = str_pad((int) $sequence + 1, 4, '0', STR_PAD_LEFT);
        } while (ItemStockOut::where('id_stock_out', $id)->exists());

        return $id;
    }

    /**
     * Cek apakah item berasal dari stock.
     *
     * @param  mixed $value
     * @return bool
     */
    private function isFromStock($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Invalidate semua cache inventory terkait stock-out dan stock report.
     *
     * @return void
     */
    private function flushInventoryCache(): void
    {
        Cache::forget('inventory:stock-outs:all');
    }
}
