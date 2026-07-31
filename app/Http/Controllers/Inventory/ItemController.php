<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreItemRequest;
use App\Http\Requests\Inventory\UpdateItemRequest;
use App\Services\Inventory\ItemService;
use App\Exports\Inventory\ItemsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk mengelola Data Barang.
 *
 * Controller ini hanya menangani request dan response HTTP.
 * Business logic didelegasikan ke ItemService.
 */
class ItemController extends Controller
{
    public function __construct(
        private readonly ItemService $itemService
    ) {}

    /**
     * Menampilkan daftar Data Barang dengan paginasi dan pencarian.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $items = $this->itemService->getPaginatedSearch($request->input('search'));

        return view('pages.inventory.item', compact('items'));
    }

    /**
     * Menyimpan Data Barang baru.
     *
     * @param  StoreItemRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreItemRequest $request)
    {
        $this->itemService->store($request->validated());

        return redirect()->back()->with('success', 'Data berhasil ditambahkan!');
    }

    /**
     * Memperbarui Data Barang yang sudah ada.
     *
     * @param  UpdateItemRequest  $request
     * @param  string             $id_item
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateItemRequest $request, string $id_item)
    {
        $item = $this->itemService->findById($id_item);

        if (!$item) {
            abort(404);
        }

        $this->itemService->update($item, $request->validated());

        return redirect()->back()->with('success', 'Data berhasil diupdate!');
    }

    /**
     * Menghapus beberapa Data Barang sekaligus (bulk delete).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request)
    {
        $ids = $request->input('selected_items', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        $deletedCount = $this->itemService->destroySelected($ids);

        return back()->with('success', "{$deletedCount} data terpilih berhasil dihapus.");
    }

    /**
     * Export Data Barang ke format PDF.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf()
    {
        $items = $this->itemService->getAll();

        $pdf = Pdf::loadView('exports.inventory.item-pdf', compact('items'));

        return $pdf->download('Stock_Hollow_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Data Barang ke format Excel.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel()
    {
        return Excel::download(new ItemsExport, 'Stock_Hollow_' . date('Y-m-d') . '.xlsx');
    }
}
