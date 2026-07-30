<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\PurchaseInvoice;
use App\Exports\Finance\PurchaseInvoiceExport;
use App\Http\Requests\Finance\StorePurchaseInvoiceRequest;
use App\Http\Requests\Finance\UpdatePurchaseInvoiceRequest;
use App\Services\Finance\PurchaseInvoiceService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\HasBulkActions;

/**
 * Controller untuk sub modul Faktur Pembelian.
 *
 * Menangani operasi CRUD, cetak PDF, dan export Excel
 * untuk data faktur pembelian.
 */
class PurchaseInvoiceController extends Controller
{
    use HasBulkActions;

    /**
     * Halaman index: menampilkan daftar faktur pembelian dengan filter.
     *
     * @param  Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $invoices = PurchaseInvoiceService::buildFilteredQuery($request)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('pages.finance.purchase-invoices', compact('invoices'));
    }

    /**
     * Simpan faktur pembelian baru.
     *
     * @param  StorePurchaseInvoiceRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StorePurchaseInvoiceRequest $request)
    {
        $validated = $request->validated();

        PurchaseInvoiceService::createInvoice($validated);

        return redirect()->route('purchase-invoice.index')
            ->with('success', 'Faktur pembelian berhasil ditambahkan!');
    }

    /**
     * Tampilkan detail faktur pembelian (JSON).
     *
     * @param  PurchaseInvoice $purchase_invoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(PurchaseInvoice $purchase_invoice)
    {
        return response()->json($purchase_invoice);
    }

    /**
     * Form edit faktur pembelian (JSON).
     *
     * @param  PurchaseInvoice $purchase_invoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(PurchaseInvoice $purchase_invoice)
    {
        return response()->json($purchase_invoice);
    }

    /**
     * Update faktur pembelian.
     *
     * @param  UpdatePurchaseInvoiceRequest $request
     * @param  PurchaseInvoice              $purchase_invoice
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdatePurchaseInvoiceRequest $request, PurchaseInvoice $purchase_invoice)
    {
        $validated = $request->validated();

        PurchaseInvoiceService::updateInvoice($purchase_invoice, $validated);

        return redirect()->route('purchase-invoice.index')
            ->with('success', 'Faktur pembelian berhasil diupdate!');
    }

    /**
     * Hapus faktur pembelian.
     *
     * @param  PurchaseInvoice $purchase_invoice
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(PurchaseInvoice $purchase_invoice)
    {
        $purchase_invoice->delete();

        return redirect()->route('purchase-invoice.index')
            ->with('success', 'Faktur pembelian berhasil dihapus!');
    }

    /**
     * Hapus beberapa faktur pembelian sekaligus (bulk delete).
     *
     * @param  Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request)
    {
        return $this->destroySelectedBy(
            $request,
            PurchaseInvoice::class,
            'selected_invoices',
            'id',
            'purchase-invoice.index'
        );
    }

    /**
     * Cetak PDF untuk satu faktur pembelian.
     *
     * @param  int $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function printPdf($id)
    {
        $invoice = PurchaseInvoice::findOrFail($id);
        $invoices = collect([$invoice]);

        $pdf = Pdf::loadView('exports.finance.purchase-invoice-pdf', compact('invoices'));
        $pdf->setPaper('a4', 'landscape');

        $date = date('Y-m-d');
        return $pdf->download("Faktur_Pembelian_{$invoice->id}_{$date}.pdf");
    }

    /**
     * Export seluruh data ke Excel (tanpa filter).
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function printExcel()
    {
        return Excel::download(new PurchaseInvoiceExport(), 'Faktur_Pembelian_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export data ke Excel dengan support filter.
     *
     * @param  Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        return Excel::download(new PurchaseInvoiceExport($request), 'Faktur_Pembelian_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export data ke PDF dengan support filter.
     *
     * @param  Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf(Request $request)
    {
        $invoices = PurchaseInvoiceService::buildFilteredQuery($request)
            ->orderBy('created_at', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.finance.purchase-invoice-pdf', compact('invoices'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('Faktur_Pembelian_' . date('Y-m-d') . '.pdf');
    }
}
