<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockOut;
use App\Models\Inventory\ItemStockIn;
use App\Models\Inventory\ItemReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ItemReturnController extends Controller
{
    private function generateIdReturn()
    {
        $lastRecord = ItemReturn::orderBy('id_return', 'desc')->first();

        if (!$lastRecord) {
            return 'RTN-' . date('Ymd') . '-0001';
        }

        $lastNumber = (int) substr($lastRecord->id_return, -4);
        return 'RTN-' . date('Ymd') . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');
        $returnType = $request->input('return_type'); // Filter: masuk, keluar, atau all

        $returns = ItemReturn::query()
            ->with(['item', 'stockOut', 'stockIn'])
            ->when($search, function ($query, $search) {
                $query->where('id_return', 'like', "%{$search}%")
                    ->orWhere('id_item', 'like', "%{$search}%")
                    ->orWhereHas('item', function ($q) use ($search) {
                        $q->where('name_item', 'like', "%{$search}%");
                    });
            })
            ->when($returnType, function ($query, $returnType) {
                $query->where('return_type', $returnType);
            })
            ->when($month, function ($query, $month) {
                $query->whereMonth('tanggal', $month);
            })
            ->when($year, function ($query, $year) {
                $query->whereYear('tanggal', $year);
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('id_return', 'desc')
            ->paginate(10);

        $items = Items::orderBy('id_item', 'asc')->get();
        $stockOuts = ItemStockOut::orderBy('id_stock_out', 'desc')->get();
        $stockIns = ItemStockIn::orderBy('id_stock_in', 'desc')->get();

        return view('pages.inventory.item-return', compact('returns', 'items', 'stockOuts', 'stockIns'));
    }

    public function store(Request $request)
    {
        $returnType = $request->input('return_type', 'keluar'); // Default: keluar

        if ($returnType === 'masuk') {
            // Validasi untuk return barang masuk
            $validated = $request->validate([
                'id_item' => 'required|exists:items,id_item',
                'id_stock_in' => 'required|exists:item_stock_ins,id_stock_in',
                'quantity' => 'required|integer|min:1',
                'alasan' => 'nullable|string|max:255',
                'tanggal' => 'required|date',
                'keterangan' => 'nullable|string|max:500',
            ]);
        } else {
            // Validasi untuk return barang keluar
            $validated = $request->validate([
                'id_item' => 'required|exists:items,id_item',
                'id_stock_out' => 'required|exists:item_stock_outs,id_stock_out',
                'quantity' => 'required|integer|min:1',
                'alasan' => 'nullable|string|max:255',
                'tanggal' => 'required|date',
                'keterangan' => 'nullable|string|max:500',
            ]);
        }

        DB::beginTransaction();
        try {
            $item = Items::lockForUpdate()->find($request->id_item);

            if ($returnType === 'masuk') {
                $stockIn = ItemStockIn::find($request->id_stock_in);

                // Validate return quantity doesn't exceed original stock-in quantity
                $totalReturned = ItemReturn::where('id_stock_in', $request->id_stock_in)
                    ->where('return_type', 'masuk')
                    ->sum('quantity');
                if ($totalReturned + $request->quantity > $stockIn->quantity) {
                    return redirect()->back()->with('error', 'Jumlah return melebihi jumlah barang masuk original!')->withInput();
                }

                // Create return record for stock in
                ItemReturn::create([
                    'id_return' => $this->generateIdReturn(),
                    'id_item' => $request->id_item,
                    'id_stock_in' => $request->id_stock_in,
                    'quantity' => $request->quantity,
                    'alasan' => $request->alasan,
                    'keterangan' => $request->keterangan,
                    'return_type' => 'masuk',
                    'tanggal' => $request->tanggal,
                ]);

                // Reduce item quantity
                $item->quantity -= $request->quantity;
                if ($item->quantity < 0) {
                    $item->quantity = 0;
                }

                // Recalculate weighted average cost
                $currentItemValue = (($item->quantity + $request->quantity) * $item->capital_price);
                $returnedValue = $request->quantity * $stockIn->capital_price;
                $newValue = $currentItemValue - $returnedValue;

                if ($item->quantity > 0) {
                    $item->capital_price = (int) round($newValue / $item->quantity);
                } else {
                    $item->capital_price = 0;
                }
            } else {
                // Return barang keluar
                ItemReturn::create([
                    'id_return' => $this->generateIdReturn(),
                    'id_item' => $request->id_item,
                    'id_stock_out' => $request->input('id_stock_out'),
                    'quantity' => $request->quantity,
                    'alasan' => $request->alasan,
                    'keterangan' => $request->keterangan,
                    'return_type' => 'keluar',
                    'tanggal' => $request->tanggal,
                ]);

                // Restore item quantity (barang kembali ke stock)
                $item->quantity += $request->quantity;
            }

            $item->save();

            DB::commit();
            return redirect()->back()->with('success', 'Data return barang berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id_return)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'alasan' => 'nullable|string|max:255',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $return = ItemReturn::findOrFail($id_return);
            $item = Items::lockForUpdate()->find($return->id_item);

            $qtyDifference = $request->quantity - $return->quantity;

            if ($return->return_type === 'masuk') {
                // Handle return barang masuk
                $stockIn = ItemStockIn::find($return->id_stock_in);

                // Validate new return quantity
                $otherReturns = ItemReturn::where('id_stock_in', $return->id_stock_in)
                    ->where('id_return', '!=', $id_return)
                    ->where('return_type', 'masuk')
                    ->sum('quantity');
                if ($otherReturns + $request->quantity > $stockIn->quantity) {
                    return redirect()->back()->with('error', 'Jumlah return melebihi jumlah barang masuk original!')->withInput();
                }

                // Update item quantity
                $item->quantity -= $qtyDifference;
                if ($item->quantity < 0) {
                    $item->quantity = 0;
                }

                // Recalculate weighted average cost
                $currentItemValue = (($item->quantity + $qtyDifference) * $item->capital_price);
                $adjustedValue = $qtyDifference * $stockIn->capital_price;
                $newValue = $currentItemValue - $adjustedValue;

                if ($item->quantity > 0) {
                    $item->capital_price = (int) round($newValue / $item->quantity);
                } else {
                    $item->capital_price = 0;
                }
            } else {
                // Handle return barang keluar
                $item->quantity += $qtyDifference;
            }

            $item->save();

            $return->update([
                'quantity' => $request->quantity,
                'alasan' => $request->alasan,
                'keterangan' => $request->keterangan,
                'tanggal' => $request->tanggal,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Data return barang berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id_return)
    {
        DB::beginTransaction();
        try {
            $return = ItemReturn::findOrFail($id_return);
            $item = Items::lockForUpdate()->find($return->id_item);

            if ($return->return_type === 'masuk') {
                // Handle delete return barang masuk - restore item quantity
                $stockIn = ItemStockIn::find($return->id_stock_in);

                $item->quantity += $return->quantity;

                // Recalculate weighted average cost
                $currentItemValue = (($item->quantity - $return->quantity) * $item->capital_price);
                $restoredValue = $return->quantity * $stockIn->capital_price;
                $newValue = $currentItemValue + $restoredValue;
                $newQuantity = $item->quantity;

                if ($newQuantity > 0) {
                    $item->capital_price = (int) round($newValue / $newQuantity);
                } else {
                    $item->capital_price = 0;
                }
            } else {
                // Handle delete return barang keluar - reduce item quantity
                $item->quantity -= $return->quantity;
                if ($item->quantity < 0) {
                    $item->quantity = 0;
                }
            }

            $item->save();

            $return->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Data return barang berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function exportPdf(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');
        $returnType = $request->input('return_type');

        $returns = ItemReturn::query()
            ->with(['item', 'stockOut', 'stockIn'])
            ->when($search, function ($query, $search) {
                $query->where('id_return', 'like', "%{$search}%")
                    ->orWhere('id_item', 'like', "%{$search}%")
                    ->orWhereHas('item', function ($q) use ($search) {
                        $q->where('name_item', 'like', "%{$search}%");
                    });
            })
            ->when($returnType, function ($query, $returnType) {
                $query->where('return_type', $returnType);
            })
            ->when($month, function ($query, $month) {
                $query->whereMonth('tanggal', $month);
            })
            ->when($year, function ($query, $year) {
                $query->whereYear('tanggal', $year);
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('id_return', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.inventory.item-return-pdf', compact('returns'));
        return $pdf->download('return-barang-' . date('Y-m-d-His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');
        $returnType = $request->input('return_type');

        return Excel::download(
            new \App\Exports\Inventory\ItemReturnExport($search, $month, $year, $returnType),
            'return-barang-' . date('Y-m-d-His') . '.xlsx'
        );
    }
}
