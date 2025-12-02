<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Report\TransactionCategory;
use Illuminate\Http\Request;

/**
 * Controller untuk mengelola kategori transaksi pengeluaran.
 * 
 * Fitur:
 * - CRUD kategori dengan kode unik dan type (pemasukan/pengeluaran)
 * - Pencarian berdasarkan nama atau kode kategori
 * - Filter berdasarkan type kategori
 * - Sort order management untuk mengatur urutan tampilan
 * - Toggle status aktif/nonaktif
 * - Bulk delete dengan validasi usage constraint
 * - Reordering otomatis saat mengubah sort_order
 */
class TransactionCategoryController extends Controller
{
    /**
     * Menampilkan daftar kategori transaksi dengan pencarian dan filter.
     * 
     * Fitur:
     * - Pencarian berdasarkan nama atau kode kategori
     * - Filter berdasarkan type (pemasukan/pengeluaran)
     * - Sorting berdasarkan sort_order dan name
     * - Pagination 10 data per halaman
     * - Kirim existingCodes untuk validasi duplikat di frontend
     * - Kirim usedCategoryIds untuk disable delete button di frontend
     */
    public function index(Request $request)
    {
        // Ambil parameter pencarian dan filter
        $search = $request->get('search');
        $type = $request->get('type');

        // Query kategori dengan filter dan pencarian
        $categories = TransactionCategory::query()
            ->when($type, function ($query, $type) {
                // Filter berdasarkan type jika ada
                return $query->where('type', $type);
            })
            ->when($search, function ($query, $search) {
                // Pencarian di nama atau kode kategori
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order') // Sorting manual (primary)
            ->orderBy('name') // Sorting alphabetical (secondary)
            ->paginate(10);

        // Ambil semua kode untuk validasi duplikat di frontend
        $existingCodes = TransactionCategory::pluck('code', 'id')->toArray();

        // Ambil ID kategori yang sedang digunakan (untuk disable delete button)
        $usedCategoryIds = TransactionCategory::has('expenseReports')->pluck('id')->toArray();

        return view('pages.report.transaction-category', compact('categories', 'existingCodes', 'usedCategoryIds'));
    }

    /**
     * Menyimpan kategori transaksi baru dengan validasi dan auto-ordering.
     * 
     * Proses:
     * 1. Validasi kode kategori harus unique
     * 2. Auto-calculate sort_order (max + 1)
     * 3. Set is_active = true (default aktif)
     * 4. Simpan ke database
     * 
     * Catatan:
     * - Code harus unique (validasi manual, bukan validation rules)
     * - sort_order otomatis di-set ke posisi terakhir
     * - type: 'pemasukan' atau 'pengeluaran'
     */
    public function store(Request $request)
    {
        // Validasi manual: cek apakah kode sudah digunakan
        $existingCode = TransactionCategory::where('code', $request->code)->exists();
        if ($existingCode) {
            return back()->with('error', 'Kode kategori sudah digunakan!')->withInput();
        }

        try {
            // Auto-assign sort_order (ambil maksimal + 1)
            $maxSortOrder = TransactionCategory::max('sort_order') ?? 0;

            // Simpan kategori baru
            TransactionCategory::create([
                'name' => $request->name,
                'code' => $request->code,
                'type' => $request->type,
                'sort_order' => $maxSortOrder + 1,
                'is_active' => true, // Default aktif
            ]);

            return redirect()->route('transaction-category.index')
                ->with('success', 'Kategori transaksi berhasil ditambahkan!');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Mengupdate kategori dengan reordering otomatis saat sort_order berubah.
     * 
     * Proses:
     * 1. Validasi kode unique (kecuali untuk kategori ini sendiri)
     * 2. Jika sort_order berubah, lakukan reordering:
     *    - Pindah ke atas: geser kategori di antara posisi baru dan lama (increment)
     *    - Pindah ke bawah: geser kategori di antara posisi lama dan baru (decrement)
     * 3. Update data kategori
     * 
     * Catatan:
     * - Reordering logic memastikan tidak ada gap dalam sort_order
     * - Algorithm: shift items between old and new position
     */
    public function update(Request $request, $id)
    {
        // Cari kategori by ID
        $category = TransactionCategory::findOrFail($id);

        // Validasi: cek kode unique (kecuali untuk kategori ini sendiri)
        $existingCode = TransactionCategory::where('code', $request->code)
            ->where('id', '!=', $id)
            ->exists();

        if ($existingCode) {
            return back()->with('error', 'Kode kategori sudah digunakan!')->withInput();
        }

        $newSortOrder = $request->sort_order;
        $oldSortOrder = $category->sort_order;

        try {
            // Jika sort_order berubah, lakukan reordering
            if ($newSortOrder != $oldSortOrder) {
                if ($newSortOrder < $oldSortOrder) {
                    // Pindah ke atas: geser kategori yang di antara new dan old sort order
                    // Contoh: old=5, new=2 → kategori dengan order 2-4 digeser +1
                    TransactionCategory::where('id', '!=', $id)
                        ->whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])
                        ->increment('sort_order');
                } else {
                    // Pindah ke bawah: geser kategori yang di antara old dan new sort order
                    // Contoh: old=2, new=5 → kategori dengan order 3-5 digeser -1
                    TransactionCategory::where('id', '!=', $id)
                        ->whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder])
                        ->decrement('sort_order');
                }
            }

            // Update kategori dengan data baru
            $category->update([
                'name' => $request->name,
                'code' => $request->code,
                'type' => $request->type,
                'sort_order' => $newSortOrder,
            ]);

            return redirect()->route('transaction-category.index')
                ->with('success', 'Kategori transaksi berhasil diupdate!');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Toggle status aktif/nonaktif kategori (quick action).
     * 
     * Proses:
     * - Toggle is_active: true <-> false
     * - Dynamic success message berdasarkan status baru
     * 
     * Digunakan untuk: Quick toggle dari tombol di halaman index
     */
    public function toggleStatus($id)
    {
        // Cari kategori by ID
        $category = TransactionCategory::findOrFail($id);

        try {
            // Toggle status: aktif <-> nonaktif
            $category->is_active = !$category->is_active;
            $category->save();

            // Dynamic message berdasarkan status baru
            $status = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';

            return redirect()->route('transaction-category.index')
                ->with('success', "Kategori berhasil {$status}!");
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus kategori secara bulk dengan validasi usage constraint.
     * 
     * Proses:
     * 1. Ambil array ID yang dipilih dari checkbox
     * 2. Validasi apakah ada data yang dipilih
     * 3. Cek apakah ada kategori yang sedang digunakan (relasi expenseReports)
     * 4. Jika ada yang digunakan, tolak dengan list nama kategori
     * 5. Jika aman, hapus semua kategori terpilih
     * 
     * Catatan:
     * - Constraint check untuk integritas data
     * - Kategori yang digunakan expense tidak bisa dihapus
     * - Error message menampilkan nama kategori yang tidak bisa dihapus
     */
    public function destroySelected(Request $request)
    {
        // Ambil array ID dari checkbox
        $selectedIds = $request->input('selected_categories', []);

        // Validasi: pastikan ada data yang dipilih
        if (empty($selectedIds)) {
            return back()->with('error', 'Tidak ada data yang dipilih!');
        }

        try {
            // Cek apakah ada kategori yang sedang digunakan
            $usedCategories = TransactionCategory::whereIn('id', $selectedIds)
                ->has('expenseReports') // Yang punya relasi dengan expense
                ->pluck('name')
                ->toArray();

            // Jika ada yang digunakan, tolak penghapusan
            if (!empty($usedCategories)) {
                return back()->with('error', 'Kategori berikut tidak dapat dihapus karena sedang digunakan: ' . implode(', ', $usedCategories));
            }

            // Hapus kategori (aman karena tidak digunakan)
            TransactionCategory::whereIn('id', $selectedIds)->delete();
            $deletedCount = count($selectedIds);

            return redirect()->route('transaction-category.index')
                ->with('success', "Berhasil menghapus {$deletedCount} kategori.");
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
