<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Items;
use App\Exports\Inventory\ItemsExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ItemController extends Controller
{
    private function generateIdItem()
    {
        // Ambil item terakhir dari database, urutkan berdasarkan id_item descending (terbesar di atas)
        // first() akan return model instance atau null jika tabel kosong
        $lastItem = Items::orderBy('id_item', 'desc')->first();

        // Jika belum ada data (tabel kosong), mulai dari ITM-0001
        if (!$lastItem) {
            return 'ITM-0001';
        }

        // Extract nomor dari id_item terakhir
        // substr($lastItem->id_item, 4) mengambil substring mulai dari index 4 sampai akhir
        // Contoh: 'ITM-0005' → substring dari index 4 = '0005'
        // (int) untuk convert string '0005' menjadi integer 5
        $lastNumber = (int) substr($lastItem->id_item, 4);

        // Tambah 1 untuk nomor berikutnya, lalu format dengan padding 0 di depan
        // str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT) akan format angka dengan 4 digit, padding 0 di kiri
        // Contoh: 5 + 1 = 6 → str_pad(6, 4, '0', STR_PAD_LEFT) → '0006'
        // 'ITM-' . '0006' → 'ITM-0006'
        return 'ITM-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

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
        // Insert data item baru ke database
        // create() menerima array associative dan akan insert record baru
        Items::create([
            // Generate id_item otomatis menggunakan private method generateIdItem()
            // Format: ITM-0001, ITM-0002, ITM-0003, dst
            'id_item' => $this->generateIdItem(),
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
        $selectedIds = $request->input('selected_items', []);

        // Validasi: cek apakah $selectedIds kosong (empty() return true jika null, [], atau '')
        if (empty($selectedIds)) {
            // Redirect kembali dengan flash message error
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        // Hapus semua item yang ID-nya ada dalam array $selectedIds
        // whereIn('id_item', $selectedIds) akan match semua record dengan id_item di dalam array
        // delete() akan menghapus record tersebut dari database
        Items::whereIn('id_item', $selectedIds)->delete();

        // Redirect kembali dengan flash message sukses
        return redirect()->back()->with('success', 'Data terpilih berhasil dihapus.');
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
        $pdf = Pdf::loadView('exports.item-pdf', compact('items'));

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
