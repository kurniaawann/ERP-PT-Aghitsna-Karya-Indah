<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
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
        // Ambil keyword pencarian dari request (untuk filter nama atau ID item)
        $search = $request->input('search');

        // Mulai query untuk mengambil data items
        $items = Items::query()
            // Filter berdasarkan pencarian jika parameter $search ada
            ->when($search, function ($query, $search) {
                // when() menjalankan closure hanya jika $search tidak null/empty
                // Cari berdasarkan nama item dengan LIKE (partial match)
                $query->where('name_item', 'like', "%{$search}%")
                    // ATAU cari berdasarkan ID item dengan LIKE (partial match)
                    ->orWhere('id_item', 'like', "%{$search}%");
            })
            // Urutkan berdasarkan id_item descending (ID terbaru di atas)
            ->orderBy('id_item', 'desc')
            // Pagination 10 data per halaman
            ->paginate(10);

        // Return view dengan data items (barang inventory + pagination)
        return view('pages.inventory.item', compact('items'));
    }
    public function store(Request $request)
    {
        $request->merge([
            'capital_price' => InputNormalizer::normalizeCurrency($request->capital_price),
            'selling_price' => InputNormalizer::normalizeCurrency($request->selling_price),
        ]);

        // Insert data item baru ke database
        // create() menerima array associative dan akan insert record baru
        Items::create([
            // Generate id_item otomatis menggunakan private method generateIdItem()
            // Format: ITM-0001, ITM-0002, ITM-0003, dst
            'id_item' => Items::generateNextId(),
            // Nama item dari input form
            'name_item' => $request->name_item,
            // Quantity/jumlah stok dari input form (integer)
            'quantity' => $request->quantity,
            // Harga modal/beli dari supplier dari input form (integer, dalam Rupiah)
            'capital_price' => $request->capital_price,
            // Harga jual ke customer dari input form (integer, dalam Rupiah)
            'selling_price' => $request->selling_price,
        ]);

        // Redirect kembali ke halaman sebelumnya (halaman index) dengan flash message sukses
        return redirect()->back()->with('success', 'Data berhasil ditambahkan!');
    }

    public function update(Request $request, $id_item)
    {
        $request->merge([
            'capital_price' => InputNormalizer::normalizeCurrency($request->capital_price),
            'selling_price' => InputNormalizer::normalizeCurrency($request->selling_price),
        ]);

        // Update data item berdasarkan id_item
        // where('id_item', $id_item) mencari record dengan id_item yang sesuai
        // update() akan mengubah field yang ada di array
        // Note: Menggunakan where()->update() karena id_item bukan primary key (primary key adalah id auto-increment)
        Items::where('id_item', $id_item)->update([
            // Update nama item dari input form edit
            'name_item' => $request->name_item,
            // Update quantity dari input form edit
            'quantity' => $request->quantity,
            // Update harga modal dari input form edit
            'capital_price' => $request->capital_price,
            // Update harga jual dari input form edit
            'selling_price' => $request->selling_price,
        ]);

        // Redirect kembali ke halaman sebelumnya dengan flash message sukses
        return redirect()->back()->with('success', 'Data berhasil diupdate!');
    }

    public function destroySelected(Request $request)
    {
        // Ambil array id_item dari input dengan nama 'selected_items'
        // Default empty array jika input tidak ada (untuk handle jika user tidak centang checkbox)
        return $this->destroySelectedBy($request, Items::class, 'selected_items', 'id_item');
    }

    public function exportPdf()
    {
        // Ambil semua data items dari database tanpa pagination
        // orderBy('id_item', 'asc') mengurutkan berdasarkan id_item ascending (ITM-0001, ITM-0002, dst)
        // get() mengambil semua record (bukan paginate, karena untuk export)
        $items = Items::orderBy('id_item', 'asc')->get();

        // Generate PDF dari view 'exports.item-pdf' dengan data items
        // Pdf::loadView() akan render blade template menjadi PDF
        // compact('items') mengirim variable $items ke view
        $pdf = Pdf::loadView('exports.inventory.item-pdf', compact('items'));

        // Download PDF dengan nama file dinamis: stock-hollow-YYYY-MM-DD.pdf
        // date('Y-m-d') menghasilkan format tanggal: 2025-01-01
        // download() akan trigger browser download file (bukan display inline)
        return $pdf->download('stock-hollow-' . date('Y-m-d') . '.pdf');
    }

    public function exportExcel()
    {
        // Download file Excel menggunakan ItemsExport class
        // new ItemsExport membuat instance dari class export (implements FromCollection)
        // Logic export ada di app/Exports/Inventory/ItemsExport.php (query, format, dll)
        // Excel::download() akan generate file .xlsx dan trigger browser download
        // Nama file: stock-hollow-YYYY-MM-DD.xlsx (dengan timestamp)
        return Excel::download(new ItemsExport, 'stock-hollow-' . date('Y-m-d') . '.xlsx');
    }



}
