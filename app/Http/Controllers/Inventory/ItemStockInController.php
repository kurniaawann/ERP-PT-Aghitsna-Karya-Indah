<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreStockInRequest;
use App\Http\Requests\Inventory\UpdateStockInRequest;
use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockIn;
use App\Services\Inventory\StockInService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Inventory\StockInExport;

/**
 * Controller untuk mengelola Barang Masuk (Stock In).
 *
 * Controller ini hanya menangani request dan response HTTP.
 * Business logic (CRUD, perhitungan stok) didelegasikan ke StockInService.
 * Penghapusan stok menggunakan StockService (shared service).
 */
class ItemStockInController extends Controller
{
    public function __construct(
        private readonly StockInService $stockInService,
        private readonly StockService $stockService
    ) {}

    /**
     * Menampilkan daftar Barang Masuk dengan paginasi, pencarian, dan filter.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $stockIns = $this->baseQuery($request)->paginate(15);

        $items = Items::orderBy('id_item', 'asc')->get();

        return view('pages.inventory.stock-in', compact('stockIns', 'items'));
    }

    /**
     * Menyimpan Barang Masuk baru beserta penyesuaian stok.
     *
     * @param  StoreStockInRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreStockInRequest $request)
    {
        $items = json_decode($request->items, true);

        if (empty($items) || !is_array($items)) {
            return redirect()->back()->with('error', 'Minimal harus ada satu item barang masuk!');
        }

        try {
            $this->stockInService->store($items, $request->date, $request->notes);

            return redirect()->back()->with('success', 'Data barang masuk berhasil ditambahkan!');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Stock In store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
        }
    }

    /**
     * Memperbarui data Barang Masuk beserta penyesuaian stok.
     *
     * @param  UpdateStockInRequest  $request
     * @param  string                $id_stock_in
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateStockInRequest $request, string $id_stock_in)
    {
        $stockIn = ItemStockIn::find($id_stock_in);

        if (!$stockIn) {
            abort(404);
        }

        $newItems = json_decode($request->items, true);

        if (empty($newItems) || !is_array($newItems)) {
            return redirect()->back()->with('error', 'Minimal harus ada satu item barang masuk!');
        }

        $itemData = $newItems[0] ?? null;

        if (!$itemData) {
            return redirect()->back()->with('error', 'Data item tidak valid!');
        }

        try {
            $this->stockInService->update($stockIn, $itemData, $request->date, $request->notes);

            return redirect()->back()->with('success', 'Data barang masuk berhasil diupdate!');
        } catch (\Exception $e) {
            Log::error('Stock In update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengupdate data. Silakan coba lagi.');
        }
    }

    /**
     * Menghapus satu record Barang Masuk.
     *
     * @param  string  $id_stock_in
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $id_stock_in)
    {
        $stockIn = ItemStockIn::find($id_stock_in);

        if (!$stockIn) {
            abort(404);
        }

        try {
            $this->stockService->processStockInDeletion($stockIn);

            return redirect()->back()->with('success', 'Data barang masuk berhasil dihapus!');
        } catch (\Exception $e) {
            Log::error('Stock In delete failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
        }
    }

    /**
     * Menghapus beberapa record Barang Masuk sekaligus (bulk delete).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request)
    {
        $selectedIds = $request->input('selected_stock_ins', []);

        if (empty($selectedIds)) {
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        $stockIns = ItemStockIn::whereIn('id_stock_in', $selectedIds)->get();

        foreach ($stockIns as $stockIn) {
            $this->stockService->processStockInDeletion($stockIn);
        }

        return redirect()->back()->with('success', 'Data terpilih berhasil dihapus.');
    }

    /**
     * Export Barang Masuk ke format PDF.
     *
     * @param  Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf(Request $request)
    {
        $stockIns = $this->baseQuery($request)->get();

        $pdf = Pdf::loadView('exports.inventory.stock-in-pdf', compact('stockIns'));

        return $pdf->download('Barang_Masuk_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Barang Masuk ke format Excel.
     *
     * @param  Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        return Excel::download(
            new StockInExport(
                $request->input('search'),
                $request->input('month'),
                $request->input('year')
            ),
            'Barang_Masuk_' . date('Y-m-d') . '.xlsx'
        );
    }

    // ─── Private Helper Methods ────────────────────────────────────────

    /**
     * Membangun query dasar untuk daftar Barang Masuk.
     *
     * Menggunakan scope pada Model untuk pencarian dan filter.
     *
     * @param  Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function baseQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        return ItemStockIn::query()
            ->with('item')
            ->search($request->input('search'))
            ->filterMonth($request->input('month'))
            ->filterYear($request->input('year'))
            ->orderBy('date', 'desc')
            ->orderBy('id_stock_in', 'desc');
    }
}
