<?php

namespace App\Http\Controllers\Report;

use App\Exports\Report\SalesReportExport;
use App\Http\Controllers\Controller;
use App\Services\Report\SalesReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

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

    /**
     * Export laporan penjualan ke PDF.
     */
    public function exportPdf(Request $request)
    {
        $data = $this->salesReportService->buildExportData($request);

        $pdf =Pdf::loadView('exports.report.sales-report-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('Laporan_Penjualan_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export laporan penjualan ke Excel.
     */
    public function exportExcel(Request $request)
    {
        $data = $this->salesReportService->buildExportData($request);

        return Excel::download(
            new SalesReportExport($data['projects'], $data['periodTitle'], $data['grandTotal']),
            'Laporan_Penjualan_' . date('Y-m-d') . '.xlsx'
        );
    }
}
