<?php

namespace App\Services\Report;

use App\Models\Report\ExpenseRecap;
use App\Models\Report\TransactionCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk business logic Laporan Pengeluaran (Expense Report).
 *
 * Menangani seluruh logika bisnis dashboard laporan pengeluaran,
 * termasuk: query building, statistik ringkasan, trend bulanan,
 * distribusi kategori, cash flow, dan kategori transaksi.
 *
 * Mengikuti pattern Single Source of Truth untuk filter query
 * agar logic filter hanya ada di satu tempat.
 */
class ExpenseReportService
{
    /**
     * Kolom yang diizinkan untuk sorting pada laporan pengeluaran.
     *
     * Mencegah SQL injection dengan memastikan hanya kolom
     * yang valid yang dapat digunakan untuk sorting.
     */
    private const ALLOWED_SORT_COLUMNS = [
        'id',
        'transaction_date',
        'income_amount',
        'expense_amount',
        'invoice_number',
        'created_at',
    ];

    /**
     * Kolom default untuk sorting.
     */
    private const DEFAULT_SORT_COLUMN = 'transaction_date';

    /**
     * Arah sorting default.
     */
    private const DEFAULT_SORT_ORDER = 'desc';

    /**
     * Membangun query dasar dengan filter dari request.
     *
     * Filter yang didukung:
     * - month: Filter berdasarkan bulan transaction_date
     * - year: Filter berdasarkan tahun transaction_date (default: tahun saat ini)
     * - category: Filter berdasarkan transaction_category_id
     * - type: Filter berdasarkan tipe transaksi (income/expense)
     * - search: Pencarian berdasarkan id, invoice_number, atau description
     *
     * Logika filter visibilitas (clause pertama):
     * - Record milik user saat ini (created_by = auth()->id()).
     * - ATAU record yang berasal dari sales recap (sales_recap_id IS NOT NULL).
     *   Record sales recap bersifat global sehingga tetap tampil walau dibuat
     *   user lain — ini dasar laporan keuangan gabungan.
     * - type income/expense diimplementasikan dengan where(income_amount>0) /
     *   where(expense_amount>0) karena satu record hanya mengisi salah satu kolom.
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildFilteredQuery(Request $request): Builder
    {
        $query = ExpenseRecap::query()
            ->where(function ($q) {
                $q->where('created_by', auth()->id())
                    ->orWhereNotNull('sales_recap_id');
            });

        if ($request->filled('month')) {
            $query->whereMonth('transaction_date', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('transaction_date', $request->year);
        } else {
            $query->whereYear('transaction_date', date('Y'));
        }

        if ($request->filled('category')) {
            $query->where('transaction_category_id', $request->category);
        }

        if ($request->filled('type')) {
            $query->when($request->type === 'income', function ($q) {
                $q->where('income_amount', '>', 0);
            }, function ($q) use ($request) {
                $q->when($request->type === 'expense', function ($q2) {
                    $q2->where('expense_amount', '>', 0);
                });
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Membangun query untuk data expense recap dengan eager loading.
     *
     * Digunakan untuk menampilkan data di tabel dengan pagination.
     * Sorting menggunakan kolom yang di-whitelist untuk keamanan.
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter dan sorting
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildIndexQuery(Request $request): Builder
    {
        $sortBy = $this->getAllowedSortColumn($request->get('sort_by', self::DEFAULT_SORT_COLUMN));
        $sortOrder = $this->getAllowedSortOrder($request->get('sort_order', self::DEFAULT_SORT_ORDER));

        return $this->buildFilteredQuery($request)
            ->with(['category'])
            ->orderBy($sortBy, $sortOrder);
    }

    /**
     * Menghitung ringkasan statistik (summary cards).
     *
     * Mengembalikan: total transaksi, total pemasukan, total pengeluaran,
     * saldo, jumlah transaksi pemasukan/pengeluaran, dan rata-rata.
     *
     * Logika:
     * - Satu SELECT agregat (single-row) memakai selectRaw; COALESCE memastikan
     *   kolom NULL dihitung 0 agar tidak merusak total.
     * - balance = total_income - total_expense (saldo periode).
     * - income_count/expense_count dihitung via CASE WHEN amount > 0.
     * - avg_income = total_income / income_count (bukan per semua transaksi),
     *   0 jika tidak ada transaksi pemasukan — menghindari division by zero.
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     * @return array<string, mixed>
     */
    public function calculateSummary(Request $request): array
    {
        $summary = $this->buildFilteredQuery($request)
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(COALESCE(income_amount, 0)) as total_income,
                SUM(COALESCE(expense_amount, 0)) as total_expense,
                SUM(CASE WHEN income_amount > 0 THEN 1 ELSE 0 END) as income_count,
                SUM(CASE WHEN expense_amount > 0 THEN 1 ELSE 0 END) as expense_count
            ')
            ->first();

        $totalIncome = $summary->total_income ?? 0;
        $totalExpense = $summary->total_expense ?? 0;
        $incomeCount = $summary->income_count ?? 0;
        $expenseCount = $summary->expense_count ?? 0;

        return [
            'total_transactions' => $summary->total_transactions ?? 0,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
            'income_count' => $incomeCount,
            'expense_count' => $expenseCount,
            'avg_income' => $incomeCount > 0 ? $totalIncome / $incomeCount : 0,
            'avg_expense' => $expenseCount > 0 ? $totalExpense / $expenseCount : 0,
        ];
    }

    /**
     * Mendapatkan data trend bulanan untuk chart.
     *
     * Mengembalikan data 12 bulan (Jan-Des) dengan jumlah transaksi,
     * total pemasukan, total pengeluaran, dan saldo per bulan.
     * Bulan yang tidak ada data akan diisi dengan nilai 0.
     *
     * Logika:
     * - Trend memakai rentang year dari request (default tahun berjalan) dan
     *   MENERAPKAN ulang filter visibilitas + category + type agar konsisten
     *   dengan data tabel — tidak memakai buildFilteredQuery karena di sini
     *   selalu grouped by bulan dan basis tahunnya eksplisit.
     * - Loop 1..12 + keyBy('month') mengisi bulan kosong dengan 0.
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     * @return array<int, array{month: int, month_name: string, count: int, income: int, expense: int, balance: int}>
     */
    public function getMonthlyTrend(Request $request): array
    {
        $year = $request->get('year', date('Y'));

        $trend = ExpenseRecap::whereYear('transaction_date', $year)
            ->where(function ($q) {
                $q->where('created_by', auth()->id())
                    ->orWhereNotNull('sales_recap_id');
            })
            ->when($request->filled('category'), function ($q) use ($request) {
                $q->where('transaction_category_id', $request->category);
            })
            ->when($request->filled('type'), function ($q) use ($request) {
                if ($request->type === 'income') {
                    $q->where('income_amount', '>', 0);
                } elseif ($request->type === 'expense') {
                    $q->where('expense_amount', '>', 0);
                }
            })
            ->selectRaw('
                MONTH(transaction_date) as month,
                COUNT(*) as count,
                SUM(COALESCE(income_amount, 0)) as income,
                SUM(COALESCE(expense_amount, 0)) as expense
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $result = [];
        for ($i = 1; $i <= 12; $i++) {
            $income = $trend->has($i) ? (int) $trend[$i]->income : 0;
            $expense = $trend->has($i) ? (int) $trend[$i]->expense : 0;

            $result[] = [
                'month' => $i,
                'month_name' => date('M', mktime(0, 0, 0, $i, 1)),
                'count' => $trend->has($i) ? (int) $trend[$i]->count : 0,
                'income' => $income,
                'expense' => $expense,
                'balance' => $income - $expense,
            ];
        }

        return $result;
    }

    /**
     * Mendapatkan distribusi pengeluaran per kategori.
     *
     * Mengembalikan data kategori dengan jumlah transaksi,
     * total pemasukan, total pengeluaran, dan total.
     * Digunakan untuk chart bar dan tabel ringkasan kategori.
     *
     * Logika:
     * - Agregasi GROUP BY transaction_category_id di-sort menurun berdasarkan
     *   total expense (kategori terbesar tampil pertama).
     * - Nama kategori diambil dari cache 'report:expense-categories' (seluruh
     *   kategori, di-keyBy id) lalu difilter sesuai categoryIds hasil query —
     *   menghindari query per kategori (N+1).
     * - Jika cache gagal dibaca, fallback query whereIn; kategori yang sudah
     *   dihapus (id null/tidak ketemu) diberi nama "Tidak ada kategori".
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     * @return \Illuminate\Support\Collection
     */
    public function getCategoryDistribution(Request $request)
    {
        $results = $this->buildFilteredQuery($request)
            ->selectRaw('
                transaction_category_id,
                COUNT(*) as count,
                SUM(COALESCE(income_amount, 0)) as income,
                SUM(COALESCE(expense_amount, 0)) as expense
            ')
            ->groupBy('transaction_category_id')
            ->orderByRaw('SUM(COALESCE(expense_amount, 0)) DESC')
            ->get();

        $categoryIds = $results->pluck('transaction_category_id')->unique()->filter();

        $userId = auth()->id();
        $cacheKey = 'report:category-lookup:' . $userId;

        try {
            $allCategories = Cache::remember(
                $cacheKey,
                now()->addDay(),
                fn () => TransactionCategory::where('created_by', $userId)->get()->keyBy('id')
            );
            $categories = $allCategories->filter(fn ($cat) => $categoryIds->contains($cat->id));
        } catch (\Exception $e) {
            Log::warning('Cache READ error [' . $cacheKey . ']: ' . $e->getMessage());
            $categories = TransactionCategory::where('created_by', $userId)->whereIn('id', $categoryIds)->get()->keyBy('id');
        }

        return $results->map(function ($item) use ($categories) {
            $category = $categories->get($item->transaction_category_id);

            return [
                'category_id' => $item->transaction_category_id,
                'category_name' => $category?->name ?? 'Tidak ada kategori',
                'category_type' => $category?->type ?? null,
                'count' => (int) $item->count,
                'income' => (int) $item->income,
                'expense' => (int) $item->expense,
                'total' => (int) $item->income + (int) $item->expense,
            ];
        });
    }

    /**
     * Mendapatkan data cash flow untuk periode tertentu.
     *
     * Mengembalikan: saldo awal, total pemasukan, total pengeluaran,
     * net cash flow, dan saldo akhir.
     *
     * Logika:
     * - Hanya menjumlahkan income dan expense dalam periode (tahun dari request,
     *   default tahun berjalan) dengan filter visibilitas yang sama.
     * - opening_balance sengaja 0: perhitungan saldo awal antar periode belum
     *   didukung, sehingga closing = 0 + income - expense.
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     * @return array{opening_balance: int, total_income: int, total_expense: int, net_cash_flow: int, closing_balance: int}
     */
    public function getCashFlow(Request $request): array
    {
        $periodData = ExpenseRecap::query()
            ->where(function ($q) {
                $q->where('created_by', auth()->id())
                    ->orWhereNotNull('sales_recap_id');
            })
            ->when($request->filled('year'), function ($q) use ($request) {
                $q->whereYear('transaction_date', $request->year);
            }, function ($q) {
                $q->whereYear('transaction_date', date('Y'));
            })
            ->selectRaw('
                SUM(COALESCE(income_amount, 0)) as total_income,
                SUM(COALESCE(expense_amount, 0)) as total_expense
            ')
            ->first();

        $totalIncome = $periodData->total_income ?? 0;
        $totalExpense = $periodData->total_expense ?? 0;

        return [
            'opening_balance' => 0,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_cash_flow' => $totalIncome - $totalExpense,
            'closing_balance' => 0 + $totalIncome - $totalExpense,
        ];
    }

    /**
     * Ambil semua kategori transaksi yang aktif untuk filter dropdown.
     *
     * Logika:
     * - Hasil di-cache 1 hari dengan key 'report:expense-categories' — key yang
     *   SAMA dengan getCategoryDistribution(), jadi keduanya saling berbagi cache.
     *   Cache di-flush saat CRUD kategori (lihat TransactionCategoryService).
     * - Hanya scope active() yang diambil, diurutkan sort_order untuk dropdown.
     * - Jika cache error, fallback query langsung agar halaman tetap berfungsi.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveCategories()
    {
        $userId = auth()->id();
        $cacheKey = 'report:expense-categories:' . $userId;

        try {
            return Cache::remember(
                $cacheKey,
                now()->addDay(),
                fn () => TransactionCategory::where('created_by', $userId)->active()->orderBy('sort_order')->get()
            );
        } catch (\Exception $e) {
            Log::warning('Cache READ error [' . $cacheKey . ']: ' . $e->getMessage());
            return TransactionCategory::where('created_by', $userId)->active()->orderBy('sort_order')->get();
        }
    }

    /**
     * Mendapatkan kolom sorting yang diizinkan.
     *
     * Mencegah SQL injection dengan memastikan kolom sorting
     * hanya berasal dari daftar yang diizinkan.
     *
     * @param  string  $sortBy  Kolom yang diminta untuk sorting
     * @return string Kolom yang valid atau default
     */
    private function getAllowedSortColumn(string $sortBy): string
    {
        return in_array($sortBy, self::ALLOWED_SORT_COLUMNS, true) ? $sortBy : self::DEFAULT_SORT_COLUMN;
    }

    /**
     * Mendapatkan arah sorting yang diizinkan.
     *
     * Mencegah SQL injection dengan memastikan hanya 'asc' atau 'desc'.
     *
     * @param  string  $sortOrder  Arah sorting yang diminta
     * @return string Arah yang valid atau default
     */
    private function getAllowedSortOrder(string $sortOrder): string
    {
        return in_array(strtolower($sortOrder), ['asc', 'desc'], true) ? strtolower($sortOrder) : self::DEFAULT_SORT_ORDER;
    }

    // ============================================================
    // EXPORT METHODS
    // ============================================================

    /**
     * Membangun data untuk export PDF/Excel.
     *
     * Mengembalikan array dengan struktur:
     * - expenseRecaps: Collection data rekap pengeluaran dengan eager loading category
     * - periodTitle: Label periode untuk header
     * - totals: Object berisi total_income, total_expense, balance
     *
     * Logika:
     * - Data diambil memakai buildFilteredQuery + eager load category dan
     *   diurutkan transaction_date menaik (kronologis untuk laporan).
     * - Totals dihitung dari Collection::sum() di sisi PHP (bukan SQL) karena
     *   sudah terlanjur dimuat semua record untuk export.
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     * @return array{expenseRecaps: \Illuminate\Support\Collection, periodTitle: string, totals: object}
     */
    public function buildExportData(Request $request): array
    {
        $expenseRecaps = $this->buildFilteredQuery($request)
            ->with(['category'])
            ->orderBy('transaction_date', 'asc')
            ->get();

        $totals = (object) [
            'total_income' => (int) $expenseRecaps->sum('income_amount'),
            'total_expense' => (int) $expenseRecaps->sum('expense_amount'),
            'balance' => (int) $expenseRecaps->sum('income_amount') - (int) $expenseRecaps->sum('expense_amount'),
        ];

        $periodTitle = $this->buildPeriodTitle($request);

        return [
            'expenseRecaps' => $expenseRecaps,
            'periodTitle' => $periodTitle,
            'totals' => $totals,
        ];
    }

    /**
     * Membangun label periode untuk header PDF/Excel.
     *
     * Logika:
     * - Prioritas label: (month+year) → (month saja, year fallback ke request
     *   atau tahun berjalan) → (year saja) → "SEMUA PERIODE".
     * - Berbeda dari SalesReportService: tidak perlu data untuk menurunkan
     *   tahun karena ekspor pengeluaran selalu punya parameter filter.
     * - Nama bulan memakai locale id via Carbon::translatedFormat('F').
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     * @return string Label periode (contoh: "BULAN FEBRUARI 2026")
     */
    public function buildPeriodTitle(Request $request): string
    {
        $month = $request->get('month');
        $year = $request->get('year');

        if (!empty($month) && !empty($year)) {
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');
            return 'BULAN ' . strtoupper($monthName) . ' ' . $year;
        }

        if (!empty($month)) {
            $year = $request->get('year', date('Y'));
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');
            return 'BULAN ' . strtoupper($monthName) . ' ' . $year;
        }

        if (!empty($year)) {
            return 'TAHUN ' . $year;
        }

        return 'SEMUA PERIODE';
    }
}
