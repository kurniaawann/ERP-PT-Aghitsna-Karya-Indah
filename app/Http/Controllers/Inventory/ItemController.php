<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreItemRequest;
use App\Models\Inventory\Items;
use App\Services\InputNormalizer;
use App\Exports\Inventory\ItemsExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\HasBulkActions;

class ItemController extends Controller
{
    use HasBulkActions;

    public function index(Request $request)
    {
        $search = $request->input('search');

        $items = Items::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name_item', 'like', "%{$search}%")
                      ->orWhere('id_item', 'like', "%{$search}%");
                });
            })
            ->orderBy('id_item', 'desc')
            ->paginate(15);

        return view('pages.inventory.item', compact('items'));
    }

    public function store(StoreItemRequest $request)
    {
        Items::create([
            'id_item' => Items::generateNextId(),
            'name_item' => $request->name_item,
            'quantity' => $request->quantity,
            'capital_price' => InputNormalizer::normalizeCurrency($request->capital_price),
            'selling_price' => InputNormalizer::normalizeCurrency($request->selling_price),
        ]);

        return redirect()->back()->with('success', 'Data berhasil ditambahkan!');
    }

    public function update(StoreItemRequest $request, string $id_item)
    {
        $item = Items::where('id_item', $id_item)->firstOrFail();

        $item->update([
            'name_item' => $request->name_item,
            'quantity' => $request->quantity,
            'capital_price' => InputNormalizer::normalizeCurrency($request->capital_price),
            'selling_price' => InputNormalizer::normalizeCurrency($request->selling_price),
        ]);

        return redirect()->back()->with('success', 'Data berhasil diupdate!');
    }

    public function destroySelected(Request $request)
    {
        return $this->destroySelectedBy($request, Items::class, 'selected_items', 'id_item');
    }

    public function exportPdf()
    {
        $items = Items::orderBy('id_item', 'asc')->get();

        $pdf = Pdf::loadView('exports.inventory.item-pdf', compact('items'));

        return $pdf->download('Stock_Hollow_' . date('Y-m-d') . '.pdf');
    }

    public function exportExcel()
    {
        return Excel::download(new ItemsExport, 'Stock_Hollow_' . date('Y-m-d') . '.xlsx');
    }
}
