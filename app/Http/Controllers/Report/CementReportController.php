<?php

namespace App\Http\Controllers\Report;

use App\Exports\Report\CementReportExport;
use App\Http\Controllers\Controller;
use App\Services\Report\CementReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk export Laporan Semen.
 *
 * Halaman tampilannya merupakan tab "Laporan Semen" di dalam Laporan Akhir
 * (FinalReportController). Controller ini hanya menyediakan endpoint export
 * PDF & Excel untuk laporan semen (gabungan DO Semen + Data Semen).
 *
 * Business logic didelegasikan ke CementReportService.
 */
class CementReportController extends Controller
{
    public function __construct(
        private readonly CementReportService $service
    ) {}

    /**
     * Export Laporan Semen ke format PDF.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf(Request $request)
    {
        $data = [
            'deliveryOrders' => $this->service->getAllDeliveryOrders($request),
            'summary' => $this->service->calculateSummary($request),
            'periodTitle' => $this->buildPeriodTitle($request),
        ];

        $pdf = Pdf::loadView('exports.report.cement-report-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('Laporan_Semen_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Laporan Semen ke format Excel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        return Excel::download(
            new CementReportExport(
                $this->service->getAllDeliveryOrders($request),
                $this->buildPeriodTitle($request)
            ),
            'Laporan_Semen_' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Bangun label periode dari filter bulan/tahun.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    private function buildPeriodTitle(Request $request): string
    {
        $month = $request->month;
        $year = $request->year ?: date('Y');

        if ($month) {
            $monthName = \Carbon\Carbon::create(null, (int) $month, 1)->locale('id')->translatedFormat('F');

            return 'BULAN ' . strtoupper($monthName) . ' ' . $year;
        }

        return 'TAHUN ' . $year;
    }
}
