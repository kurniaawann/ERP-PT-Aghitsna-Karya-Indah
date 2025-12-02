<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Items;
use App\Exports\ItemsExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk mengelola data barang inventory (hollow/aluminium).
 * 
 * Fitur:
 * - CRUD barang dengan auto-generate ID (ITM-0001, ITM-0002, dst)
 * - Pencarian berdasarkan nama atau ID barang
 * - Bulk delete dengan checkbox selection
 * - Export ke PDF dan Excel dengan timestamp
 * - Tracking harga modal, harga jual, dan quantity stok
 */
class ItemController extends Controller
{

    /**
     * Generate ID item otomatis dengan format ITM-XXXX (4 digit).
     * 
     * Logic:
     * 1. Ambil item terakhir dari database (ORDER BY id_item DESC)
     * 2. Jika belum ada data, return 'ITM-0001'
     * 3. Extract nomor dari id_item terakhir (ambil substring setelah 'ITM-')
     * 4. Tambah 1, lalu format dengan padding 0 di depan (4 digit)
     * 
     * Contoh:
     * - Item terakhir: ITM-0005 → Generate: ITM-0006
     * - Item terakhir: ITM-0099 → Generate: ITM-0100
     * - Item terakhir: ITM-9999 → Generate: ITM-10000 (5 digit)
     */
    private function generateIdItem()
    {
        // Ambil item terakhir berdasarkan id_item descending
        $lastItem = Items::orderBy('id_item', 'desc')->first();
        
        // Jika belum ada data, mulai dari ITM-0001
        if (!$lastItem) {
            return 'ITM-0001';
        }
        
        // Extract nomor dari id_item (ambil substring setelah 'ITM-')
        $lastNumber = (int) substr($lastItem->id_item, 4);
        
        // Format dengan padding 0 di depan (4 digit)
        return 'ITM-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }


    /**
     * Menampilkan halaman daftar barang inventory dengan fitur pencarian.
     * 
     * Fitur:
     * - Pencarian berdasarkan nama barang atau ID barang
     * - Sorting berdasarkan id_item terbaru (descending)
     * - Pagination 10 data per halaman
     * - Menampilkan semua info: id_item, name_item, quantity, capital_price, selling_price
     */
    public function index(Request $request)
    {
        // Ambil keyword pencarian dari request
        $search = $request->input('search');

        // Query data items dengan filter pencarian
        $items = Items::query()
            ->when($search, function ($query, $search) {
                // Cari berdasarkan nama barang atau ID barang
                $query->where('name_item', 'like', "%{$search}%")
                    ->orWhere('id_item', 'like', "%{$search}%");
            })
            ->orderBy('id_item', 'desc') // Urutkan ID terbaru di atas
            ->paginate(10);

        return view('pages.inventory.item', compact('items'));
    }
    /**
     * Menyimpan data barang baru ke database.
     * 
     * Proses:
     * 1. Generate id_item otomatis (ITM-XXXX)
     * 2. Ambil data dari form: name_item, quantity, capital_price, selling_price
     * 3. Simpan ke database
     * 
     * Catatan:
     * - id_item di-generate otomatis, tidak perlu input manual
     * - capital_price = harga modal/beli
     * - selling_price = harga jual ke customer
     */
    public function store(Request $request)
    {
        // Simpan data barang dengan id_item auto-generated
        Items::create([
            'id_item' => $this->generateIdItem(), // Generate ID otomatis
            'name_item' => $request->name_item,
            'quantity' => $request->quantity,
            'capital_price' => $request->capital_price,
            'selling_price' => $request->selling_price,
        ]);

        return redirect()->back()->with('success', 'Data berhasil ditambahkan!');
    }

    /**
     * Mengupdate data barang yang sudah ada.
     * 
     * Proses:
     * 1. Cari barang berdasarkan id_item (route parameter)
     * 2. Update field: name_item, quantity, capital_price, selling_price
     * 3. id_item tidak bisa diubah (sebagai identifier unik)
     * 
     * Catatan: Menggunakan where()->update() karena id_item bukan primary key
     */
    public function update(Request $request, $id_item)
    {
        // Update barang berdasarkan id_item
        Items::where('id_item', $id_item)->update([
            'name_item' => $request->name_item,
            'quantity' => $request->quantity,
            'capital_price' => $request->capital_price,
            'selling_price' => $request->selling_price,
        ]);
        
        return redirect()->back()->with('success', 'Data berhasil diupdate!');
    }
    /**
     * Menghapus data barang secara bulk (multiple selection).
     * 
     * Proses:
     * 1. Ambil array id_item yang dipilih dari checkbox (parameter 'selected_items')
     * 2. Validasi apakah ada data yang dipilih
     * 3. Hapus semua data berdasarkan id_item
     * 
     * Catatan:
     * - Bulk delete untuk efisiensi (hapus banyak data sekaligus)
     * - Menggunakan id_item sebagai identifier (bukan primary key)
     */
    public function destroySelected(Request $request)
    {
        // Ambil array id_item dari checkbox (default empty array jika tidak ada)
        $selectedIds = $request->input('selected_items', []);

        // Validasi: pastikan ada data yang dipilih
        if (empty($selectedIds)) {
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        // Hapus semua item yang dipilih berdasarkan id_item
        Items::whereIn('id_item', $selectedIds)->delete();

        return redirect()->back()->with('success', 'Data terpilih berhasil dihapus.');
    }

    /**
     * Export daftar barang ke format PDF.
     * 
     * Proses:
     * 1. Ambil semua data items (ORDER BY id_item ASC)
     * 2. Load view 'exports.item-pdf' dengan data items
     * 3. Generate PDF menggunakan DomPDF
     * 4. Download dengan nama file 'stock-hollow-YYYY-MM-DD.pdf'
     * 
     * Library: Barryvdh\DomPDF (wrapper untuk DomPDF)
     */
    public function exportPdf()
    {
        // Ambil semua data items, urutkan berdasarkan id_item ascending
        $items = Items::orderBy('id_item', 'asc')->get();

        // Generate PDF dari view
        $pdf = Pdf::loadView('exports.item-pdf', compact('items'));

        // Download PDF dengan timestamp
        return $pdf->download('stock-hollow-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export daftar barang ke format Excel.
     * 
     * Proses:
     * 1. Gunakan class ItemsExport (implements FromCollection)
     * 2. Generate file Excel dengan Maatwebsite\Excel
     * 3. Download dengan nama file 'stock-hollow-YYYY-MM-DD.xlsx'
     * 
     * Catatan:
     * - Logic export ada di app/Exports/ItemsExport.php
     * - Library: Maatwebsite\Excel (wrapper untuk PhpSpreadsheet)
     * - Format Excel: .xlsx (Excel 2007+)
     */
    public function exportExcel()
    {
        // Download Excel menggunakan ItemsExport class dengan timestamp
        return Excel::download(new ItemsExport, 'stock-hollow-' . date('Y-m-d') . '.xlsx');
    }

}
