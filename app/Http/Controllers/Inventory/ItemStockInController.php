<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockIn;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ItemStockInController extends Controller
{
    private function generateIdStockIn()
    {
        $lastRecord = ItemStockIn::orderBy('id_stock_in', 'desc')->first();

        if (!$lastRecord) {
            return 'SIN-' . date('Ymd') . '-0001';
        }

        $lastNumber = (int) substr($lastRecord->id_stock_in, -4);
        return 'SIN-' . date('Ymd') . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        $stockIns = ItemStockIn::query()
            ->with('item')
            ->when($month, function ($query, $month) {
                $query->whereMonth('tanggal', $month);
            })
            ->when($year, function ($query, $year) {
                $query->whereYear('tanggal', $year);
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('id_stock_in', 'desc')
            ->paginate(10);

        $items = Items::orderBy('id_item', 'asc')->get();

        return view('pages.inventory.stock-in', compact('stockIns', 'items'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_item' => 'required|exists:items,id_item',
            'quantity' => 'required|integer|min:1',
            'capital_price' => 'required|integer|min:0',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $item = Items::find($request->id_item);

        // Update quantity item
        $item->quantity += $request->quantity;
        $item->capital_price = $request->capital_price; // Update harga modal
        $item->save();

        // Create stock in record
        ItemStockIn::create([
            'id_stock_in' => $this->generateIdStockIn(),
            'id_item' => $request->id_item,
            'quantity' => $request->quantity,
            'capital_price' => $request->capital_price,
            'keterangan' => $request->keterangan,
            'tanggal' => $request->tanggal,
        ]);

        return redirect()->back()->with('success', 'Data barang masuk berhasil ditambahkan!');
    }

    public function update(Request $request, $id_stock_in)
    {
        $stockIn = ItemStockIn::findOrFail($id_stock_in);
        $item = Items::find($stockIn->id_item);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'capital_price' => 'required|integer|min:0',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:500',
        ]);

        // Calculate difference
        $qtyDifference = $request->quantity - $stockIn->quantity;
        $item->quantity += $qtyDifference;
        $item->capital_price = $request->capital_price;
        $item->save();

        $stockIn->update([
            'quantity' => $request->quantity,
            'capital_price' => $request->capital_price,
            'keterangan' => $request->keterangan,
            'tanggal' => $request->tanggal,
        ]);

        return redirect()->back()->with('success', 'Data barang masuk berhasil diupdate!');
    }

    public function destroy($id_stock_in)
    {
        $stockIn = ItemStockIn::findOrFail($id_stock_in);
        $item = Items::find($stockIn->id_item);

        // Reduce item quantity
        $item->quantity -= $stockIn->quantity;
        if ($item->quantity < 0) {
            $item->quantity = 0;
        }
        $item->save();

        $stockIn->delete();

        return redirect()->back()->with('success', 'Data barang masuk berhasil dihapus!');
    }

    public function exportPdf(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        $stockIns = ItemStockIn::query()
            ->with('item')
            ->when($month, function ($query, $month) {
                $query->whereMonth('tanggal', $month);
            })
            ->when($year, function ($query, $year) {
                $query->whereYear('tanggal', $year);
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('id_stock_in', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.inventory.stock-in-pdf', compact('stockIns'));
        return $pdf->download('barang-masuk-' . date('Y-m-d-His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        return Excel::download(
            new \App\Exports\Inventory\StockInExport($month, $year),
            'barang-masuk-' . date('Y-m-d-His') . '.xlsx'
        );
    }
}
