<?php

namespace App\Services\Finance;

use App\Models\Report\ExpenseRecap;
use App\Models\Report\TransactionCategory;
use App\Services\InputNormalizer;
use App\Services\Report\TransactionCategoryService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service layer untuk operasi bisnis Rekap Pengeluaran.
 *
 * Menangani semua logika bisnis terkait Expense Recap termasuk:
 * - Pencarian dan filter
 * - Pembuatan ID unik (race-condition safe)
 * - Perhitungan total income, expense, dan balance
 * - CRUD operations
 * - Bulk delete
 */
class RecapExpenseService
{
    /**
     * Angka awal untuk format invoice number uang masuk.
     * Format: {INCOME_INVOICE_START_A + n}/{INCOME_INVOICE_START_B + n}/div.produksi/{INCOME_INVOICE_START_C + n}
     */
    private const INCOME_INVOICE_START_A = 333;
    private const INCOME_INVOICE_START_B = 590;
    private const INCOME_INVOICE_START_C = 146;
    /**
     * Membangun query dasar untuk listing rekap pengeluaran.
     *
     * Logika filter penting:
     * - Data yang terlihat = milik user login (created_by = user). Ini berlaku
     *   untuk input manual maupun rekap otomatis dari sales recap, karena
     *   auto-generated kini mencatat created_by = user yang memicu status Lunas
     *   (diisi SalesRecapObserver). Dengan begitu data rekap pengeluaran
     *   konsisten: setiap user hanya melihat datanya sendiri.
     * - Filter month, year, category, search opsional.
     *
     * @param  \Illuminate\Http\Request $request  Request yang berisi parameter filter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildFilteredQuery(Request $request): Builder
    {
        $month = $request->get('month');
        $year = $request->get('year');
        $category = $request->get('category');
        $search = $request->get('search');

        return ExpenseRecap::query()
            ->where('created_by', auth()->id())
            ->when($month, function ($query, $month) {
                $query->whereMonth('transaction_date', $month);
            })
            ->when($year, function ($query, $year) {
                $query->whereYear('transaction_date', $year);
            })
            ->when($category, function ($query, $category) {
                $query->where('transaction_category_id', $category);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('money_source', 'like', "%{$search}%");
                });
            });
    }

    /**
     * Membangun query untuk grand totals (ringkasan income, expense, balance).
     *
     * @param  \Illuminate\Http\Request $request  Request yang berisi parameter filter
     * @return object  Object berisi total_income, total_expense, balance
     */
    public function getGrandTotals(Request $request): object
    {
        $totals = $this->buildFilteredQuery($request)
            ->select(
                DB::raw('SUM(income_amount) as total_income'),
                DB::raw('SUM(expense_amount) as total_expense')
            )
            ->first();

        $totals->balance = ($totals->total_income ?? 0) - ($totals->total_expense ?? 0);

        return $totals;
    }

    /**
     * Membangun query untuk data expense recap dengan eager loading (web/index).
     *
     * Mengurutkan data terbaru di atas (descending).
     *
     * @param  \Illuminate\Http\Request $request  Request yang berisi parameter filter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildIndexQuery(Request $request): Builder
    {
        return $this->buildFilteredQuery($request)
            ->with(['category', 'salesRecap', 'creator'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Membangun query untuk export (Excel/PDF) dengan eager loading.
     *
     * @param  \Illuminate\Http\Request $request  Request yang berisi parameter filter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildExportQuery(Request $request): Builder
    {
        return $this->buildFilteredQuery($request)
            ->with(['category', 'salesRecap', 'creator'])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Label modul sesuai role yang login (revisi klien).
     *
     * Admin menyebut modul ini "Kas Kantor"; super admin (dan role lain)
     * tetap "Rekap Pengeluaran". Dipakai untuk judul halaman, tombol, modal,
     * pesan flash, dan nama file export.
     *
     * @return string
     */
    public function getRecapLabel(): string
    {
        return auth()->user()?->isAdmin() ? 'Kas Kantor' : 'Rekap Pengeluaran';
    }

    /**
     * Apakah role yang login memakai nomor faktur.
     *
     * Admin (Kas Kantor) tidak memakai kolom FAKTUR sama sekali (layar maupun
     * export), sehingga nomor faktur tidak di-generate untuk datanya.
     *
     * @return bool
     */
    public function usesInvoiceNumber(): bool
    {
        return !(auth()->user()?->isAdmin() ?? false);
    }

    /**
     * Membuat rekap pengeluaran baru dari input manual user.
     *
     * Logika INCOME vs EXPENSE (ditentukan dari tipe kategori transaksi):
     * - Kategori INCOME (uang masuk): expense_amount dipindah ke income_amount,
     *   dan invoice_number digenerate (format {333+n}/{590+n}/div.produksi/{146+n}).
     * - Kategori selain INCOME (uang keluar): income_amount null, expense_amount
     *   dipakai, dan TIDAK punya nomor faktur (invoice_number null).
     * - Role admin (Kas Kantor) tidak memakai nomor faktur sama sekali.
     *
     * @param  array<string, mixed> $data  Data yang sudah validasi dari FormRequest
     * @return \App\Models\Report\ExpenseRecap
     */
    public function createRecap(array $data): ExpenseRecap
    {
        $category = TransactionCategory::find($data['transaction_category_id']);

        if ($category && $category->type === 'INCOME') {
            $data['income_amount'] = InputNormalizer::normalizeCurrency($data['expense_amount'] ?? null);
            $data['expense_amount'] = null;
            $data['invoice_number'] = $this->usesInvoiceNumber() ? $this->generateIncomeInvoiceNumber() : null;
        } else {
            $data['income_amount'] = null;
            $data['expense_amount'] = InputNormalizer::normalizeCurrency($data['expense_amount'] ?? null);
            $data['invoice_number'] = null;
        }

        $data['created_by'] = auth()->id();

        $recap = ExpenseRecap::create($data);

        app(TransactionCategoryService::class)->flushCache();

        return $recap;
    }

    /**
     * Mengupdate rekap pengeluaran yang sudah ada.
     *
     * Logika INCOME vs EXPENSE (ditentukan dari tipe kategori transaksi):
     * - Kategori INCOME (uang masuk): expense_amount dipindah ke income_amount.
     *   Nomor faktur uang masuk yang sudah ada dipertahankan (tidak bisa diubah
     *   dari form); bila record sebelumnya uang keluar atau belum punya nomor
     *   faktur, invoice_number digenerate (format {333+n}/{590+n}/div.produksi/{146+n}).
     * - Kategori selain INCOME (uang keluar): income_amount null, expense_amount
     *   dipakai, dan nomor faktur dikosongkan.
     * - Role admin (Kas Kantor) tidak memakai nomor faktur: nilai lama dibiarkan.
     *
     * @param  \App\Models\Report\ExpenseRecap $expenseRecap  Model yang akan diupdate
     * @param  array<string, mixed>            $data          Data yang sudah validasi dari FormRequest
     * @return bool
     *
     * @throws \RuntimeException  Jika data adalah auto-generated
     */
    public function updateRecap(ExpenseRecap $expenseRecap, array $data): bool
    {
        if ($expenseRecap->isAutoGenerated()) {
            throw new \RuntimeException('Data yang auto-generated dari sales report tidak dapat diubah!');
        }

        $category = TransactionCategory::find($data['transaction_category_id']);
        $amount = InputNormalizer::normalizeCurrency($data['expense_amount'] ?? null);

        // Nomor faktur tidak pernah diambil dari input form (read-only / disembunyikan)
        unset($data['invoice_number']);

        if ($category && $category->type === 'INCOME') {
            $data['income_amount'] = $amount;
            $data['expense_amount'] = null;

            // Generate bila sebelumnya bukan uang masuk atau belum punya nomor faktur
            $wasIncome = (int) $expenseRecap->income_amount > 0;
            if ($this->usesInvoiceNumber() && (!$wasIncome || blank($expenseRecap->invoice_number))) {
                $data['invoice_number'] = $this->generateIncomeInvoiceNumber();
            }
        } else {
            $data['income_amount'] = null;
            $data['expense_amount'] = $amount;

            if ($this->usesInvoiceNumber()) {
                $data['invoice_number'] = null;
            }
        }

        $updated = $expenseRecap->update($data);

        app(TransactionCategoryService::class)->flushCache();

        return $updated;
    }

    /**
     * Hapus beberapa rekap pengeluaran sekaligus (bulk delete).
     *
     * Logika: data auto-generated dari sales report TIDAK dihapus (isAutoGenerated()).
     * Memakai each() + $expense->delete() per record (bukan mass delete) agar
     * event/observer model tetap berjalan dan cache ikut ter-bersihkan.
     *
     * @param  array<int, string> $ids  Daftar ID rekap pengeluaran
     * @return int  Jumlah rekap yang dihapus
     */
    public function bulkDelete(array $ids): int
    {
        $deletedCount = 0;

        ExpenseRecap::whereIn('id', $ids)->each(function ($expense) use (&$deletedCount) {
            if (!$expense->isAutoGenerated()) {
                $expense->delete();
                $deletedCount++;
            }
        });

        app(TransactionCategoryService::class)->flushCache();

        return $deletedCount;
    }

    /**
     * Generate unique expense recap ID (format: RE-00001).
     *
     * Prefix: RE (Recap Expense)
     * Sequential number: 5 digit zero-padded
     * Contoh: RE-00001, RE-00002, dst.
     *
     * Menggunakan database lock untuk mencegah race condition.
     *
     * @return string
     */
    public function generateId(): string
    {
        $lastRecap = ExpenseRecap::lockForUpdate()
            ->where('id', 'like', 'RE-%')
            ->orderByDesc('id')
            ->first();

        if ($lastRecap && preg_match('/^RE-(\d+)$/', $lastRecap->id, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        $newId = 'RE-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        while (ExpenseRecap::where('id', $newId)->exists()) {
            $nextNumber++;
            $newId = 'RE-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        }

        return $newId;
    }

    /**
     * Generate invoice number untuk uang masuk dengan format increment.
     *
     * Format: {333+n}/{590+n}/div.produksi/{146+n} — tiga angka berjalan sekaligus.
     * n = urutan record uang masuk yang sudah ada. Mencari record income terakhir
     * yang punya nomor faktur berformat tersebut (record tanpa faktur, mis. milik
     * admin/Kas Kantor, dilewati agar urutan tidak kembali ke awal), lalu parse
     * ketiga angkanya via regex dan increment semuanya.
     *
     * Contoh:
     * - Data ke-1: 333/590/div.produksi/146
     * - Data ke-2: 334/591/div.produksi/147
     * - Data ke-3: 335/592/div.produksi/148
     *
     * @return string
     */
    public function generateIncomeInvoiceNumber(): string
    {
        $lastIncome = ExpenseRecap::whereNotNull('income_amount')
            ->where('income_amount', '>', 0)
            ->where('invoice_number', 'like', '%/div.produksi/%')
            ->orderByDesc('created_at')
            ->first();

        if ($lastIncome && preg_match('/^(\d+)\/(\d+)\/div\.produksi\/(\d+)$/', $lastIncome->invoice_number, $matches)) {
            $nextA = (int) $matches[1] + 1;
            $nextB = (int) $matches[2] + 1;
            $nextC = (int) $matches[3] + 1;
        } else {
            $nextA = self::INCOME_INVOICE_START_A;
            $nextB = self::INCOME_INVOICE_START_B;
            $nextC = self::INCOME_INVOICE_START_C;
        }

        return "{$nextA}/{$nextB}/div.produksi/{$nextC}";
    }

    /**
     * Ambil semua kategori transaksi yang aktif milik modul Rekap Pengeluaran.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpenseCategories()
    {
        $userId = auth()->id();
        $cacheKey = 'finance:expense-categories:' . $userId;

        $query = fn () => TransactionCategory::where('created_by', $userId)
            ->module(TransactionCategory::MODULE_EXPENSE_RECAP)
            ->active()
            ->orderBy('sort_order')
            ->get();

        try {
            return Cache::remember($cacheKey, now()->addDay(), $query);
        } catch (\Exception $e) {
            Log::warning('Cache READ error [' . $cacheKey . ']: ' . $e->getMessage());
            return $query();
        }
    }

    /**
     * Build judul periode untuk PDF/Excel header.
     *
     * @param  \Illuminate\Http\Request $request  Request yang berisi parameter filter
     * @return string
     */
    public function buildPeriodTitle(Request $request): string
    {
        $month = $request->get('month');
        $year = $request->get('year');

        $periodParts = [];

        if ($month && $year) {
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');
            $periodParts[] = $monthName . ' ' . $year;
        } elseif ($month) {
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');
            $periodParts[] = 'Bulan ' . $monthName;
        } elseif ($year) {
            $periodParts[] = 'Tahun ' . $year;
        }

        return !empty($periodParts) ? implode(' - ', $periodParts) : 'Semua Periode';
    }
}
