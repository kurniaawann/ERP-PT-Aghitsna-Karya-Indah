<?php

namespace App\Http\Controllers\Finance;

use App\Exports\Finance\ProyekRecapExport;
use App\Http\Controllers\Controller;
use App\Services\Finance\RecapProyekService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk Rekap Proyek.
 *
 * Menangani request untuk menampilkan rekap invoice proyek,
 * termasuk export ke Excel dan PDF.
 *
 * Business logic didelegasikan ke RecapProyekService.
 */
class RecapProyekController extends Controller
{
    public function __construct(
        protected RecapProyekService $service
    ) {}

    /**
     * Menampilkan daftar rekap invoice proyek dengan filter dan pagination.
     *
     * @param  \Illuminate\Http\Request  $request  Filter: search, month, year
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = $this->service->buildBaseQuery($request);

        $invoices = $this->service->getPaginatedInvoices($query, $request);
        $totals = $this->service->buildTotals($this->service->getAllInvoices($query));
        $periodTitle = $this->service->buildPeriodTitle($request);

        return view('pages.finance.project-recaps.index', compact('invoices', 'totals', 'periodTitle'));
    }

    /**
     * Export rekap invoice proyek ke Excel (XLSX).
     *
     * @param  \Illuminate\Http\Request  $request  Filter: search, month, year
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        $query = $this->service->buildBaseQuery($request);
        $invoices = $this->service->getAllInvoices($query);
        $totals = $this->service->buildTotals($invoices);
        $periodTitle = $this->service->buildPeriodTitle($request);

        $filename = 'Rekap_Proyek_' . date('Y-m-d') . '.xlsx';

        return Excel::download(new ProyekRecapExport($invoices, $totals, $periodTitle), $filename);
    }

    /**
     * Export rekap invoice proyek ke PDF.
     *
     * @param  \Illuminate\Http\Request  $request  Filter: search, month, year
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf(Request $request)
    {
        $query = $this->service->buildBaseQuery($request);
        $invoices = $this->service->getAllInvoices($query);
        $totals = $this->service->buildTotals($invoices);
        $periodTitle = $this->service->buildPeriodTitle($request);

        $pdf = Pdf::loadView('exports.finance.project-recaps-pdf', [
            'invoices' => $invoices,
            'totals' => $totals,
            'periodTitle' => $periodTitle,
        ])->setPaper('a4', 'landscape');

        $filename = 'Rekap_Proyek_' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}
