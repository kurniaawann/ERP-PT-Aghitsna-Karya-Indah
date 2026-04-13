<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\PurchaseInvoice;
use App\Exports\Finance\PurchaseInvoiceExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseInvoiceController extends Controller
{
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

        $invoices = $query->orderBy('date', 'desc')->paginate(10);

        return view('pages.finance.purchase-invoice', compact('invoices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'material_name' => 'required|string|max:255',
            'npwp' => 'required|string|max:50',
            'tax_number_code' => 'required|string|max:50',
            'item_name' => 'required|string|max:255',
            'selling_price' => 'required|integer|min:0',
            'ppn_tax' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

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
        $validated = $request->validate([
            'date' => 'required|date',
            'material_name' => 'required|string|max:255',
            'npwp' => 'required|string|max:50',
            'tax_number_code' => 'required|string|max:50',
            'item_name' => 'required|string|max:255',
            'selling_price' => 'required|integer|min:0',
            'ppn_tax' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

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
        $selectedIds = $request->input('selected_invoices', []);

        if (empty($selectedIds)) {
            return redirect()->back()->with('error', 'Tidak ada faktur yang dipilih untuk dihapus.');
        }

        PurchaseInvoice::whereIn('id', $selectedIds)->delete();

        return redirect()->route('purchase-invoice.index')
            ->with('success', count($selectedIds) . ' faktur pembelian berhasil dihapus!');
    }

    public function printPdf($id)
    {
        $invoice = PurchaseInvoice::findOrFail($id);

        $pdf = Pdf::loadView('exports.finance.purchase-invoice-pdf', compact('invoice'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('Faktur-Pembelian-' . $invoice->id . '.pdf');
    }

    public function printExcel()
    {
        return Excel::download(new PurchaseInvoiceExport(), 'Faktur-Pembelian.xlsx');
    }

    /**
     * Export seluruh data ke Excel dengan support filter
     */
    public function exportExcel(Request $request)
    {
        return Excel::download(new PurchaseInvoiceExport($request), 'Faktur-Pembelian.xlsx');
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

        return $pdf->download('Faktur-Pembelian-' . now()->format('d-m-Y') . '.pdf');
    }
}
