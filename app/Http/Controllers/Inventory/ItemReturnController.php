<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreItemReturnRequest;
use App\Http\Requests\Inventory\UpdateItemReturnRequest;
use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockOut;
use App\Models\Inventory\ItemStockIn;
use App\Models\Inventory\ItemReturn;
use App\Services\Inventory\ItemReturnService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk halaman Pengembalian Barang (Item Return).
 *
 * Menangani:
 * - CRUD pengembalian barang (tipe masuk & keluar)
 * - Export PDF & Excel
 * - Penyesuaian stok dan capital price
 */
class ItemReturnController extends Controller
{
    public function __construct(
        private ItemReturnService $returnService,
        private StockService $stockService
    ) {}

    /**
     * Membangun query dasar untuk daftar pengembalian barang.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function baseQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        return ItemReturn::query()
            ->with(['item', 'stockOut', 'stockIn'])
            ->search($request->input('search'))
            ->filterReturnType($request->input('return_type'))
            ->filterMonth($request->input('month'))
            ->filterYear($request->input('year'))
            ->orderBy('date', 'desc')
            ->orderBy('id_return', 'desc');
    }

    /**
     * Menampilkan halaman daftar pengembalian barang.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $returns = $this->baseQuery($request)->paginate(15);

        $items = Items::orderBy('id_item', 'asc')->get();
        $stockOuts = ItemStockOut::orderBy('id_stock_out', 'desc')->get();
        $stockIns = ItemStockIn::orderBy('id_stock_in', 'desc')->get();

        $maxQuantities = $this->computeMaxQuantities($returns, $stockIns, $stockOuts);

        return view('pages.inventory.item-return', compact('returns', 'items', 'stockOuts', 'stockIns', 'maxQuantities'));
    }

    /**
     * Menghitung max quantity yang bisa di-return untuk setiap record.
     *
     * Menghindari N+1 query dengan pre-computing di controller.
     *
     * @param  \Illuminate\Database\Eloquent\Collection $returns
     * @param  \Illuminate\Database\Eloquent\Collection $stockIns
     * @param  \Illuminate\Database\Eloquent\Collection $stockOuts
     * @return array<string, int>
     */
    private function computeMaxQuantities($returns, $stockIns, $stockOuts): array
    {
        $maxQuantities = [];

        foreach ($returns as $record) {
            if ($record->return_type === 'masuk') {
                $stockIn = $stockIns->firstWhere('id_stock_in', $record->id_stock_in);
                $maxQuantities[$record->id_return] = $stockIn ? $stockIn->quantity + $record->quantity : 0;
            } else {
                $stockOut = $stockOuts->firstWhere('id_stock_out', $record->id_stock_out);
                $maxQuantities[$record->id_return] = $stockOut ? $stockOut->quantity + $record->quantity : 0;
            }
        }

        return $maxQuantities;
    }

    /**
     * Menyimpan pengembalian baru.
     *
     * @param  \App\Http\Requests\Inventory\StoreItemReturnRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreItemReturnRequest $request)
    {
        try {
            $this->returnService->createReturn($request->validated());

            return redirect()->route('item-return.index')->with('success', 'Data return barang berhasil ditambahkan!');
        } catch (\RuntimeException $e) {
            return back()->withErrors([
                'quantity' => $e->getMessage(),
            ])->withInput();
        } catch (\Exception $e) {
            Log::error('Item Return store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors([
                'error' => 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.',
            ])->withInput();
        }
    }

    /**
     * Memperbarui pengembalian yang sudah ada.
     *
     * @param  \App\Http\Requests\Inventory\UpdateItemReturnRequest $request
     * @param  string $id_return
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateItemReturnRequest $request, string $id_return)
    {
        try {
            $return = ItemReturn::findOrFail($id_return);
            $this->returnService->updateReturn($return, $request->validated());

            return redirect()->route('item-return.index')->with('success', 'Data return barang berhasil diupdate!');
        } catch (\RuntimeException $e) {
            return back()->withErrors([
                'quantity' => $e->getMessage(),
            ])->withInput();
        } catch (\Exception $e) {
            Log::error('Item Return update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors([
                'error' => 'Terjadi kesalahan saat mengupdate data. Silakan coba lagi.',
            ])->withInput();
        }
    }

    /**
     * Menghapus satu record pengembalian.
     *
     * @param  string $id_return
     * @return \Illuminate\Http\RedirectResponse
     */
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
            Log::error('Item Return destroy failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
        }
    }

    /**
     * Menghapus beberapa record pengembalian sekaligus.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
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
            Log::error('Item Return bulkDelete failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
        }
    }

    /**
     * Mengunduh laporan pengembalian barang dalam format PDF.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf(Request $request)
    {
        $returns = $this->baseQuery($request)->get();

        $pdf = Pdf::loadView('exports.inventory.item-return-pdf', compact('returns'));
        return $pdf->download('Retur_Barang_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Mengunduh laporan pengembalian barang dalam format Excel.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        return Excel::download(
            new \App\Exports\Inventory\ItemReturnExport(
                $request->input('search'),
                $request->input('month'),
                $request->input('year'),
                $request->input('return_type')
            ),
            'Retur_Barang_' . date('Y-m-d') . '.xlsx'
        );
    }
}
