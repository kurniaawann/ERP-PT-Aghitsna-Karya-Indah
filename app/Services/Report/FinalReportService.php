<?php

namespace App\Services\Report;

use App\Models\Finance\ProjectRecap;
use App\Models\Inventory\Items;
use App\Services\Inventory\StockReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Service orchestrator untuk modul Laporan Akhir.
 *
 * Laporan Akhir menggabungkan enam jenis laporan dalam satu halaman:
 * - Laporan Stok (Stock Report)           → StockReportService
 * - Laporan Penjualan (Sales Report)      → SalesReportService
 * - Laporan Pengeluaran (Expense Report)  → ExpenseReportService
 * - Laporan Semen (Cement Report)         → CementReportService
 * - Rekap Proyek (Project Recap)          → ProjectRecap (query langsung)
 * - Laporan Keuangan Proyek (Financial)   → ProjectRecap + financialReport
 *
 * Service ini TIDAK menduplikasi business logic domain. Ia hanya
 * mengorkestrasi pemanggilan ke service di atas sesuai tab yang aktif.
 * Filter tiap laporan tetap memakai parameter masing-masing (lihat partial view).
 *
 * Akses per tab meniru aturan di sidebar (resources/views/layouts/sidebar.blade.php):
 * - stock    : superadmin + user (bukan admin & bukan general_manager)
 * - sales    : superadmin + general_manager
 * - expense  : superadmin + admin + general_manager
 * - cement   : superadmin + user (bukan admin & bukan general_manager)
 * - recap    : superadmin + admin (bukan general_manager)
 * - financial: semua role (menu Report di sidebar tampil untuk semua role)
 */
class FinalReportService
{
    public function __construct(
        private StockReportService $stockReportService,
        private SalesReportService $salesReportService,
        private ExpenseReportService $expenseReportService,
        private CementReportService $cementReportService,
    ) {}

    /**
     * Daftar tab yang boleh diakses user (meniru aturan sidebar).
     *
     * @param  \App\Models\User|null  $user
     * @return array<int, string>
     */
    public function getAllowedTabs(?object $user): array
    {
        if (!$user) {
            return [];
        }

        $tabs = [];

        if (!$user->isAdmin() && !$user->isGeneralManager()) {
            $tabs[] = 'stock';
            $tabs[] = 'cement';
        }

        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            $tabs[] = 'sales';
        }

        if ($user->isSuperAdmin() || $user->isAdmin() || $user->isGeneralManager()) {
            $tabs[] = 'expense';
        }

        if (!$user->isGeneralManager() && ($user->isSuperAdmin() || $user->isAdmin())) {
            $tabs[] = 'recap';
        }

        $tabs[] = 'financial';

        return $tabs;
    }

    /**
     * Membangun seluruh data yang dibutuhkan view untuk tab aktif.
     *
     * @param  string              $tab      Tab aktif (stock|sales|expense|cement)
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function build(string $tab, Request $request): array
    {
        return match ($tab) {
            'stock' => $this->buildStockData($request),
            'expense' => $this->buildExpenseData($request),
            'cement' => $this->buildCementData($request),
            'recap' => $this->buildRecapData($request),
            'financial' => $this->buildFinancialData($request),
            default => $this->buildSalesData($request),
        };
    }

    /**
     * Data Laporan Stok (replikasi logic StockReportController@index).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    private function buildStockData(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'item_id' => 'nullable|exists:items,id_item',
        ]);

        // Default: laporan bulan ini
        $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? Carbon::now()->toDateString();

        // Generate report (Collection)
        $reportData = $this->stockReportService->generateReport(
            $startDate,
            $endDate,
            $validated['item_id'] ?? null
        );

        // Summary tetap berdasarkan semua data, bukan hanya halaman
        $summary = $this->stockReportService->getSummary($reportData);

        // Pagination manual untuk tampilan tabel
        $perPage = max(1, min((int) ($request->input('per_page') ?: 10), 100));
        $page = max(1, (int) ($request->input('page') ?: 1));

        $total = $reportData->count();
        $pageItems = $reportData->slice(($page - 1) * $perPage, $perPage)->values();

        $reportPaginator = new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        // Ambil data item kecil saja untuk placeholder (selected item).
        $items = collect();
        $selectedItemId = $validated['item_id'] ?? null;
        if (!empty($selectedItemId)) {
            $selected = Items::where('id_item', $selectedItemId)->first();
            if ($selected) {
                $items = collect([$selected]);
            }
        }

        return [
            // reportData = paginator (sesuai nama variabel di StockReportController@index)
            'reportData' => $reportPaginator,
            'summary' => $summary,
            'items' => $items,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedItemId' => $selectedItemId,
            'perPage' => $perPage,
        ];
    }

    /**
     * Data Laporan Penjualan (replikasi logic SalesReportController@index).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    private function buildSalesData(Request $request): array
    {
        $salesRecaps = $this->salesReportService->getPaginatedRecaps($request);
        $summary = $this->salesReportService->calculateSummary($request);
        $monthlyTrend = $this->salesReportService->getMonthlyTrend($request);
        $statusDistribution = $this->salesReportService->getStatusDistribution($request);
        $topProjects = $this->salesReportService->getTopProjects($request);

        return compact(
            'salesRecaps',
            'summary',
            'monthlyTrend',
            'statusDistribution',
            'topProjects'
        );
    }

    /**
     * Data Laporan Pengeluaran (replikasi logic ExpenseReportController@index).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    private function buildExpenseData(Request $request): array
    {
        $expenseRecaps = $this->expenseReportService->buildIndexQuery($request)
            ->paginate(10)
            ->appends($request->all());

        $summary = $this->expenseReportService->calculateSummary($request);
        $monthlyTrend = $this->expenseReportService->getMonthlyTrend($request);
        $categoryDistribution = $this->expenseReportService->getCategoryDistribution($request);
        $cashFlow = $this->expenseReportService->getCashFlow($request);
        $categories = $this->expenseReportService->getActiveCategories();

        return compact(
            'expenseRecaps',
            'summary',
            'monthlyTrend',
            'categoryDistribution',
            'cashFlow',
            'categories'
        );
    }

    /**
     * Data Laporan Semen (replikasi logic CementReportController@index).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    private function buildCementData(Request $request): array
    {
        $cementDeliveryOrders = $this->cementReportService->getPaginatedDeliveryOrders($request);
        $summary = $this->cementReportService->calculateSummary($request);

        return compact(
            'cementDeliveryOrders',
            'summary'
        );
    }

    /**
     * Query dasar daftar Rekap Proyek untuk laporan.
     *
     * Superadmin melihat seluruh rekap; role lain hanya miliknya sendiri
     * (sama seperti modul Rekap Proyek & Laporan Keuangan Proyek). Relasi
     * yang dipakai untuk perhitungan (rab, paymentProofs, financialReport)
     * ikut di-load agar tidak terjadi N+1.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function baseRecapQuery(Request $request)
    {
        $user = auth()->user();

        return ProjectRecap::query()
            ->with(['creator', 'rab', 'paymentProofs', 'financialReport.items'])
            ->when($user && $user->role !== 'superadmin', fn ($query) => $query->where('created_by', $user->id))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('project_name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('month'), fn ($query) => $query->whereMonth('created_at', $request->month))
            ->when($request->filled('year'), fn ($query) => $query->whereYear('created_at', $request->year))
            ->orderByDesc('created_at');
    }

    /**
     * Data Rekap Proyek untuk tab laporan.
     *
     * Menampilkan ringkasan nilai proyek, pembayaran, dan sisa tagihan.
     * Ringkasan dihitung dari seluruh data yang lolos filter (bukan hanya
     * halaman aktif) agar konsisten dengan laporan lainnya.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    private function buildRecapData(Request $request): array
    {
        $collection = $this->baseRecapQuery($request)->get();

        // Filter status pembayaran (status turunan, dihitung per rekap).
        if ($request->filled('status')) {
            $status = $request->status;
            $collection = $collection->filter(function (ProjectRecap $recap) use ($status) {
                if ($recap->isFullyPaid()) {
                    $currentStatus = 'lunas';
                } elseif ($recap->getTotalPaidAmount() > 0) {
                    $currentStatus = 'sebagian';
                } else {
                    $currentStatus = 'belum';
                }

                return $currentStatus === $status;
            })->values();
        }

        $summary = [
            'total_projects' => $collection->count(),
            'total_rab' => (int) $collection->sum(fn (ProjectRecap $recap) => $recap->getTotalAmount()),
            'total_paid' => (int) $collection->sum(fn (ProjectRecap $recap) => $recap->getTotalPaidAmount()),
            'total_remaining' => (int) $collection->sum(fn (ProjectRecap $recap) => $recap->getRemainingAmount()),
            'paid_projects' => $collection->filter(fn (ProjectRecap $recap) => $recap->isFullyPaid())->count(),
        ];

        $recaps = $this->paginateCollection($collection, $request);

        return compact('recaps', 'summary');
    }

    /**
     * Data Laporan Keuangan Proyek untuk tab laporan.
     *
     * Menampilkan ringkasan uang masuk, uang keluar, dan saldo per proyek
     * berdasarkan baris transaksi (item) pada laporan keuangan proyek.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    private function buildFinancialData(Request $request): array
    {
        $collection = $this->baseRecapQuery($request)->get()
            ->map(function (ProjectRecap $recap) {
                $items = $recap->financialReport?->items
                    ->where('is_informational', false)
                    ?? collect();

                $recap->fin_income = (int) $items->sum('income_amount');
                $recap->fin_expense = (int) $items->sum('expense_amount');
                $recap->fin_balance = $recap->fin_income - $recap->fin_expense;
                $recap->fin_transactions = $items->count();

                return $recap;
            });

        // Filter status laporan (ada/belum ada transaksi).
        if ($request->filled('status')) {
            $status = $request->status;
            $collection = $collection->filter(function (ProjectRecap $recap) use ($status) {
                if ($status === 'with_transactions') {
                    return $recap->fin_transactions > 0;
                }

                return $recap->fin_transactions === 0;
            })->values();
        }

        $summary = [
            'total_projects' => $collection->count(),
            'total_income' => (int) $collection->sum('fin_income'),
            'total_expense' => (int) $collection->sum('fin_expense'),
            'total_balance' => (int) $collection->sum('fin_balance'),
            'total_transactions' => (int) $collection->sum('fin_transactions'),
        ];

        $recaps = $this->paginateCollection($collection, $request);

        return compact('recaps', 'summary');
    }

    /**
     * Pagination manual untuk hasil laporan berbentuk Collection.
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    private function paginateCollection($collection, Request $request): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($request->input('per_page') ?: 10), 100));
        $page = max(1, (int) ($request->input('page') ?: 1));

        $total = $collection->count();
        $pageItems = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
