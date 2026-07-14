<?php

namespace App\Http\Controllers\Finance;

use App\Exports\Finance\AlumuniumInvoiceExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\AlumuniumInvoiceStoreRequest;
use App\Http\Requests\Finance\AlumuniumInvoiceUpdateRequest;
use App\Models\Finance\InvoiceAlumunium;
use App\Models\Finance\PaymentAccount;
use App\Services\Finance\AlumuniumInvoiceService;
use App\Traits\HasBulkActions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk modul Invoice Alumunium (Finance).
 *
 * Menangani operasi CRUD, cetak PDF/Excel Invoice Alumunium.
 * Semua logika bisnis didelegasikan ke AlumuniumInvoiceService.
 */
class AlumuniumInvoiceController extends Controller
{
    use HasBulkActions;

    public function __construct(
        private AlumuniumInvoiceService $service
    ) {}

    /**
     * Menampilkan daftar Invoice Alumunium dengan filter dan pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $invoices = $this->service->baseQuery($request)->paginate(15);
        $paymentAccounts = PaymentAccount::active()->get();

        return view('pages.finance.aluminium-invoices.index', compact('invoices', 'paymentAccounts'));
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
     * Menyimpan Invoice Alumunium baru.
     *
     * @param  \App\Http\Requests\Finance\AlumuniumInvoiceStoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(AlumuniumInvoiceStoreRequest $request)
    {
        $items = $this->service->normalizeInvoiceItems($request->items);

        if (empty($items)) {
            return back()->with('error', 'Minimal harus ada 1 item!')->withInput();
        }

        // Auto-generate invoice number jika kosong atau placeholder
        if (empty($request->invoice_number) || str_contains($request->invoice_number, 'Akan digenerate')) {
            $request->merge(['invoice_number' => $this->service->generateInvoiceNumber()]);
        }

        $totalAmount = $this->service->calculateItemsTotal($items);
        $calculations = $this->service->calculateFromRequest($request, $totalAmount);

        $data = $request->all();
        $data['items'] = $items;
        $data['total_amount'] = $totalAmount;
        $data['total_after_discount'] = $calculations['totalAfterDiscount'] > 0 && $calculations['totalAfterDiscount'] != $totalAmount
            ? $calculations['totalAfterDiscount']
            : null;
        $data['dp_amount'] = $calculations['dpAmount'] > 0 ? $calculations['dpAmount'] : null;

        InvoiceAlumunium::create($data);

        return redirect()->route('alumunium-invoice.index')
            ->with('success', 'Invoice berhasil ditambahkan!');
    }

    /**
     * Mengambil data invoice untuk modal edit (AJAX response).
     *
     * @param  string  $invoiceNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(string $invoiceNumber)
    {
        $invoice = InvoiceAlumunium::where('invoice_number', $invoiceNumber)->firstOrFail();

        return response()->json([
            'invoice' => $invoice,
            'items' => $invoice->items, // Sudah di-decode otomatis oleh model cast
        ]);
    }

    /**
     * Mengupdate Invoice Alumunium.
     *
     * @param  \App\Http\Requests\Finance\AlumuniumInvoiceUpdateRequest  $request
     * @param  string  $invoiceNumber
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(AlumuniumInvoiceUpdateRequest $request, string $invoiceNumber)
    {
        try {
            $invoice = InvoiceAlumunium::where('invoice_number', $invoiceNumber)->firstOrFail();

            $items = $this->service->normalizeInvoiceItems($request->items);
            $totalAmount = $this->service->calculateItemsTotal($items);
            $calculations = $this->service->calculateFromRequest($request, $totalAmount);

            $invoice->update([
                'invoice_date' => $request->invoice_date,
                'recipient' => $request->recipient,
                'regarding' => $request->regarding ?? null,
                'project_description' => $request->project_description,
                'items' => $items,
                'total_amount' => $totalAmount,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'total_after_discount' => $calculations['totalAfterDiscount'] > 0 && $calculations['totalAfterDiscount'] != $totalAmount
                    ? $calculations['totalAfterDiscount']
                    : null,
                'dp_type' => $request->dp_type,
                'dp_value' => $request->dp_value,
                'dp_amount' => $calculations['dpAmount'] > 0 ? $calculations['dpAmount'] : null,
                'selected_payment_accounts' => $request->selected_payment_accounts,
            ]);

            return redirect()->route('alumunium-invoice.index')
                ->with('success', 'Invoice berhasil diupdate!');
        } catch (\Exception $e) {
            Log::error('Alumunium Invoice update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat mengupdate invoice. Silakan coba lagi.')->withInput();
        }
    }

    /**
     * Menghapus beberapa Invoice Alumunium sekaligus.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request)
    {
        return $this->destroySelectedBy(
            $request,
            InvoiceAlumunium::class,
            'selected_invoices',
            'invoice_number',
            'alumunium-invoice.index'
        );
    }

    /**
     * Mencetak Invoice Alumunium sebagai PDF.
     *
     * @param  string  $invoiceNumber
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function printPdf(string $invoiceNumber)
    {
        $invoice = InvoiceAlumunium::where('invoice_number', $invoiceNumber)->firstOrFail();
        $invoice->items = $this->service->normalizeInvoiceItems($invoice->items);

        $pdf = Pdf::loadView('exports.finance.alumunium-invoice-pdf', compact('invoice'));
        $pdf->setPaper('a4', 'portrait');

        $safeFileName = str_replace(['/', '\\'], '-', $invoice->invoice_number);
        $date = date('Y-m-d');

        return $pdf->download("Invoice_Aluminium_{$safeFileName}_{$date}.pdf");
    }

    /**
     * Mencetak Invoice Alumunium sebagai Excel.
     *
     * @param  string  $invoiceNumber
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function printExcel(string $invoiceNumber)
    {
        $safeFileName = str_replace(['/', '\\'], '-', $invoiceNumber);
        $date = date('Y-m-d');

        return Excel::download(new AlumuniumInvoiceExport($invoiceNumber), "Invoice_Aluminium_{$safeFileName}_{$date}.xlsx");
    }
}
