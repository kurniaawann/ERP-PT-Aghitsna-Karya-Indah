<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreItemRequest;
use App\Http\Requests\Inventory\UpdateItemRequest;
use App\Models\Inventory\Items;
use App\Services\Inventory\ItemService;
use App\Exports\Inventory\ItemsExport;
use App\Traits\HasBulkActions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ItemController extends Controller
{
    use HasBulkActions;

    public function __construct(
        private readonly ItemService $itemService
    ) {}

    public function index(Request $request)
    {
        $items = $this->itemService->getPaginatedSearch($request->input('search'));

        return view('pages.inventory.item', compact('items'));
    }

    public function store(StoreItemRequest $request)
    {
        $this->itemService->store($request->validated());

        return redirect()->back()->with('success', 'Data berhasil ditambahkan!');
    }

    public function update(UpdateItemRequest $request, string $id_item)
    {
        $item = $this->itemService->findById($id_item);

        if (!$item) {
            abort(404);
        }

        $this->itemService->update($item, $request->validated());

        return redirect()->back()->with('success', 'Data berhasil diupdate!');
    }

    public function destroySelected(Request $request)
    {
        return $this->destroySelectedBy($request, Items::class, 'selected_items', 'id_item');
    }

    public function exportPdf()
    {
        $items = $this->itemService->getAll();

        $pdf = Pdf::loadView('exports.inventory.item-pdf', compact('items'));

        return $pdf->download('Stock_Hollow_' . date('Y-m-d') . '.pdf');
    }

    public function exportExcel()
    {
        return Excel::download(new ItemsExport, 'Stock_Hollow_' . date('Y-m-d') . '.xlsx');
    }
}
