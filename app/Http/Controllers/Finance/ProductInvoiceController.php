<?php

namespace App\Http\Controllers\Finance;

use App\Exports\Finance\ItemInvoiceExport;
use App\Exports\Finance\ItemInvoiceIndexExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ProductInvoiceStoreRequest;
use App\Http\Requests\Finance\ProductInvoiceUpdateRequest;
use App\Http\Requests\Finance\ProductInvoiceDestroySelectedRequest;
use App\Models\Finance\InvoiceBarang;
use App\Models\Inventory\Items;
use App\Models\Report\SalesRecap;
use App\Services\Finance\ProductInvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk modul Invoice Barang (Finance).
 *
 * Menangani operasi CRUD, cetak PDF/Excel, dan export rekap Invoice Barang.
 * Semua logika bisnis didelegasikan ke ProductInvoiceService.
 */
class ProductInvoiceController extends Controller
{
    public function __construct(
        private ProductInvoiceService $service
    ) {}

    /**
     * Menampilkan daftar Invoice Barang dengan filter dan pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $invoices = $this->service->baseQuery($request)->paginate(10)->appends($request->all());
        $summaryInvoices = (clone $this->service->baseQuery($request))->get();
        $totals = $this->service->buildTotals($summaryInvoices);
        $items = Items::query()->orderBy('name_item')->get();

        return view('pages.finance.product-invoices', compact('invoices', 'totals', 'items'));
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
     * Menyimpan Invoice Barang baru beserta Sales Recap terkait.
     *
     * @param  \App\Http\Requests\Finance\ProductInvoiceStoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ProductInvoiceStoreRequest $request)
    {
        $rawItems = $this->service->normalizeInvoiceItems($request->input('items'));

        if (empty($rawItems)) {
            return back()->with('error', 'Minimal harus ada 1 item!')->withInput();
        }

        if (empty($request->invoice_number) || str_contains($request->invoice_number, 'Akan digenerate')) {
            $request->merge(['invoice_number' => $this->service->generateInvoiceNumber()]);
        }

        DB::beginTransaction();
        try {
            $items = $this->service->processItemsForStore($rawItems);
            $totals = $this->service->calculateTotals($items);
            $salesRecapId = $this->service->generateSalesRecapId();

            SalesRecap::create([
                'id_sales_recap' => $salesRecapId,
                'date' => $request->invoice_date,
                'name_proyek' => $request->project_description,
                'items' => $items,
                'total_capital' => $totals['total_capital'],
                'total_selling' => $totals['total_selling'],
                'total_profit' => $totals['total_profit'],
                'status' => 'Belum Lunas',
            ]);

            InvoiceBarang::create([
                'invoice_number' => $request->invoice_number,
                'invoice_date' => $request->invoice_date,
                'recipient' => $request->recipient,
                'regarding' => $request->regarding,
                'project_description' => $request->project_description,
                'items' => $items,
                'total_capital' => $totals['total_capital'],
                'total_selling' => $totals['total_selling'],
                'total_profit' => $totals['total_profit'],
                'sales_recap_id' => $salesRecapId,
            ]);

            DB::commit();

            return redirect()->route('item-invoice.index')
                ->with('success', 'Invoice item berhasil ditambahkan dan otomatis masuk ke rekap penjualan!');
        } catch (\Throwable $throwable) {
            DB::rollBack();
            Log::error('Item Invoice store failed', [
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menyimpan invoice. Silakan coba lagi.')->withInput();
        }
    }

    /**
     * Mengambil data invoice untuk modal edit (AJAX response).
     *
     * @param  string  $invoiceNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(string $invoiceNumber)
    {
        $invoice = InvoiceBarang::where('invoice_number', $invoiceNumber)->firstOrFail();

        return response()->json([
            'invoice' => $invoice,
            'items' => is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items,
        ]);
    }

    /**
     * Mengupdate Invoice Barang dan Sales Recap terkait.
     *
     * @param  \App\Http\Requests\Finance\ProductInvoiceUpdateRequest  $request
     * @param  string  $invoiceNumber
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ProductInvoiceUpdateRequest $request, string $invoiceNumber)
    {
        $invoice = InvoiceBarang::where('invoice_number', $invoiceNumber)->firstOrFail();
        $salesRecap = SalesRecap::where('id_sales_recap', $invoice->sales_recap_id)->first();

        if (!$this->service->isEditable($salesRecap)) {
            return back()->with('error', 'Data yang sudah lunas tidak dapat diubah!')->withInput();
        }

        $rawItems = $this->service->normalizeInvoiceItems($request->input('items'));

        if (empty($rawItems)) {
            return back()->with('error', 'Minimal harus ada 1 item!')->withInput();
        }

        DB::beginTransaction();
        try {
            $oldItems = $this->service->getItemsFromSource($salesRecap, $invoice);

            $this->service->restoreStockFromItems($oldItems);
            $items = $this->service->processItemsForStore($rawItems);
            $totals = $this->service->calculateTotals($items);

            if ($salesRecap) {
                $salesRecap->update([
                    'date' => $request->invoice_date,
                    'name_proyek' => $request->project_description,
                    'items' => $items,
                    'total_capital' => $totals['total_capital'],
                    'total_selling' => $totals['total_selling'],
                    'total_profit' => $totals['total_profit'],
                ]);
            }

            $invoice->update([
                'invoice_date' => $request->invoice_date,
                'recipient' => $request->recipient,
                'regarding' => $request->regarding,
                'project_description' => $request->project_description,
                'items' => $items,
                'total_capital' => $totals['total_capital'],
                'total_selling' => $totals['total_selling'],
                'total_profit' => $totals['total_profit'],
                'sales_recap_id' => $salesRecap?->id_sales_recap ?? $invoice->sales_recap_id,
            ]);

            DB::commit();

            return redirect()->route('item-invoice.index')
                ->with('success', 'Invoice item berhasil diupdate!');
        } catch (\Throwable $throwable) {
            DB::rollBack();
            Log::error('Item Invoice update failed', [
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat mengupdate invoice. Silakan coba lagi.')->withInput();
        }
    }

    /**
     * Menghapus beberapa Invoice Barang sekaligus.
     *
     * @param  \App\Http\Requests\Finance\ProductInvoiceDestroySelectedRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(ProductInvoiceDestroySelectedRequest $request)
    {
        $selectedInvoiceNumbers = $request->input('selected_invoices', []);

        DB::beginTransaction();
        try {
            $invoices = InvoiceBarang::whereIn('invoice_number', $selectedInvoiceNumbers)->get();

            foreach ($invoices as $invoice) {
                $salesRecap = $invoice->salesRecap;

                if (!$this->service->isEditable($salesRecap)) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'Invoice "' . $invoice->invoice_number . '" sudah Lunas dan tidak dapat dihapus!');
                }

                $items = $this->service->getItemsFromSource($salesRecap, $invoice);

                $this->service->restoreStockFromItems($items);

                if ($salesRecap) {
                    $salesRecap->delete();
                }

                $invoice->delete();
            }

            DB::commit();

            return redirect()->route('item-invoice.index')
                ->with('success', count($invoices) . ' invoice berhasil dihapus!');
        } catch (\Throwable $throwable) {
            DB::rollBack();
            Log::error('Item Invoice destroySelected failed', [
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menghapus invoice. Silakan coba lagi.');
        }
    }

    /**
     * Mencetak Invoice Barang sebagai PDF.
     *
     * @param  string  $invoiceNumber
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function printPdf(string $invoiceNumber)
    {
        $invoice = InvoiceBarang::with('salesRecap')->where('invoice_number', $invoiceNumber)->firstOrFail();

        $pdf = Pdf::loadView('exports.finance.item-invoice-pdf', compact('invoice'));
        $pdf->setPaper('a4', 'portrait');

        $safeFileName = str_replace(['/', '\\'], '-', $invoice->invoice_number);
        $date = date('Y-m-d');

        return $pdf->download("Invoice_Barang_{$safeFileName}_{$date}.pdf");
    }

    /**
     * Mencetak Invoice Barang sebagai Excel.
     *
     * @param  string  $invoiceNumber
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function printExcel(string $invoiceNumber)
    {
        $safeFileName = str_replace(['/', '\\'], '-', $invoiceNumber);
        $date = date('Y-m-d');

        return Excel::download(new ItemInvoiceExport($invoiceNumber), "Invoice_Barang_{$safeFileName}_{$date}.xlsx");
    }

    /**
     * Export rekap Invoice Barang sebagai PDF.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf(Request $request)
    {
        $invoices = $this->service->baseQuery($request)->get();

        $pdf = Pdf::loadView('exports.finance.item-invoice-index-pdf', [
            'invoices' => $invoices,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Rekap_Invoice_Barang_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export rekap Invoice Barang sebagai Excel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        $invoices = $this->service->baseQuery($request)->get();

        return Excel::download(
            new ItemInvoiceIndexExport($invoices, $request->month, $request->year),
            'Rekap_Invoice_Barang_' . date('Y-m-d') . '.xlsx'
        );
    }
}
