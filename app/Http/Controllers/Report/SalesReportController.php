<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Services\Report\SalesReportService;
use Illuminate\Http\Request;

/**
 * Controller untuk Laporan Penjualan (Sales Report).
 *
 * Hanya bisa diakses oleh General Manager (melalui route middleware).
 * Menampilkan dashboard ringkasan, trend bulanan, distribusi status,
 * top proyek, dan detail transaksi.
 *
 * Seluruh business logic didelegasikan ke SalesReportService.
 */
class SalesReportController extends Controller
{
    public function __construct(
        private SalesReportService $salesReportService
    ) {}

    /**
     * Menampilkan halaman laporan penjualan.
     *
     * Mengambil data dari SalesReportService:
     * - Paginated recaps dengan sorting
     * - Summary statistik (total penjualan, modal, profit, dll)
     * - Data trend bulanan untuk chart
     * - Distribusi status untuk chart
     * - Top 5 proyek berdasarkan profit
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $salesRecaps = $this->salesReportService->getPaginatedRecaps($request);
        $summary = $this->salesReportService->calculateSummary($request);
        $monthlyTrend = $this->salesReportService->getMonthlyTrend($request);
        $statusDistribution = $this->salesReportService->getStatusDistribution($request);
        $topProjects = $this->salesReportService->getTopProjects($request);

        return view('pages.report.sales-report', compact(
            'salesRecaps',
            'summary',
            'monthlyTrend',
            'statusDistribution',
            'topProjects'
        ));
    }
}
