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
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');

        $stockIns = ItemStockIn::query()
            ->with('item')
            ->when($search, function ($query, $search) {
                $query->where('id_stock_in', 'like', "%{$search}%")
                    ->orWhere('id_item', 'like', "%{$search}%")
                    ->orWhereHas('item', function ($q) use ($search) {
                        $q->where('name_item', 'like', "%{$search}%");
                    });
            })
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

        // Calculate Weighted Average Cost
        $existingValue = $item->quantity * $item->capital_price;
        $newValue = $request->quantity * $request->capital_price;
        $totalQuantity = $item->quantity + $request->quantity;
        $newAveragePrice = $totalQuantity > 0 ? (int) round(($existingValue + $newValue) / $totalQuantity) : $request->capital_price;

        // Update quantity dan modal dengan weighted average
        $item->quantity += $request->quantity;
        $item->capital_price = $newAveragePrice;
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

        // Reverse the effect of previous stock-in
        $oldValue = $stockIn->quantity * $stockIn->capital_price;
        $currentItemValue = $item->quantity * $item->capital_price;
        $itemValueWithoutThisStockIn = $currentItemValue - $oldValue;

        // Calculate new average with updated values
        $newValue = $request->quantity * $request->capital_price;
        $totalQuantity = ($item->quantity - $stockIn->quantity) + $request->quantity;
        $newAveragePrice = $totalQuantity > 0 ? (int) round(($itemValueWithoutThisStockIn + $newValue) / $totalQuantity) : $request->capital_price;

        // Update item quantity and price
        $qtyDifference = $request->quantity - $stockIn->quantity;
        $item->quantity += $qtyDifference;
        $item->capital_price = $newAveragePrice;
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

        // Calculate remaining value after removing this stock-in
        $currentItemValue = $item->quantity * $item->capital_price;
        $removedValue = $stockIn->quantity * $stockIn->capital_price;
        $remainingValue = $currentItemValue - $removedValue;

        // Reduce item quantity
        $item->quantity -= $stockIn->quantity;
        if ($item->quantity < 0) {
            $item->quantity = 0;
        }

        // Recalculate average price based on remaining stock
        if ($item->quantity > 0) {
            $item->capital_price = (int) round($remainingValue / $item->quantity);
        }

        $item->save();

        $stockIn->delete();

        return redirect()->back()->with('success', 'Data barang masuk berhasil dihapus!');
    }

    public function destroySelected(Request $request)
    {
        $selectedIds = $request->input('selected_stock_ins', []);

        if (empty($selectedIds)) {
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        // Get all stock-in records to delete and update their items
        $stockIns = ItemStockIn::whereIn('id_stock_in', $selectedIds)->get();

        foreach ($stockIns as $stockIn) {
            $item = Items::find($stockIn->id_item);
            if ($item) {
                // Calculate remaining value after removing this stock-in
                $currentItemValue = $item->quantity * $item->capital_price;
                $removedValue = $stockIn->quantity * $stockIn->capital_price;
                $remainingValue = $currentItemValue - $removedValue;

                // Reduce item quantity
                $item->quantity -= $stockIn->quantity;
                if ($item->quantity < 0) {
                    $item->quantity = 0;
                }

                // Recalculate average price based on remaining stock
                if ($item->quantity > 0) {
                    $item->capital_price = (int) round($remainingValue / $item->quantity);
                }

                $item->save();
            }
        }

        // Delete all selected stock-in records
        ItemStockIn::whereIn('id_stock_in', $selectedIds)->delete();

        return redirect()->back()->with('success', 'Data terpilih berhasil dihapus.');
    }

    public function exportPdf(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');

        $stockIns = ItemStockIn::query()
            ->with('item')
            ->when($search, function ($query, $search) {
                $query->where('id_stock_in', 'like', "%{$search}%")
                    ->orWhere('id_item', 'like', "%{$search}%")
                    ->orWhereHas('item', function ($q) use ($search) {
                        $q->where('name_item', 'like', "%{$search}%");
                    });
            })
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
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');

        return Excel::download(
            new \App\Exports\Inventory\StockInExport($search, $month, $year),
            'barang-masuk-' . date('Y-m-d-His') . '.xlsx'
        );
    }
}
