<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreProyekInvoiceRequest;
use App\Http\Requests\Finance\UpdateProyekInvoiceRequest;
use App\Models\Finance\InvoiceProyek;
use App\Models\Sdm\Division;
use App\Models\Sdm\Executive;
use App\Exports\Finance\ProyekInvoiceExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\Finance\ProyekInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller untuk menangani CRUD Invoice Proyek.
 *
 * Menangani operasi pembuatan, pembacaan, pembaruan, dan penghapusan
 * invoice proyek, termasuk cetak PDF dan export Excel.
 */
class ProyekInvoiceController extends Controller
{
    public function __construct(
        protected ProyekInvoiceService $service,
        protected \App\Services\Finance\PaymentAccountService $paymentAccountService
    ) {}

    /**
     * Mendapatkan nomor invoice proyek berikutnya secara otomatis.
     *
     * Format: {n}/{n}/PT.AKI/{yy}
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
     * Menampilkan halaman daftar invoice proyek dengan filter dan pencarian.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $invoices = $this->service->baseQuery($request)->paginate(15);
        $paymentAccounts = $this->paymentAccountService->getActiveAccounts();
        $executives = Executive::where('created_by', auth()->id())->orderBy('name')->get();
        $divisions = Division::where('created_by', auth()->id())->orderBy('name')->get();

        return view('pages.finance.project-invoices', compact('invoices', 'paymentAccounts', 'executives', 'divisions'));
    }

    /**
     * Menyimpan invoice proyek baru ke database.
     *
     * @param  \App\Http\Requests\Finance\StoreProyekInvoiceRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreProyekInvoiceRequest $request)
    {
        $items = $this->service->normalizeInvoiceItems(json_decode($request->items, true));

        if (empty($items)) {
            return back()->with('error', 'Data items tidak valid')->withInput();
        }

        $data = $request->validated();

        if (empty($data['invoice_number']) || str_contains($data['invoice_number'], 'Akan digenerate')) {
            $data['invoice_number'] = $this->service->generateInvoiceNumber();
        }

        $this->service->createInvoice($data, $items);

        return redirect()->route('proyek-invoice.index')
            ->with('success', 'Invoice proyek berhasil ditambahkan!');
    }

    /**
     * Mengupdate invoice proyek yang sudah ada.
     *
     * @param  \App\Http\Requests\Finance\UpdateProyekInvoiceRequest  $request
     * @param  \App\Models\Finance\InvoiceProyek  $proyek_invoice
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateProyekInvoiceRequest $request, InvoiceProyek $proyek_invoice)
    {
        try {
            $items = $this->service->normalizeInvoiceItems($request->items);

            if (empty($items)) {
                return back()->with('error', 'Data items tidak valid')->withInput();
            }

            $this->service->updateInvoice($proyek_invoice, $request->validated(), $items);

            return redirect()->route('proyek-invoice.index')
                ->with('success', 'Invoice proyek berhasil diupdate!');
        } catch (\Exception $e) {
            Log::error('Proyek Invoice update failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat mengupdate invoice. Silakan coba lagi.')->withInput();
        }
    }

    /**
     * Mengambil data invoice untuk modal edit (JSON response).
     *
     * @param  \App\Models\Finance\InvoiceProyek  $proyek_invoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(InvoiceProyek $proyek_invoice)
    {
        return response()->json([
            'invoice' => $proyek_invoice,
            'items' => json_decode($proyek_invoice->items),
        ]);
    }

    /**
     * Menghapus beberapa invoice proyek sekaligus (bulk delete).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request)
    {
        $ids = $request->input('selected_invoices', []);

        if (empty($ids)) {
            return redirect()->route('proyek-invoice.index')
                ->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        $deletedCount = $this->service->destroySelected($ids);

        return redirect()->route('proyek-invoice.index')
            ->with('success', "{$deletedCount} data terpilih berhasil dihapus.");
    }

    /**
     * Mencetak invoice proyek dalam format PDF untuk diunduh.
     *
     * @param  string  $invoiceNumber
     * @return \Barryvdh\DomPDF\PDF|\Symfony\Component\HttpFoundation\Response
     */
    public function printPdf($invoiceNumber)
    {
        $invoice = InvoiceProyek::where('invoice_number', $invoiceNumber)->firstOrFail();
        $invoice->items = $this->service->normalizeInvoiceItems($invoice->items);

        $pdf = Pdf::loadView('exports.finance.project-invoice-pdf', compact('invoice'));
        $pdf->setPaper('a4', 'portrait');

        $safeFileName = str_replace(['/', '\\'], '-', $invoice->invoice_number);
        $date = date('Y-m-d');
        $prefix =(auth()->check() && auth()->user()->role === 'admin') ? 'Invoice' : 'Invoice_Proyek';

        return $pdf->download("{$prefix}_{$safeFileName}_{$date}.pdf");
    }

    /**
     * Mencetak invoice proyek dalam format Excel untuk diunduh.
     *
     * @param  string  $invoiceNumber
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function printExcel($invoiceNumber)
    {
        $safeFileName = str_replace(['/', '\\'], '-', $invoiceNumber);
        $date = date('Y-m-d');
        $prefix = (auth()->check() && auth()->user()->role === 'admin') ? 'Invoice' : 'Invoice_Proyek';

        return Excel::download(new ProyekInvoiceExport($invoiceNumber), "{$prefix}_{$safeFileName}_{$date}.xlsx");
    }
}
