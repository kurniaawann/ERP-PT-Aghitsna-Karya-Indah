<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Report\TransactionCategory;
use Illuminate\Http\Request;

class TransactionCategoryController extends Controller
{
    public function index(Request $request)
    {
        // Ambil parameter pencarian dari request (untuk filter nama atau kode kategori)
        $search = $request->get('search');
        // Ambil parameter type dari request (untuk filter jenis kategori: pemasukan/pengeluaran)
        $type = $request->get('type');

        // Mulai query untuk mengambil data kategori transaksi
        $categories = TransactionCategory::query()
            // Filter berdasarkan type jika parameter ada
            ->when($type, function ($query, $type) {
                // when() menjalankan closure hanya jika $type tidak null/empty
                // Filter where type = 'pemasukan' atau 'pengeluaran'
                return $query->where('type', $type);
            })
            // Filter berdasarkan pencarian jika parameter $search ada
            ->when($search, function ($query, $search) {
                // Gunakan where dengan closure untuk grouping kondisi OR
                return $query->where(function ($q) use ($search) {
                    // Cari di kolom name dengan LIKE (partial match)
                    $q->where('name', 'like', "%{$search}%")
                        // ATAU cari di kolom code dengan LIKE (partial match)
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            // Urutkan berdasarkan sort_order ascending (manual ordering, urutan terkecil di atas)
            ->orderBy('sort_order')
            // Jika sort_order sama, urutkan berdasarkan name ascending (alphabetical)
            ->orderBy('name')
            // Pagination 10 data per halaman
            ->paginate(10);

        // Ambil semua kode kategori yang sudah ada untuk validasi duplikat di frontend
        // pluck('code', 'id') menghasilkan array: [id => code]
        // Contoh: [1 => 'KTG001', 2 => 'KTG002']
        // toArray() convert collection menjadi array biasa
        $existingCodes = TransactionCategory::pluck('code', 'id')->toArray();

        // Ambil ID kategori yang sedang digunakan di expense reports (untuk disable delete button)
        // has('expenseRecaps') filter hanya kategori yang punya relasi dengan expense reports
        // pluck('id') ambil hanya kolom id, hasilnya collection [1, 5, 10]
        // toArray() convert collection menjadi array biasa
        $usedCategoryIds = TransactionCategory::has('expenseRecaps')->pluck('id')->toArray();

        // Return view dengan data categories (kategori + pagination), existingCodes (validasi duplikat),
        // dan usedCategoryIds (untuk disable delete button kategori yang sedang digunakan)
        return view('pages.report.transaction-category', compact('categories', 'existingCodes', 'usedCategoryIds'));
    }

    public function store(Request $request)
    {
        // Validasi manual: cek apakah kode kategori sudah digunakan
        // where('code', $request->code) mencari record dengan code yang sama
        // exists() return true jika ada, false jika tidak ada
        $existingCode = TransactionCategory::where('code', $request->code)->exists();

        // Jika kode sudah ada (duplikat)
        if ($existingCode) {
            // Redirect kembali dengan flash message error dan input sebelumnya (withInput)
            return back()->with('error', 'Kode kategori sudah digunakan!')->withInput();
        }

        try {
            // Auto-assign sort_order ke posisi terakhir
            // max('sort_order') mengambil nilai sort_order terbesar dari database
            // ?? 0 adalah null coalescing operator, jika max() return null (tabel kosong), gunakan 0
            $maxSortOrder = TransactionCategory::max('sort_order') ?? 0;

            // Insert kategori baru ke database
            TransactionCategory::create([
                // Nama kategori dari input form
                'name' => $request->name,
                // Kode kategori dari input form (sudah divalidasi unique)
                'code' => $request->code,
                // Type kategori dari input form ('pemasukan' atau 'pengeluaran')
                'type' => $request->type,
                // Sort order otomatis ke posisi terakhir (max + 1)
                'sort_order' => $maxSortOrder + 1,
                // Status default aktif (true) saat pertama kali dibuat
                'is_active' => true,
            ]);

            // Redirect ke halaman index dengan flash message sukses
            return redirect()->route('transaction-category.index')
                ->with('success', 'Kategori transaksi berhasil ditambahkan!');

        } catch (\Exception $e) {
            // Jika terjadi error saat insert (database error, dll)
            // Redirect kembali dengan flash message error berisi detail exception dan input sebelumnya
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        // Cari kategori berdasarkan ID, jika tidak ditemukan throw 404
        $category = TransactionCategory::findOrFail($id);

        // Validasi: cek apakah kode sudah digunakan kategori lain (kecuali kategori ini sendiri)
        // where('code', $request->code) mencari code yang sama
        // where('id', '!=', $id) kecualikan kategori yang sedang diedit (boleh pakai code sendiri)
        // exists() return true jika ada kategori lain yang pakai code ini
        $existingCode = TransactionCategory::where('code', $request->code)
            ->where('id', '!=', $id)
            ->exists();

        // Jika kode sudah digunakan kategori lain (duplikat)
        if ($existingCode) {
            // Redirect kembali dengan flash message error dan input sebelumnya
            return back()->with('error', 'Kode kategori sudah digunakan!')->withInput();
        }

        // Ambil sort_order baru dari input form
        $newSortOrder = $request->sort_order;
        // Ambil sort_order lama dari database (sebelum diupdate)
        $oldSortOrder = $category->sort_order;

        try {
            // REORDERING LOGIC: Jika sort_order berubah, lakukan reordering kategori lain
            // Tujuan: memastikan tidak ada gap dalam sort_order (harus berurutan 1, 2, 3, ...)
            if ($newSortOrder != $oldSortOrder) {

                // CASE 1: Pindah ke atas (sort_order mengecil)
                // Contoh: old=5, new=2 → kategori dengan order 2,3,4 harus digeser +1 menjadi 3,4,5
                if ($newSortOrder < $oldSortOrder) {
                    // Geser kategori yang berada di antara posisi baru dan lama
                    // where('id', '!=', $id) kecualikan kategori yang sedang diedit
                    // whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1]) ambil kategori dengan order 2-4
                    // increment('sort_order') tambah 1 ke sort_order masing-masing
                    TransactionCategory::where('id', '!=', $id)
                        ->whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])
                        ->increment('sort_order');

                } else {
                    // CASE 2: Pindah ke bawah (sort_order membesar)
                    // Contoh: old=2, new=5 → kategori dengan order 3,4,5 harus digeser -1 menjadi 2,3,4
                    // whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder]) ambil kategori dengan order 3-5
                    // decrement('sort_order') kurangi 1 dari sort_order masing-masing
                    TransactionCategory::where('id', '!=', $id)
                        ->whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder])
                        ->decrement('sort_order');
                }
            }

            // Update data kategori dengan data baru dari form
            $category->update([
                // Update nama kategori
                'name' => $request->name,
                // Update kode kategori (sudah divalidasi unique)
                'code' => $request->code,
                // Update type kategori
                'type' => $request->type,
                // Update sort_order (setelah reordering kategori lain)
                'sort_order' => $newSortOrder,
            ]);

            // Redirect ke halaman index dengan flash message sukses
            return redirect()->route('transaction-category.index')
                ->with('success', 'Kategori transaksi berhasil diupdate!');

        } catch (\Exception $e) {
            // Jika terjadi error (database error, dll)
            // Redirect kembali dengan flash message error berisi detail exception
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        // Cari kategori berdasarkan ID, jika tidak ditemukan throw 404
        $category = TransactionCategory::findOrFail($id);

        try {
            // Toggle status is_active: true <-> false
            // ! adalah NOT operator, jika true menjadi false, jika false menjadi true
            $category->is_active = !$category->is_active;

            // Simpan perubahan ke database
            $category->save();

            // Buat pesan sukses dinamis berdasarkan status baru
            // Ternary operator: kondisi ? nilai_jika_true : nilai_jika_false
            // Jika is_active = true, text = 'diaktifkan', jika false, text = 'dinonaktifkan'
            $status = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';

            // Redirect ke halaman index dengan flash message sukses
            // {$status} akan di-replace dengan 'diaktifkan' atau 'dinonaktifkan'
            return redirect()->route('transaction-category.index')
                ->with('success', "Kategori berhasil {$status}!");

        } catch (\Exception $e) {
            // Jika terjadi error saat save
            // Redirect kembali dengan flash message error berisi detail exception
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroySelected(Request $request)
    {
        // Ambil array ID dari input dengan nama 'selected_categories' (dari checkbox selection)
        // Default empty array jika input tidak ada
        $selectedIds = $request->input('selected_categories', []);

        // Validasi: cek apakah $selectedIds kosong
        if (empty($selectedIds)) {
            // Redirect kembali dengan flash message error
            return back()->with('error', 'Tidak ada data yang dipilih!');
        }

        try {
            // CEK CONSTRAINT: Cek apakah ada kategori yang sedang digunakan di expense reports
            // whereIn('id', $selectedIds) filter hanya kategori yang dipilih
            // has('expenseRecaps') filter hanya yang punya relasi expense (sudah dipakai)
            // pluck('name') ambil hanya kolom name, hasilnya collection nama-nama kategori
            // toArray() convert collection menjadi array biasa
            $usedCategories = TransactionCategory::whereIn('id', $selectedIds)
                ->has('expenseRecaps')
                ->pluck('name')
                ->toArray();

            // Jika ada kategori yang sedang digunakan (array tidak kosong)
            if (!empty($usedCategories)) {
                // implode(', ', $usedCategories) menggabungkan array menjadi string dengan separator ', '
                // Contoh: ['Kategori A', 'Kategori B'] → 'Kategori A, Kategori B'
                // Redirect kembali dengan flash message error berisi nama kategori yang tidak bisa dihapus
                return back()->with('error', 'Kategori berikut tidak dapat dihapus karena sedang digunakan: ' . implode(', ', $usedCategories));
            }

            // Jika aman (tidak ada yang digunakan), hapus semua kategori yang dipilih
            // whereIn('id', $selectedIds) filter kategori yang dipilih
            // delete() menghapus record dari database
            TransactionCategory::whereIn('id', $selectedIds)->delete();

            // Hitung jumlah data yang terhapus untuk ditampilkan di pesan sukses
            $deletedCount = count($selectedIds);

            // Redirect ke halaman index dengan flash message sukses berisi jumlah data terhapus
            return redirect()->route('transaction-category.index')
                ->with('success', "Berhasil menghapus {$deletedCount} kategori.");

        } catch (\Exception $e) {
            // Jika terjadi error (database error, dll)
            // Redirect kembali dengan flash message error berisi detail exception
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}

