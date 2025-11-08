<?php

namespace App\Http\Controllers;

use App\Models\TransactionCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

        return view('pages.transaction-category', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:transaction_categories,code',
            'type' => 'required|in:INCOME,EXPENSE',
            'sort_order' => 'nullable|integer|min:1',
        ], [
            'name.required' => 'Nama kategori wajib diisi',
            'code.required' => 'Kode kategori wajib diisi',
            'code.unique' => 'Kode kategori sudah digunakan',
            'type.required' => 'Tipe kategori wajib dipilih',
            'type.in' => 'Tipe kategori tidak valid',
            'sort_order.min' => 'Urutan minimal 1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        $validated['is_active'] = true;

        // Auto-assign sort_order jika tidak diisi (ambil urutan terakhir + 1)
        if (empty($validated['sort_order'])) {
            $maxSortOrder = TransactionCategory::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;
        } else {
            // Jika user mengisi sort_order, geser kategori yang >= sort_order tersebut
            TransactionCategory::where('sort_order', '>=', $validated['sort_order'])
                ->increment('sort_order');
        }

        try {
            TransactionCategory::create($validated);

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

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:transaction_categories,code,' . $id,
            'type' => 'required|in:INCOME,EXPENSE',
            'sort_order' => 'required|integer|min:1',
        ], [
            'name.required' => 'Nama kategori wajib diisi',
            'code.required' => 'Kode kategori wajib diisi',
            'code.unique' => 'Kode kategori sudah digunakan',
            'type.required' => 'Tipe kategori wajib dipilih',
            'sort_order.required' => 'Urutan wajib diisi',
            'sort_order.min' => 'Urutan minimal 1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        $newSortOrder = $validated['sort_order'];
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
            $category->update($validated);

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
