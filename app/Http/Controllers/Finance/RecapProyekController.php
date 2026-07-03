<?php

namespace App\Http\Controllers\Finance;

use App\Exports\Finance\ProyekRecapExport;
use App\Http\Controllers\Controller;
use App\Models\Finance\InvoiceProyek;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RecapProyekController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->baseQuery($request);

        $invoices = (clone $query)
            ->orderByDesc('invoice_date')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->appends($request->all());

        $summaryInvoices = (clone $query)
            ->orderByDesc('invoice_date')
            ->orderByDesc('created_at')
            ->get();

        $totals = $this->buildTotals($summaryInvoices);
        $periodTitle = $this->buildPeriodTitle($request);

        return view('pages.finance.recap-proyek', compact('invoices', 'totals', 'periodTitle'));
    }

    public function exportExcel(Request $request)
    {
        $query = $this->baseQuery($request);

        $invoices = (clone $query)
            ->orderByDesc('invoice_date')
            ->orderByDesc('created_at')
            ->get();

        $totals = $this->buildTotals($invoices);
        $periodTitle = $this->buildPeriodTitle($request);
        $filename = 'Rekap_Proyek_' . date('Y-m-d') . '.xlsx';

        return Excel::download(new ProyekRecapExport($invoices, $totals, $periodTitle), $filename);
    }

    public function exportPdf(Request $request)
    {
        $query = $this->baseQuery($request);

        $invoices = (clone $query)
            ->orderByDesc('invoice_date')
            ->orderByDesc('created_at')
            ->get();

        $totals = $this->buildTotals($invoices);
        $periodTitle = $this->buildPeriodTitle($request);

        $pdf = Pdf::loadView('exports.finance.recap-proyek-pdf', [
            'invoices' => $invoices,
            'totals' => $totals,
            'periodTitle' => $periodTitle,
        ])->setPaper('a4', 'landscape');

        $filename = 'Rekap_Proyek_' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    private function baseQuery(Request $request)
    {
        return InvoiceProyek::query()
            ->with('paymentProofs')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('recipient', 'like', "%{$search}%")
                        ->orWhere('regarding', 'like', "%{$search}%")
                        ->orWhere('project_description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('month'), function ($query) use ($request) {
                $query->whereMonth('invoice_date', $request->month);
            })
            ->when($request->filled('year'), function ($query) use ($request) {
                $query->whereYear('invoice_date', $request->year);
            });
    }

    private function buildTotals($invoices): object
    {
        return (object) [
            'invoice_count' => $invoices->count(),
            'total_invoice' => $invoices->sum(fn($invoice) => (int) $invoice->getNetAmount()),
            'total_paid' => $invoices->sum(fn($invoice) => (int) $invoice->getTotalPaidAmount()),
            'total_remaining' => $invoices->sum(fn($invoice) => (int) $invoice->getRemainingAmount()),
            'paid_count' => $invoices->filter(fn($invoice) => $invoice->isFullyPaid())->count(),
            'unpaid_count' => $invoices->filter(fn($invoice) => !$invoice->isFullyPaid())->count(),
        ];
    }

    private function buildPeriodTitle(Request $request): string
    {
        $month = $request->get('month');
        $year = $request->get('year');

        if ($month && $year) {
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');

            return strtoupper($monthName) . ' ' . $year;
        }

        if ($month) {
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');

            return 'BULAN ' . strtoupper($monthName);
        }

        if ($year) {
            return 'TAHUN ' . $year;
        }

        return 'SEMUA PERIODE';
    }
}