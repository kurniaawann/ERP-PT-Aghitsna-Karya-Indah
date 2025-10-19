<?php

namespace App\Http\Controllers;

use App\Models\Items;
use App\Exports\ItemsExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ItemController extends Controller
{

    //Generate ID Item


    private function generateIdItem()
    {
        $lastItem = Items::orderBy('id_item', 'desc')->first();
        if (!$lastItem) {
            return 'ITM-0001';
        }
        $lastNumber = (int) substr($lastItem->id_item, 4);
        return 'ITM-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }


    public function index(Request $request)
    {
        $search = $request->input('search');

        $items = Items::query()
            ->when($search, function ($query, $search) {
                $query->where('name_item', 'like', "%{$search}%")
                    ->orWhere('id_item', 'like', "%{$search}%");
            })
            ->orderBy('id_item', 'desc')
            ->paginate(10);

        // render view yang sama
        return view('pages.item', compact('items'));
    }
    //Simpan Data Baru
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name_item' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'capital_price' => 'required|integer|min:0',
            'selling_price' => 'required|integer|min:0',
        ]);

        Items::create([
            'id_item' => $this->generateIdItem(),
            'name_item' => $validatedData['name_item'],
            'quantity' => $validatedData['quantity'],
            'capital_price' => $validatedData['capital_price'],
            'selling_price' => $validatedData['selling_price'],
        ]);

        return redirect()->back()->with('success', 'Data berhasil ditambahkan!');
    }

    //Update Data
    public function update(Request $request, $id_item)
    {
        $validatedData = $request->validate([
            'name_item' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'capital_price' => 'required|integer|min:0',
            'selling_price' => 'required|integer|min:0',
        ]);

        Items::where('id_item', $id_item)->update([
            'name_item' => $validatedData['name_item'],
            'quantity' => $validatedData['quantity'],
            'capital_price' => $validatedData['capital_price'],
            'selling_price' => $validatedData['selling_price'],
        ]);
        return redirect()->back()->with('success', 'Data berhasil diupdate!');
    }
    //Hapus Data
    public function destroySelected(Request $request)
    {
        $selectedIds = $request->input('selected_items', []);

        if (empty($selectedIds)) {
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        // Hapus semua item yang dipilih
        Items::whereIn('id_item', $selectedIds)->delete();

        return redirect()->back()->with('success', 'Data terpilih berhasil dihapus.');
    }

    //Export PDF
    public function exportPdf()
    {
        $items = Items::orderBy('id_item', 'asc')->get();

        $pdf = Pdf::loadView('exports.item-pdf', compact('items'));

        return $pdf->download('stock-hollow-' . date('Y-m-d') . '.pdf');
    }

    //Export Excel
    public function exportExcel()
    {
        return Excel::download(new ItemsExport, 'stock-hollow-' . date('Y-m-d') . '.xlsx');
    }

}
