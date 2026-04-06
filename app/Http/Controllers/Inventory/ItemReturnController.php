<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockOut;
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
        $month = $request->input('month');
        $year = $request->input('year');

        $returns = ItemReturn::query()
            ->with(['item', 'stockOut'])
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

        return view('pages.inventory.item-return', compact('returns', 'items', 'stockOuts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_item' => 'required|exists:items,id_item',
            'quantity' => 'required|integer|min:1',
            'alasan' => 'nullable|string|max:255',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $item = Items::lockForUpdate()->find($request->id_item);

            // Create return record
            ItemReturn::create([
                'id_return' => $this->generateIdReturn(),
                'id_item' => $request->id_item,
                'id_stock_out' => $request->input('id_stock_out'),
                'quantity' => $request->quantity,
                'alasan' => $request->alasan,
                'keterangan' => $request->keterangan,
                'tanggal' => $request->tanggal,
            ]);

            // Restore item quantity (barang kembali ke stock)
            $item->quantity += $request->quantity;
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

            // Calculate difference
            $qtyDifference = $request->quantity - $return->quantity;

            // Update item quantity
            $item->quantity += $qtyDifference;
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

            // Reduce item quantity (return dihapus, jadi qty tidak dikembalikan)
            $item->quantity -= $return->quantity;
            if ($item->quantity < 0) {
                $item->quantity = 0;
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
        $month = $request->input('month');
        $year = $request->input('year');

        $returns = ItemReturn::query()
            ->with('item')
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
        $month = $request->input('month');
        $year = $request->input('year');

        return Excel::download(
            new \App\Exports\Inventory\ItemReturnExport($month, $year),
            'return-barang-' . date('Y-m-d-His') . '.xlsx'
        );
    }
}
