<?php

namespace App\Http\Controllers\Finance;

use App\Exports\Finance\SemenInvoiceExport;
use App\Exports\Finance\SemenInvoiceIndexExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreSemenInvoiceRequest;
use App\Http\Requests\Finance\UpdateSemenInvoiceRequest;
use App\Models\Finance\InvoiceSemen;
use App\Models\Inventory\Cement;
use App\Services\Finance\PaymentAccountService;
use App\Services\Finance\SemenInvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk modul Invoice Semen (Finance).
 *
 * Menangani operasi CRUD, cetak PDF/Excel per invoice, dan export rekap
 * Invoice Semen. Logika bisnis didelegasikan ke SemenInvoiceService.
 */
class SemenInvoiceController extends Controller
{
    public function __construct(
        private SemenInvoiceService $service,
        private PaymentAccountService $paymentAccountService
    ) {}

    /**
     * Menampilkan daftar Invoice Semen dengan filter dan pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $invoices = $this->service->baseQuery($request)->paginate(10)->appends($request->all());

        $paymentAccounts = $this->paymentAccountService->getActiveAccounts();

        return view('pages.finance.semen-invoices', compact('invoices', 'paymentAccounts'));
    }

    /**
     * Menghasilkan nomor invoice berikutnya (AJAX response).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNextInvoiceNumber()
    {
        return response()->json([
            'invoice_number' => $this->service->generateInvoiceNumber(),
        ]);
    }

    /**
     * Mengambil Data Semen (tabel `cements`) untuk dropdown Invoice Semen.
     *
     * Dipanggil JS saat membuka/mencari dropdown "Pilih Data Semen" agar
     * daftar selalu mutakhir (dibaca langsung dari database). Mendukung
     * filter kata kunci opsional (no, nama_proyek, name, tanggal).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cementsData(Request $request)
    {
        $cements = Cement::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($q) use ($search) {
                    $q->where('no', 'like', "%{$search}%")
                        ->orWhere('nama_proyek', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('tanggal', 'like', "%{$search}%");
                });
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('no', 'desc')
            ->get();

        return response()->json(
            $cements->map(fn ($c) => [
                'no' => $c->no,
                'tanggal' => optional($c->tanggal)->format('Y-m-d'),
                'nama_proyek' => $c->nama_proyek,
                'name' => $c->name,
                'jumlah' => $c->jumlah,
                'satuan' => $c->satuan,
                'harga' => $c->harga,
                'total' => $c->total,
            ])->values()
        );
    }

    /**
     * Menyimpan Invoice Semen baru.
     *
     * @param  \App\Http\Requests\Finance\StoreSemenInvoiceRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreSemenInvoiceRequest $request)
    {
        $projects = $this->service->normalizeProjects($request->input('projects'));

        if (empty($projects)) {
            return back()->with('error', 'Minimal harus ada 1 proyek dengan data lengkap!')->withInput();
        }

        $data = $request->validated();

        if (empty($data['invoice_number']) || str_contains($data['invoice_number'], 'Akan digenerate')) {
            $data['invoice_number'] = $this->service->generateInvoiceNumber();
        }

        $this->service->createInvoice($data, $projects);

        return redirect()->route('semen-invoice.index')
            ->with('success', 'Invoice semen berhasil ditambahkan!');
    }

    /**
     * Mengambil data invoice untuk modal edit (AJAX response).
     *
     * @param  string  $invoiceNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(string $invoiceNumber)
    {
        $invoice = InvoiceSemen::where('invoice_number', $invoiceNumber)->firstOrFail();

        return response()->json([
            'invoice' => $invoice,
            'projects' => is_string($invoice->projects) ? json_decode($invoice->projects, true) : $invoice->projects,
        ]);
    }

    /**
     * Mengupdate Invoice Semen.
     *
     * @param  \App\Http\Requests\Finance\UpdateSemenInvoiceRequest  $request
     * @param  string  $invoiceNumber
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateSemenInvoiceRequest $request, string $invoiceNumber)
    {
        $invoice = InvoiceSemen::where('invoice_number', $invoiceNumber)->firstOrFail();

        $projects = $this->service->normalizeProjects($request->input('projects'));

        if (empty($projects)) {
            return back()->with('error', 'Minimal harus ada 1 proyek dengan data lengkap!')->withInput();
        }

        $this->service->updateInvoice($invoice, $request->validated(), $projects);

        return redirect()->route('semen-invoice.index')
            ->with('success', 'Invoice semen berhasil diupdate!');
    }

    /**
     * Menghapus beberapa Invoice Semen sekaligus.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request)
    {
        $selectedInvoiceNumbers = $request->input('selected_invoices', []);
        $isAjax = $request->ajax();

        if (empty($selectedInvoiceNumbers)) {
            $msg = 'Tidak ada data yang dipilih untuk dihapus.';

            return $isAjax
                ? response()->json(['success' => false, 'message' => $msg])
                : back()->with('error', $msg);
        }

        $deletedCount = $this->service->destroySelected($selectedInvoiceNumbers);

        $msg = "{$deletedCount} invoice semen berhasil dihapus.";

        return $isAjax
            ? response()->json(['success' => true, 'message' => $msg])
            : back()->with('success', $msg);
    }

    /**
     * Mencetak Invoice Semen sebagai PDF.
     *
     * @param  string  $invoiceNumber
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function printPdf(string $invoiceNumber)
    {
        $invoice = InvoiceSemen::where('invoice_number', $invoiceNumber)->firstOrFail();

        $pdf = Pdf::loadView('exports.finance.semen-invoice-pdf', compact('invoice'));
        $pdf->setPaper('a4', 'portrait');

        $safeFileName = str_replace(['/', '\\'], '-', $invoice->invoice_number);
        $date = date('Y-m-d');

        return $pdf->download("Invoice_Semen_{$safeFileName}_{$date}.pdf");
    }

    /**
     * Mencetak Invoice Semen sebagai Excel.
     *
     * @param  string  $invoiceNumber
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function printExcel(string $invoiceNumber)
    {
        $safeFileName = str_replace(['/', '\\'], '-', $invoiceNumber);
        $date = date('Y-m-d');

        return Excel::download(new SemenInvoiceExport($invoiceNumber), "Invoice_Semen_{$safeFileName}_{$date}.xlsx");
    }

    /**
     * Export rekap Invoice Semen sebagai PDF.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf(Request $request)
    {
        $invoices = $this->service->baseQuery($request)->get();

        $pdf = Pdf::loadView('exports.finance.semen-invoice-recap-pdf', [
            'invoices' => $invoices,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Rekap_Invoice_Semen_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export rekap Invoice Semen sebagai Excel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        $invoices = $this->service->baseQuery($request)->get();

        return Excel::download(
            new SemenInvoiceIndexExport($invoices, $request->month, $request->year),
            'Rekap_Invoice_Semen_' . date('Y-m-d') . '.xlsx'
        );
    }
}