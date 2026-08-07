<?php

namespace App\Services\Report;

use App\Models\Inventory\Items;
use App\Services\Inventory\StockReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Service orchestrator untuk modul Laporan Akhir.
 *
 * Laporan Akhir menggabungkan tiga jenis laporan dalam satu halaman:
 * - Laporan Stok (Stock Report)  → StockReportService
 * - Laporan Penjualan (Sales Report) → SalesReportService
 * - Laporan Pengeluaran (Expense Report) → ExpenseReportService
 *
 * Service ini TIDAK menduplikasi business logic domain. Ia hanya
 * mengorkestrasi pemanggilan ke tiga service di atas sesuai tab yang aktif.
 * Filter tiap laporan tetap memakai parameter masing-masing (lihat partial view).
 *
 * Akses per tab meniru aturan di sidebar (resources/views/layouts/sidebar.blade.php):
 * - stock  : superadmin + user (bukan admin & bukan general_manager)
 * - sales  : superadmin + general_manager
 * - expense: superadmin + admin + general_manager
 */
class FinalReportService
{
    public function __construct(
        private StockReportService $stockReportService,
        private SalesReportService $salesReportService,
        private ExpenseReportService $expenseReportService,
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
        }

        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            $tabs[] = 'sales';
        }

        if ($user->isSuperAdmin() || $user->isAdmin() || $user->isGeneralManager()) {
            $tabs[] = 'expense';
        }

        return $tabs;
    }

    /**
     * Membangun seluruh data yang dibutuhkan view untuk tab aktif.
     *
     * @param  string              $tab      Tab aktif (stock|sales|expense)
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function build(string $tab, Request $request): array
    {
        return match ($tab) {
            'stock' => $this->buildStockData($request),
            'expense' => $this->buildExpenseData($request),
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
}
