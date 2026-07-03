<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\PurchaseInvoice;
use App\Exports\Finance\PurchaseInvoiceExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\InputNormalizer;
use App\Traits\HasBulkActions;

class PurchaseInvoiceController extends Controller
{
    use HasBulkActions;
    public function index(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');

        $query = PurchaseInvoice::query();

        // Filter pencarian
        $query->when($search, function ($q, $search) {
            $q->where('material_name', 'like', "%{$search}%")
                ->orWhere('item_name', 'like', "%{$search}%")
                ->orWhere('npwp', 'like', "%{$search}%");
        });

        // Filter bulan
        $query->when($month, function ($q, $month) {
            $q->whereMonth('date', $month);
        });

        // Filter tahun
        $query->when($year, function ($q, $year) {
            $q->whereYear('date', $year);
        });

        $invoices = $query->orderBy('date', 'desc')->paginate(15);

        return view('pages.finance.purchase-invoice', compact('invoices'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'selling_price' => InputNormalizer::normalizeCurrency($request->selling_price),
            'ppn_percentage' => InputNormalizer::normalizeDecimal($request->ppn_percentage),
        ]);

        $validated = $request->validate([
            'date' => 'required|date',
            'material_name' => 'required|string|max:255',
            'npwp' => 'required|string|max:50',
            'tax_number_code' => 'required|string|max:50',
            'item_name' => 'required|string|max:255',
            'selling_price' => 'required|numeric|min:0',
            'ppn_percentage' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        // Calculate PPN tax based on percentage
        $validated['ppn_tax'] = (int) ($validated['selling_price'] * $validated['ppn_percentage'] / 100);

        PurchaseInvoice::create($validated);

        return redirect()->route('purchase-invoice.index')
            ->with('success', 'Faktur pembelian berhasil ditambahkan!');
    }

    public function show(PurchaseInvoice $purchase_invoice)
    {
        return response()->json($purchase_invoice);
    }

    public function edit(PurchaseInvoice $purchase_invoice)
    {
        return response()->json($purchase_invoice);
    }

    public function update(Request $request, PurchaseInvoice $purchase_invoice)
    {
        $request->merge([
            'selling_price' => InputNormalizer::normalizeCurrency($request->selling_price),
            'ppn_percentage' => InputNormalizer::normalizeDecimal($request->ppn_percentage),
        ]);

        $validated = $request->validate([
            'date' => 'required|date',
            'material_name' => 'required|string|max:255',
            'npwp' => 'required|string|max:50',
            'tax_number_code' => 'required|string|max:50',
            'item_name' => 'required|string|max:255',
            'selling_price' => 'required|numeric|min:0',
            'ppn_percentage' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        // Calculate PPN tax based on percentage
        $validated['ppn_tax'] = (int) ($validated['selling_price'] * $validated['ppn_percentage'] / 100);

        $purchase_invoice->update($validated);

        return redirect()->route('purchase-invoice.index')
            ->with('success', 'Faktur pembelian berhasil diupdate!');
    }

    public function destroy(PurchaseInvoice $purchase_invoice)
    {
        $purchase_invoice->delete();

        return redirect()->route('purchase-invoice.index')
            ->with('success', 'Faktur pembelian berhasil dihapus!');
    }

    public function destroySelected(Request $request)
    {
        return $this->destroySelectedBy($request, PurchaseInvoice::class, 'selected_invoices', 'id', 'purchase-invoice.index');
    }

    public function printPdf($id)
    {
        $invoice = PurchaseInvoice::findOrFail($id);

        $pdf = Pdf::loadView('exports.finance.purchase-invoice-pdf', compact('invoice'));
        $pdf->setPaper('a4', 'portrait');

        $date = date('Y-m-d');
        return $pdf->download("Faktur_Pembelian_{$invoice->id}_{$date}.pdf");
    }

    public function printExcel()
    {
        return Excel::download(new PurchaseInvoiceExport(), 'Faktur_Pembelian_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export seluruh data ke Excel dengan support filter
     */
    public function exportExcel(Request $request)
    {
        return Excel::download(new PurchaseInvoiceExport($request), 'Faktur_Pembelian_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export seluruh data ke PDF dengan support filter
     */
    public function exportPdf(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');

        $query = PurchaseInvoice::query();

        // Filter pencarian
        $query->when($search, function ($q, $search) {
            $q->where('material_name', 'like', "%{$search}%")
                ->orWhere('item_name', 'like', "%{$search}%")
                ->orWhere('npwp', 'like', "%{$search}%");
        });

        // Filter bulan
        $query->when($month, function ($q, $month) {
            $q->whereMonth('date', $month);
        });

        // Filter tahun
        $query->when($year, function ($q, $year) {
            $q->whereYear('date', $year);
        });

        $invoices = $query->orderBy('date', 'desc')->get();

        $pdf = Pdf::loadView('exports.finance.purchase-invoices-pdf', compact('invoices'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('Faktur_Pembelian_' . date('Y-m-d') . '.pdf');
    }


}
