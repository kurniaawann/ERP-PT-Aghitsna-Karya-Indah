<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\InputNormalizer;
use App\Services\StockService;

class ItemStockInController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}

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
        $stockIns = $this->baseQuery($request)->paginate(15);

        $items = Items::orderBy('id_item', 'asc')->get();

        return view('pages.inventory.stock-in', compact('stockIns', 'items'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|json',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $items = json_decode($request->items, true);

        if (empty($items) || !is_array($items)) {
            return redirect()->back()->with('error', 'Minimal harus ada satu item barang masuk!');
        }

        DB::beginTransaction();
        try {
            foreach ($items as $itemData) {
                if (empty($itemData['name_item']) || empty($itemData['quantity']) || $itemData['quantity'] < 1) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'Semua item harus memiliki nama dan quantity minimal 1!');
                }

                $idItem = $itemData['id_item'] ?? null;
                $quantity = (int) $itemData['quantity'];
                $capitalPrice = InputNormalizer::normalizeCurrency($itemData['capital_price'] ?? 0);
                $fromStock = $itemData['from_stock'] ?? false;

                if ($fromStock && $idItem) {
                    $item = Items::lockForUpdate()->find($idItem);
                    if (!$item) {
                        DB::rollBack();
                        return redirect()->back()->with('error', 'Barang dengan ID ' . $idItem . ' tidak ditemukan!');
                    }
                } else {
                    $item = $idItem ? Items::lockForUpdate()->find($idItem) : null;

                    if (!$item) {
                        $item = Items::create([
                            'id_item' => Items::generateNextId(),
                            'name_item' => $itemData['name_item'],
                            'quantity' => 0,
                            'capital_price' => $capitalPrice,
                            'selling_price' => 0,
                        ]);
                    }
                }

                $existingValue = $item->quantity * $item->capital_price;
                $newValue = $quantity * $capitalPrice;
                $totalQuantity = $item->quantity + $quantity;
                $newAveragePrice = $totalQuantity > 0 ? (int) round(($existingValue + $newValue) / $totalQuantity) : $capitalPrice;

                $item->quantity += $quantity;
                $item->capital_price = $newAveragePrice;
                $item->save();

                ItemStockIn::create([
                    'id_stock_in' => $this->generateIdStockIn(),
                    'id_item' => $item->id_item,
                    'quantity' => $quantity,
                    'capital_price' => $capitalPrice,
                    'notes' => $request->notes,
                    'date' => $request->date,
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Data barang masuk berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock In store failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
        }
    }

    private function baseQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');

        return ItemStockIn::query()
            ->with('item')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_stock_in', 'like', "%{$search}%")
                        ->orWhere('id_item', 'like', "%{$search}%")
                        ->orWhereHas('item', function ($sub) use ($search) {
                            $sub->where('name_item', 'like', "%{$search}%");
                        });
                });
            })
            ->when($month, function ($query, $month) {
                $query->whereMonth('date', $month);
            })
            ->when($year, function ($query, $year) {
                $query->whereYear('date', $year);
            })
            ->orderBy('date', 'desc')
            ->orderBy('id_stock_in', 'desc');
    }

    public function update(Request $request, string $id_stock_in)
    {
        $validated = $request->validate([
            'items' => 'required|json',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $newItems = json_decode($request->items, true);

        if (empty($newItems) || !is_array($newItems)) {
            return redirect()->back()->with('error', 'Minimal harus ada satu item barang masuk!');
        }

        $itemData = $newItems[0] ?? null;

        if (!$itemData) {
            return redirect()->back()->with('error', 'Data item tidak valid!');
        }

        $newQuantity = (int) $itemData['quantity'];
        $newCapitalPrice = InputNormalizer::normalizeCurrency($itemData['capital_price'] ?? 0);
        $newItemId = $itemData['id_item'] ?? null;
        $newItemName = $itemData['name_item'] ?? null;

        DB::beginTransaction();
        try {
            $stockIn = ItemStockIn::lockForUpdate()->findOrFail($id_stock_in);
            $oldItem = Items::lockForUpdate()->find($stockIn->id_item);

            if ($newItemId && $newItemId === $stockIn->id_item) {
                $qtyDifference = $newQuantity - $stockIn->quantity;

                $oldValue = $stockIn->quantity * $stockIn->capital_price;
                $currentItemValue = $oldItem->quantity * $oldItem->capital_price;
                $itemValueWithoutThisStockIn = $currentItemValue - $oldValue;

                $newValue = $newQuantity * $newCapitalPrice;
                $totalQuantity = ($oldItem->quantity - $stockIn->quantity) + $newQuantity;
                $newAveragePrice = $totalQuantity > 0 ? (int) round(($itemValueWithoutThisStockIn + $newValue) / $totalQuantity) : $newCapitalPrice;

                $oldItem->quantity += $qtyDifference;
                $oldItem->capital_price = $newAveragePrice;
                $oldItem->save();
            } else {
                $oldValue = $stockIn->quantity * $stockIn->capital_price;
                $currentItemValue = $oldItem->quantity * $oldItem->capital_price;
                $itemValueWithoutThisStockIn = $currentItemValue - $oldValue;

                $oldItem->quantity -= $stockIn->quantity;
                if ($oldItem->quantity < 0) {
                    $oldItem->quantity = 0;
                }

                if ($oldItem->quantity > 0) {
                    $oldItem->capital_price = (int) round($itemValueWithoutThisStockIn / $oldItem->quantity);
                } else {
                    $oldItem->capital_price = 0;
                }
                $oldItem->save();

                $newItem = null;

                if ($newItemId) {
                    $newItem = Items::lockForUpdate()->find($newItemId);
                    if (!$newItem) {
                        DB::rollBack();
                        return redirect()->back()->with('error', 'Barang dengan ID ' . $newItemId . ' tidak ditemukan!');
                    }
                } else if ($newItemName) {
                    $newItem = Items::where('name_item', $newItemName)->first();

                    if (!$newItem) {
                        $newItem = Items::create([
                            'id_item' => Items::generateNextId(),
                            'name_item' => $newItemName,
                            'quantity' => 0,
                            'capital_price' => $newCapitalPrice,
                            'selling_price' => 0,
                        ]);
                    }
                }

                if (!$newItem) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'Gagal menentukan item untuk diupdate!');
                }

                $existingValue = $newItem->quantity * $newItem->capital_price;
                $newValue = $newQuantity * $newCapitalPrice;
                $totalQuantity = $newItem->quantity + $newQuantity;
                $newAveragePrice = $totalQuantity > 0 ? (int) round(($existingValue + $newValue) / $totalQuantity) : $newCapitalPrice;

                $newItem->quantity += $newQuantity;
                $newItem->capital_price = $newAveragePrice;
                $newItem->save();

                $stockIn->id_item = $newItem->id_item;
            }

            $stockIn->update([
                'quantity' => $newQuantity,
                'capital_price' => $newCapitalPrice,
                'notes' => $request->notes,
                'date' => $request->date,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Data barang masuk berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock In update failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengupdate data. Silakan coba lagi.');
        }
    }

    public function destroy(string $id_stock_in)
    {
        $stockIn = ItemStockIn::findOrFail($id_stock_in);

        $this->stockService->processStockInDeletion($stockIn);

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
            $this->stockService->processStockInDeletion($stockIn);
        }

        return redirect()->back()->with('success', 'Data terpilih berhasil dihapus.');
    }

    public function exportPdf(Request $request)
    {
        $stockIns = $this->baseQuery($request)->get();

        $pdf = Pdf::loadView('exports.inventory.stock-in-pdf', compact('stockIns'));
        return $pdf->download('Barang_Masuk_' . date('Y-m-d') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(
            new \App\Exports\Inventory\StockInExport(
                $request->input('search'),
                $request->input('month'),
                $request->input('year')
            ),
            'Barang_Masuk_' . date('Y-m-d') . '.xlsx'
        );
    }
}
