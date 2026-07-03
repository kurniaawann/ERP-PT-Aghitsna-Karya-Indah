<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ItemStockOut;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ItemStockOutController extends Controller
{
    private function baseQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');

        return ItemStockOut::query()
            ->with(['item', 'salesRecap', 'returns'])
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
            ->orderBy('id_stock_out', 'desc');
    }

    public function index(Request $request)
    {
        $stockOuts = $this->baseQuery($request)->paginate(15);

        return view('pages.inventory.stock-out', compact('stockOuts'));
    }

    public function exportPdf(Request $request)
    {
        $stockOuts = $this->baseQuery($request)->get();

        $pdf = Pdf::loadView('exports.inventory.stock-out-pdf', compact('stockOuts'));
        return $pdf->download('Barang_Keluar_' . date('Y-m-d') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');

        return Excel::download(
            new \App\Exports\Inventory\StockOutExport($search, $month, $year),
            'Barang_Keluar_' . date('Y-m-d') . '.xlsx'
        );
    }
}
