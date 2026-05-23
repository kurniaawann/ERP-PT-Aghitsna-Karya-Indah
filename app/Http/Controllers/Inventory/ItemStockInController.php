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

    private function generateIdItem()
    {
        // Ambil item terakhir dari database
        $lastItem = Items::orderBy('id_item', 'desc')->first();

        // Jika belum ada data, mulai dari ITM-0001
        if (!$lastItem) {
            return 'ITM-0001';
        }

        // Extract nomor dari id_item terakhir
        $lastNumber = (int) substr($lastItem->id_item, 4);

        // Tambah 1 dan format dengan padding 0
        return 'ITM-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
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
            'items' => 'required|json',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $items = json_decode($request->items, true);

        if (empty($items) || !is_array($items)) {
            return redirect()->back()->with('error', 'Minimal harus ada satu item barang masuk!');
        }

        foreach ($items as $itemData) {
            // Validate each item
            if (empty($itemData['name_item']) || empty($itemData['quantity']) || $itemData['quantity'] < 1) {
                return redirect()->back()->with('error', 'Semua item harus memiliki nama dan quantity minimal 1!');
            }

            $idItem = $itemData['id_item'];
            $quantity = (int) $itemData['quantity'];
            $capitalPrice = $this->normalizeCurrencyInput($itemData['capital_price'] ?? 0);
            $fromStock = $itemData['from_stock'] ?? false;

            // Handle "dari stok" - ambil dari data barang yang ada
            if ($fromStock && $idItem) {
                $item = Items::find($idItem);
                if (!$item) {
                    return redirect()->back()->with('error', 'Barang dengan ID ' . $idItem . ' tidak ditemukan!');
                }
            } else {
                // Handle barang baru/manual input
                $item = Items::find($idItem);

                if (!$item) {
                    // Create new item jika belum ada
                    $item = Items::create([
                        'id_item' => $this->generateIdItem(),
                        'name_item' => $itemData['name_item'],
                        'quantity' => 0,
                        'capital_price' => $capitalPrice,
                        'selling_price' => 0, // Will be set later
                    ]);
                }
            }

            // Calculate Weighted Average Cost
            $existingValue = $item->quantity * $item->capital_price;
            $newValue = $quantity * $capitalPrice;
            $totalQuantity = $item->quantity + $quantity;
            $newAveragePrice = $totalQuantity > 0 ? (int) round(($existingValue + $newValue) / $totalQuantity) : $capitalPrice;

            // Update quantity dan modal dengan weighted average
            $item->quantity += $quantity;
            $item->capital_price = $newAveragePrice;
            $item->save();

            // Create stock in record
            ItemStockIn::create([
                'id_stock_in' => $this->generateIdStockIn(),
                'id_item' => $item->id_item,
                'quantity' => $quantity,
                'capital_price' => $capitalPrice,
                'keterangan' => $request->keterangan,
                'tanggal' => $request->tanggal,
            ]);
        }

        return redirect()->back()->with('success', 'Data barang masuk berhasil ditambahkan!');
    }

    public function update(Request $request, $id_stock_in)
    {
        $stockIn = ItemStockIn::findOrFail($id_stock_in);
        $oldItem = Items::find($stockIn->id_item);

        $validated = $request->validate([
            'items' => 'required|json',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $newItems = json_decode($request->items, true);

        if (empty($newItems) || !is_array($newItems)) {
            return redirect()->back()->with('error', 'Minimal harus ada satu item barang masuk!');
        }

        // For now, handle single item update (first item in array)
        $itemData = $newItems[0] ?? null;

        if (!$itemData) {
            return redirect()->back()->with('error', 'Data item tidak valid!');
        }

        $newQuantity = (int) $itemData['quantity'];
        $newCapitalPrice = $this->normalizeCurrencyInput($itemData['capital_price'] ?? 0);
        $newItemId = $itemData['id_item'] ?? null;
        $newItemName = $itemData['name_item'] ?? null;

        // ===== CASE 1: Item ID tidak berubah (update pada item yang sama) =====
        if ($newItemId && $newItemId === $stockIn->id_item) {
            // Item tetap sama, hanya update quantity dan price
            $qtyDifference = $newQuantity - $stockIn->quantity;

            // Reverse the effect of previous stock-in
            $oldValue = $stockIn->quantity * $stockIn->capital_price;
            $currentItemValue = $oldItem->quantity * $oldItem->capital_price;
            $itemValueWithoutThisStockIn = $currentItemValue - $oldValue;

            // Calculate new average with updated values
            $newValue = $newQuantity * $newCapitalPrice;
            $totalQuantity = ($oldItem->quantity - $stockIn->quantity) + $newQuantity;
            $newAveragePrice = $totalQuantity > 0 ? (int) round(($itemValueWithoutThisStockIn + $newValue) / $totalQuantity) : $newCapitalPrice;

            // Update item quantity and price
            $oldItem->quantity += $qtyDifference;
            $oldItem->capital_price = $newAveragePrice;
            $oldItem->save();
        }
        // ===== CASE 2: Item ID berubah atau mode berubah dari "dari stock" =====
        else {
            // Step 1: Kembalikan stock/cost dari item lama
            $oldValue = $stockIn->quantity * $stockIn->capital_price;
            $currentItemValue = $oldItem->quantity * $oldItem->capital_price;
            $itemValueWithoutThisStockIn = $currentItemValue - $oldValue;

            $oldItem->quantity -= $stockIn->quantity;
            if ($oldItem->quantity < 0) {
                $oldItem->quantity = 0;
            }

            // Recalculate average price for old item
            if ($oldItem->quantity > 0) {
                $oldItem->capital_price = (int) round($itemValueWithoutThisStockIn / $oldItem->quantity);
            } else {
                $oldItem->capital_price = 0;
            }
            $oldItem->save();

            // Step 2: Tentukan item baru
            $newItem = null;

            if ($newItemId) {
                // User pilih dari stock
                $newItem = Items::find($newItemId);
                if (!$newItem) {
                    return redirect()->back()->with('error', 'Barang dengan ID ' . $newItemId . ' tidak ditemukan!');
                }
            } else if ($newItemName) {
                // User input manual - cari atau buat item baru
                $newItem = Items::where('name_item', $newItemName)->first();

                if (!$newItem) {
                    // Create new item
                    $newItem = Items::create([
                        'id_item' => $this->generateIdItem(),
                        'name_item' => $newItemName,
                        'quantity' => 0,
                        'capital_price' => $newCapitalPrice,
                        'selling_price' => 0,
                    ]);
                }
            }

            if (!$newItem) {
                return redirect()->back()->with('error', 'Gagal menentukan item untuk diupdate!');
            }

            // Step 3: Update stock/cost untuk item baru
            $existingValue = $newItem->quantity * $newItem->capital_price;
            $newValue = $newQuantity * $newCapitalPrice;
            $totalQuantity = $newItem->quantity + $newQuantity;
            $newAveragePrice = $totalQuantity > 0 ? (int) round(($existingValue + $newValue) / $totalQuantity) : $newCapitalPrice;

            $newItem->quantity += $newQuantity;
            $newItem->capital_price = $newAveragePrice;
            $newItem->save();

            // Step 4: Update stock in record dengan item baru
            $stockIn->id_item = $newItem->id_item;
        }

        $stockIn->update([
            'quantity' => $newQuantity,
            'capital_price' => $newCapitalPrice,
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

    private function normalizeCurrencyInput($value): int
    {
        return (int) preg_replace('/[^0-9]/', '', (string) $value);
    }
}
