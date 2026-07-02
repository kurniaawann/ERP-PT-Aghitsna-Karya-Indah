<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockOut;
use App\Models\Inventory\ItemStockIn;
use App\Models\Inventory\ItemReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\StockService;

class ItemReturnController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}
    private function generateIdReturn()
    {
        $lastRecord = ItemReturn::orderBy('id_return', 'desc')->first();

        if (!$lastRecord) {
            return 'RTN-' . date('Ymd') . '-0001';
        }

        $lastNumber = (int) substr($lastRecord->id_return, -4);
        return 'RTN-' . date('Ymd') . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    private function baseQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');
        $returnType = $request->input('return_type');

        return ItemReturn::query()
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
            ->orderBy('id_return', 'desc');
    }

    public function index(Request $request)
    {
        $returns = $this->baseQuery($request)->paginate(15);

        $items = Items::orderBy('id_item', 'asc')->get();
        $stockOuts = ItemStockOut::orderBy('id_stock_out', 'desc')->get();
        $stockIns = ItemStockIn::orderBy('id_stock_in', 'desc')->get();

        return view('pages.inventory.item-return', compact('returns', 'items', 'stockOuts', 'stockIns'));
    }

    public function store(Request $request)
    {
        $returnType = $request->input('return_type', 'keluar');

        $rules = [
            'id_item' => 'required|exists:items,id_item',
            'quantity' => 'required|integer|min:1',
            'alasan' => 'nullable|string|max:255',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:500',
        ];

        if ($returnType === 'masuk') {
            $rules['id_stock_in'] = 'required|exists:item_stock_ins,id_stock_in';
        } else {
            $rules['id_stock_out'] = 'required|exists:item_stock_outs,id_stock_out';
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            $item = Items::lockForUpdate()->findOrFail($request->id_item);

            if ($returnType === 'masuk') {
                $stockIn = ItemStockIn::lockForUpdate()->findOrFail($request->id_stock_in);

                $totalReturned = ItemReturn::where('id_stock_in', $request->id_stock_in)
                    ->where('return_type', 'masuk')
                    ->lockForUpdate()
                    ->sum('quantity');

                if ($totalReturned + $request->quantity > $stockIn->quantity) {
                    DB::rollBack();
                    return back()->withErrors([
                        'quantity' => "Jumlah return melebihi jumlah barang masuk! (Tersedia: " . ($stockIn->quantity - $totalReturned) . ")"
                    ])->withInput();
                }

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

                $item->quantity -= $request->quantity;
                if ($item->quantity < 0) {
                    $item->quantity = 0;
                }

                $stockIn->quantity -= $request->quantity;
                if ($stockIn->quantity < 0) {
                    $stockIn->quantity = 0;
                }

                $currentItemValue = (($item->quantity + $request->quantity) * $item->capital_price);
                $returnedValue = $request->quantity * $stockIn->capital_price;
                $newValue = $currentItemValue - $returnedValue;

                $item->capital_price = $item->quantity > 0 ? (int) round($newValue / $item->quantity) : 0;
                $stockIn->save();
            } else {
                $stockOut = ItemStockOut::lockForUpdate()->findOrFail($request->id_stock_out);

                $totalReturned = ItemReturn::where('id_stock_out', $request->id_stock_out)
                    ->where('return_type', 'keluar')
                    ->lockForUpdate()
                    ->sum('quantity');

                if ($totalReturned + $request->quantity > $stockOut->quantity) {
                    DB::rollBack();
                    return back()->withErrors([
                        'quantity' => "Jumlah return melebihi jumlah barang keluar! (Tersedia: " . ($stockOut->quantity - $totalReturned) . ")"
                    ])->withInput();
                }

                ItemReturn::create([
                    'id_return' => $this->generateIdReturn(),
                    'id_item' => $request->id_item,
                    'id_stock_out' => $request->id_stock_out,
                    'quantity' => $request->quantity,
                    'alasan' => $request->alasan,
                    'keterangan' => $request->keterangan,
                    'return_type' => 'keluar',
                    'tanggal' => $request->tanggal,
                ]);

                $item->quantity += $request->quantity;

                $stockOut->quantity -= $request->quantity;
                if ($stockOut->quantity < 0) {
                    $stockOut->quantity = 0;
                }
                $stockOut->save();
            }

            $item->save();

            DB::commit();
            return redirect()->route('item-return.index')->with('success', 'Data return barang berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Item Return store failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors([
                'error' => 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.'
            ])->withInput();
        }
    }

    public function update(Request $request, string $id_return)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'alasan' => 'nullable|string|max:255',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $return = ItemReturn::lockForUpdate()->findOrFail($id_return);
            $item = Items::lockForUpdate()->findOrFail($return->id_item);

            if ($return->return_type === 'masuk') {
                $stockIn = ItemStockIn::lockForUpdate()->findOrFail($return->id_stock_in);

                $otherReturns = ItemReturn::where('id_stock_in', $return->id_stock_in)
                    ->where('id_return', '!=', $id_return)
                    ->where('return_type', 'masuk')
                    ->lockForUpdate()
                    ->sum('quantity');

                if ($otherReturns + $request->quantity > $stockIn->quantity) {
                    DB::rollBack();
                    return back()->withErrors([
                        'quantity' => "Jumlah return melebihi jumlah barang masuk! (Tersedia: " . ($stockIn->quantity - $otherReturns) . ")"
                    ])->withInput();
                }

                $qtyDifference = $request->quantity - $return->quantity;

                $item->quantity -= $qtyDifference;
                if ($item->quantity < 0) {
                    $item->quantity = 0;
                }

                $stockIn->quantity -= $qtyDifference;
                if ($stockIn->quantity < 0) {
                    $stockIn->quantity = 0;
                }

                $currentItemValue = (($item->quantity + $qtyDifference) * $item->capital_price);
                $adjustedValue = $qtyDifference * $stockIn->capital_price;
                $newValue = $currentItemValue - $adjustedValue;

                $item->capital_price = $item->quantity > 0 ? (int) round($newValue / $item->quantity) : 0;
                $stockIn->save();
            } else {
                $stockOut = ItemStockOut::lockForUpdate()->findOrFail($return->id_stock_out);

                $otherReturns = ItemReturn::where('id_stock_out', $return->id_stock_out)
                    ->where('id_return', '!=', $id_return)
                    ->where('return_type', 'keluar')
                    ->lockForUpdate()
                    ->sum('quantity');

                if ($otherReturns + $request->quantity > $stockOut->quantity) {
                    DB::rollBack();
                    return back()->withErrors([
                        'quantity' => "Jumlah return melebihi jumlah barang keluar! (Tersedia: " . ($stockOut->quantity - $otherReturns) . ")"
                    ])->withInput();
                }

                $qtyDifference = $request->quantity - $return->quantity;
                $item->quantity += $qtyDifference;

                $stockOut->quantity -= $qtyDifference;
                if ($stockOut->quantity < 0) {
                    $stockOut->quantity = 0;
                }
                $stockOut->save();
            }

            $item->save();

            $return->update([
                'quantity' => $request->quantity,
                'alasan' => $request->alasan,
                'keterangan' => $request->keterangan,
                'tanggal' => $request->tanggal,
            ]);

            DB::commit();
            return redirect()->route('item-return.index')->with('success', 'Data return barang berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Item Return update failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors([
                'error' => 'Terjadi kesalahan saat mengupdate data. Silakan coba lagi.'
            ])->withInput();
        }
    }

    public function destroy(string $id_return)
    {
        DB::beginTransaction();
        try {
            $return = ItemReturn::findOrFail($id_return);

            $this->stockService->processReturnDeletion($return);

            DB::commit();
            return redirect()->back()->with('success', 'Data return barang berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Item Return destroy failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
        }
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('selected_returns', []);

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Pilih minimal satu data untuk dihapus');
        }

        DB::beginTransaction();
        try {
            $returns = ItemReturn::whereIn('id_return', $ids)->get();

            foreach ($returns as $return) {
                $this->stockService->processReturnDeletion($return);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Berhasil menghapus ' . count($returns) . ' data return barang!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Item Return bulkDelete failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
        }
    }

    public function exportPdf(Request $request)
    {
        $returns = $this->baseQuery($request)->get();

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
