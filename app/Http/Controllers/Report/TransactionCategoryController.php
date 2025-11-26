<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Report\TransactionCategory;
use Illuminate\Http\Request;

class TransactionCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $type = $request->get('type');

        $categories = TransactionCategory::query()
            ->when($type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10);

        // Get all existing codes for frontend validation
        $existingCodes = TransactionCategory::pluck('code', 'id')->toArray();

        // Get IDs of categories that are being used
        $usedCategoryIds = TransactionCategory::has('expenseReports')->pluck('id')->toArray();

        return view('pages.report.transaction-category', compact('categories', 'existingCodes', 'usedCategoryIds'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Cek apakah kode sudah digunakan (validasi unik)
        $existingCode = TransactionCategory::where('code', $request->code)->exists();
        if ($existingCode) {
            return back()->with('error', 'Kode kategori sudah digunakan!')->withInput();
        }

        try {
            // Auto-assign sort_order (ambil urutan terakhir + 1)
            $maxSortOrder = TransactionCategory::max('sort_order') ?? 0;

            TransactionCategory::create([
                'name' => $request->name,
                'code' => $request->code,
                'type' => $request->type,
                'sort_order' => $maxSortOrder + 1,
                'is_active' => true,
            ]);

            return redirect()->route('transaction-category.index')
                ->with('success', 'Kategori transaksi berhasil ditambahkan!');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $category = TransactionCategory::findOrFail($id);

        // Cek apakah kode sudah digunakan (kecuali untuk kategori ini sendiri)
        $existingCode = TransactionCategory::where('code', $request->code)
            ->where('id', '!=', $id)
            ->exists();

        if ($existingCode) {
            return back()->with('error', 'Kode kategori sudah digunakan!')->withInput();
        }

        $newSortOrder = $request->sort_order;
        $oldSortOrder = $category->sort_order;

        try {
            // Jika urutan berubah, lakukan reordering
            if ($newSortOrder != $oldSortOrder) {
                if ($newSortOrder < $oldSortOrder) {
                    // Pindah ke atas: geser kategori yang di antara new dan old sort order
                    TransactionCategory::where('id', '!=', $id)
                        ->whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])
                        ->increment('sort_order');
                } else {
                    // Pindah ke bawah: geser kategori yang di antara old dan new sort order
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
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        $category = TransactionCategory::findOrFail($id);

        try {
            $category->is_active = !$category->is_active;
            $category->save();

            $status = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';

            return redirect()->route('transaction-category.index')
                ->with('success', "Kategori berhasil {$status}!");
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Delete selected items (bulk delete).
     */
    public function destroySelected(Request $request)
    {
        $selectedIds = $request->input('selected_categories', []);

        if (empty($selectedIds)) {
            return back()->with('error', 'Tidak ada data yang dipilih!');
        }

        try {
            // Check if any category is being used
            $usedCategories = TransactionCategory::whereIn('id', $selectedIds)
                ->has('expenseReports')
                ->pluck('name')
                ->toArray();

            if (!empty($usedCategories)) {
                return back()->with('error', 'Kategori berikut tidak dapat dihapus karena sedang digunakan: ' . implode(', ', $usedCategories));
            }

            TransactionCategory::whereIn('id', $selectedIds)->delete();
            $deletedCount = count($selectedIds);

            return redirect()->route('transaction-category.index')
                ->with('success', "Berhasil menghapus {$deletedCount} kategori.");
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
