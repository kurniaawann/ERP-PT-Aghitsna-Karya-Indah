<?php

namespace App\Services\Finance;

use App\Models\Report\ExpenseRecap;
use App\Models\Report\TransactionCategory;
use App\Services\InputNormalizer;
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
     * Membangun query dasar untuk listing rekap pengeluaran.
     *
     * Method ini digunakan untuk listing, totals, dan export
     * sehingga logic filter hanya ada di satu tempat (Single Source of Truth).
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
            ->where(function ($query) {
                $query->where('created_by', auth()->id())
                    ->orWhereNotNull('sales_recap_id');
            })
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
     * Membuat rekap pengeluaran baru dari input manual user.
     *
     * @param  array<string, mixed> $data  Data yang sudah validasi dari FormRequest
     * @return \App\Models\Report\ExpenseRecap
     */
    public function createRecap(array $data): ExpenseRecap
    {
        $data['income_amount'] = null;
        $data['expense_amount'] = InputNormalizer::normalizeCurrency($data['expense_amount'] ?? null);
        $data['created_by'] = auth()->id();

        return ExpenseRecap::create($data);
    }

    /**
     * Mengupdate rekap pengeluaran yang sudah ada.
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

        $data['expense_amount'] = InputNormalizer::normalizeCurrency($data['expense_amount'] ?? null);

        return $expenseRecap->update($data);
    }

    /**
     * Hapus beberapa rekap pengeluaran sekaligus (bulk delete).
     *
     * Hanya menghapus data manual (bukan auto-generated).
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
     * Ambil semua kategori transaksi yang aktif dan berjenis EXPENSE.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpenseCategories()
    {
        try {
            return Cache::remember(
                'finance:expense-categories',
                now()->addDay(),
                fn () => TransactionCategory::active()->orderBy('sort_order')->get()
            );
        } catch (\Exception $e) {
            Log::warning('Cache READ error [finance:expense-categories]: ' . $e->getMessage());
            return TransactionCategory::active()->orderBy('sort_order')->get();
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
