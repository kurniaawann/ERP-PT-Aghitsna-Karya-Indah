<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockOut;
use App\Models\Report\SalesRecap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ItemStockOutController extends Controller
{
    private function generateIdStockOut()
    {
        $lastRecord = ItemStockOut::orderBy('id_stock_out', 'desc')->first();

        if (!$lastRecord) {
            return 'SOUT-' . date('Ymd') . '-0001';
        }

        $lastNumber = (int) substr($lastRecord->id_stock_out, -4);
        return 'SOUT-' . date('Ymd') . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');

        $stockOuts = ItemStockOut::query()
            ->with(['item', 'salesRecap'])
            ->when($search, function ($query, $search) {
                $query->where('id_stock_out', 'like', "%{$search}%")
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
            ->orderBy('id_stock_out', 'desc')
            ->paginate(10);

        // Get items with selling_price (untuk kategori Penjualan)
        $items = Items::orderBy('id_item', 'asc')->get();

        return view('pages.inventory.stock-out', compact('stockOuts', 'items'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_item' => 'required|exists:items,id_item',
            'quantity' => 'required|integer|min:1',
            'kategori' => 'required|in:Penjualan,Proyek,Transfer,Lainnya',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $item = Items::lockForUpdate()->find($request->id_item);

            // Validasi: stock tidak boleh minus
            if ($item->quantity < $request->quantity) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'Stock tidak cukup! Stock tersedia: ' . $item->quantity)
                    ->withInput();
            }

            // Create stock out record
            $stockOut = ItemStockOut::create([
                'id_stock_out' => $this->generateIdStockOut(),
                'id_item' => $request->id_item,
                'quantity' => $request->quantity,
                'kategori' => $request->kategori,
                'keterangan' => $request->keterangan,
                'tanggal' => $request->tanggal,
            ]);

            // Reduce item quantity
            $item->quantity -= $request->quantity;
            $item->save();

            DB::commit();
            return redirect()->back()->with('success', 'Data barang keluar berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id_stock_out)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'kategori' => 'required|in:Penjualan,Proyek,Transfer,Lainnya',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $stockOut = ItemStockOut::findOrFail($id_stock_out);
            $item = Items::lockForUpdate()->find($stockOut->id_item);

            // Calculate difference
            $qtyDifference = $request->quantity - $stockOut->quantity;

            // Validasi: stock tidak boleh minus setelah update
            if ($item->quantity < $qtyDifference) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'Stock tidak cukup untuk perubahan ini!')
                    ->withInput();
            }

            // Update item quantity
            $item->quantity -= $qtyDifference;
            $item->save();

            $stockOut->update([
                'quantity' => $request->quantity,
                'kategori' => $request->kategori,
                'keterangan' => $request->keterangan,
                'tanggal' => $request->tanggal,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Data barang keluar berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id_stock_out)
    {
        DB::beginTransaction();
        try {
            $stockOut = ItemStockOut::findOrFail($id_stock_out);
            $item = Items::lockForUpdate()->find($stockOut->id_item);

            // Restore item quantity
            $item->quantity += $stockOut->quantity;
            $item->save();

            $stockOut->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Data barang keluar berhasil dihapus!');
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

        $stockOuts = ItemStockOut::query()
            ->with(['item', 'salesRecap'])
            ->when($search, function ($query, $search) {
                $query->where('id_stock_out', 'like', "%{$search}%")
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
            ->orderBy('id_stock_out', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.inventory.stock-out-pdf', compact('stockOuts'));
        return $pdf->download('barang-keluar-' . date('Y-m-d-His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');

        return Excel::download(
            new \App\Exports\Inventory\StockOutExport($search, $month, $year),
            'barang-keluar-' . date('Y-m-d-His') . '.xlsx'
        );
    }
}
