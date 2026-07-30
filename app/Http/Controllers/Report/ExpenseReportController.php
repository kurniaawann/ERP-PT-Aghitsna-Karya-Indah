<?php

namespace App\Http\Controllers\Report;

use App\Exports\Report\ExpenseReportExport;
use App\Http\Controllers\Controller;
use App\Services\Report\ExpenseReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Controller untuk Laporan Pengeluaran (Expense Report).
 *
 * Menampilkan dashboard laporan pengeluaran dengan:
 * - Summary cards (total pemasukan, pengeluaran, saldo, transaksi)
 * - Chart trend bulanan (line chart)
 * - Chart distribusi kategori (horizontal bar chart)
 * - Chart perbandingan pemasukan vs pengeluaran (doughnut chart)
 * - Rincian cash flow
 * - Tabel ringkasan per kategori
 * - Tabel detail transaksi dengan pagination
 *
 * Akses: admin, general_manager (via role middleware).
 *
 * Tanggung jawab: Request handling, Response, View rendering.
 * Business logic didelegasikan ke ExpenseReportService.
 */
class ExpenseReportController extends Controller
{
    public function __construct(
        private ExpenseReportService $service
    ) {}

    /**
     * Menampilkan halaman laporan pengeluaran dengan semua komponen dashboard.
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $expenseRecaps = $this->service->buildIndexQuery($request)
            ->paginate(10)
            ->appends($request->all());

        $summary = $this->service->calculateSummary($request);
        $monthlyTrend = $this->service->getMonthlyTrend($request);
        $categoryDistribution = $this->service->getCategoryDistribution($request);
        $cashFlow = $this->service->getCashFlow($request);
        $categories = $this->service->getActiveCategories();

        return view('pages.report.expense-report', compact(
            'expenseRecaps',
            'summary',
            'monthlyTrend',
            'categoryDistribution',
            'cashFlow',
            'categories'
        ));
    }

    /**
     * Export laporan pengeluaran ke PDF.
     */
    public function exportPdf(Request $request)
    {
        $data = $this->service->buildExportData($request);

        $pdf = Pdf::loadView('exports.report.expense-report-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('Laporan_Pengeluaran_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export laporan pengeluaran ke Excel.
     */
    public function exportExcel(Request $request)
    {
        $data = $this->service->buildExportData($request);

        return Excel::download(
            new ExpenseReportExport($data['expenseRecaps'], $data['periodTitle'], $data['totals']),
            'Laporan_Pengeluaran_' . date('Y-m-d') . '.xlsx'
        );
    }
}
