<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Exports\Inventory\StockOutExport;
use App\Models\Inventory\ItemStockOut;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk halaman Barang Keluar (read-only).
 *
 * Menangani:
 * - Menampilkan daftar barang keluar dengan filter & pencarian
 * - Export PDF
 * - Export Excel
 */
class ItemStockOutController extends Controller
{
    /**
     * Membangun query dasar untuk daftar barang keluar.
     *
     * Query ini digunakan oleh index, exportPdf, dan exportExcel
     * untuk memastikan konsistensi data yang ditampilkan.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function baseQuery(Request $request): Builder
    {
        return ItemStockOut::query()
            ->with(['item', 'salesRecap', 'returns'])
            ->search($request->input('search'))
            ->filterMonth($request->input('month'))
            ->filterYear($request->input('year'))
            ->orderBy('date', 'desc')
            ->orderBy('id_stock_out', 'desc');
    }

    /**
     * Menampilkan halaman daftar barang keluar.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $stockOuts = $this->baseQuery($request)->paginate(15);

        return view('pages.inventory.stock-out', compact('stockOuts'));
    }

    /**
     * Mengunduh laporan barang keluar dalam format PDF.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf(Request $request)
    {
        $stockOuts = $this->baseQuery($request)->get();

        $pdf = Pdf::loadView('exports.inventory.stock-out-pdf', compact('stockOuts'));
        return $pdf->download('Barang_Keluar_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Mengunduh laporan barang keluar dalam format Excel.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        return Excel::download(
            new StockOutExport(
                $request->input('search'),
                $request->input('month'),
                $request->input('year')
            ),
            'Barang_Keluar_' . date('Y-m-d') . '.xlsx'
        );
    }
}
