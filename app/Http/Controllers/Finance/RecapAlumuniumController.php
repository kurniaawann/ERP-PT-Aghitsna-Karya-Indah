<?php

namespace App\Http\Controllers\Finance;

use App\Exports\Finance\AlumuniumRecapExport;
use App\Http\Controllers\Controller;
use App\Services\Finance\RecapAlumuniumService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk Rekap Aluminium.
 *
 * Menangani request untuk menampilkan rekap invoice aluminium,
 * termasuk export ke Excel dan PDF.
 *
 * Business logic didelegasikan ke RecapAlumuniumService.
 */
class RecapAlumuniumController extends Controller
{
    public function __construct(
        protected RecapAlumuniumService $service
    ) {}

    /**
     * Menampilkan daftar rekap invoice aluminium dengan filter dan pagination.
     *
     * Role super admin diarahkan ke halaman Rekap ber-tab; role lain
     * (mis. admin) tetap memakai halaman standalone ini.
     *
     * @param  \Illuminate\Http\Request  $request  Filter: search, month, year
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        if ((auth()->user()?->role ?? null) === 'superadmin') {
            return redirect()->route('rekap.index', ['tab' => 'aluminium']);
        }

        return view('pages.finance.aluminium-recaps', $this->indexData($request));
    }

    /**
     * Menyiapkan data untuk halaman Rekap Alumunium.
     *
     * Dipakai oleh index() dan oleh RekapController (tab Alumunium).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function indexData(Request $request)
    {
        $query = $this->service->buildBaseQuery($request);

        $invoices = $this->service->getPaginatedInvoices($query, $request);
        $totals = $this->service->buildTotals($this->service->getAllInvoices($query));
        $periodTitle = $this->service->buildPeriodTitle($request);

        return compact('invoices', 'totals', 'periodTitle');
    }

    /**
     * Export rekap invoice aluminium ke Excel (XLSX).
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

        $filename = 'Rekap_Alumunium_' . date('Y-m-d') . '.xlsx';

        return Excel::download(new AlumuniumRecapExport($invoices, $totals, $periodTitle), $filename);
    }

    /**
     * Export rekap invoice aluminium ke PDF.
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

        $pdf = Pdf::loadView('exports.finance.aluminium-invoice-recap-pdf', [
            'invoices' => $invoices,
            'totals' => $totals,
            'periodTitle' => $periodTitle,
        ])->setPaper('a4', 'landscape');

        $filename = 'Rekap_Alumunium_' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}
