<?php

namespace App\Services\Report;

use App\Models\Report\TransactionCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service untuk menangani business logic Kategori Transaksi.
 *
 * Bertanggung jawab atas CRUD, validasi bisnis, dan reordering kategori.
 */
class TransactionCategoryService
{
    /**
     * Mengambil data kategori transaksi dengan pagination, filter, dan pencarian.
     *
     * @param string|null $search  Kata kunci pencarian (nama atau kode)
     * @param string|null $type    Tipe kategori (INCOME/EXPENSE)
     * @return LengthAwarePaginator
     */
    public function getPaginatedCategories(?string $search = null, ?string $type = null): LengthAwarePaginator
    {
        return TransactionCategory::query()
            ->when($type, fn ($query, $type) => $query->where('type', $type))
            ->when($search, fn ($query, $search) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);
    }

    /**
     * Mengambil semua kode kategori yang sudah ada (untuk validasi duplikat di frontend).
     *
     * @return array<int, string> Array dengan format [id => code]
     */
    public function getExistingCodes(): array
    {
        return TransactionCategory::pluck('code', 'id')->toArray();
    }

    /**
     * Mengambil ID kategori yang sedang digunakan di expense reports.
     *
     * @return array<int> Array berisi ID kategori yang sedang digunakan
     */
    public function getUsedCategoryIds(): array
    {
        return TransactionCategory::has('expenseRecaps')->pluck('id')->toArray();
    }

    /**
     * Membuat kategori transaksi baru dengan auto sort_order.
     *
     * @param array{name: string, code: string, type: string} $data
     * @return TransactionCategory
     */
    public function createCategory(array $data): TransactionCategory
    {
        $maxSortOrder = TransactionCategory::max('sort_order') ?? 0;

        return TransactionCategory::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'type' => $data['type'],
            'sort_order' => $maxSortOrder + 1,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Mengupdate kategori transaksi beserta logika reordering.
     *
     * Jika sort_order berubah, kategori lain akan digeser secara otomatis
     * untuk memastikan tidak ada gap dalam urutan.
     *
     * @param int   $id   ID kategori yang akan diupdate
     * @param array{name: string, code: string, type: string, sort_order: int} $data
     * @return TransactionCategory
     */
    public function updateCategory(int $id, array $data): TransactionCategory
    {
        $category = TransactionCategory::findOrFail($id);
        $newSortOrder = $data['sort_order'];
        $oldSortOrder = $category->sort_order;

        if ($newSortOrder != $oldSortOrder) {
            $this->reorderCategories($id, $oldSortOrder, $newSortOrder);
        }

        $category->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'type' => $data['type'],
            'sort_order' => $newSortOrder,
        ]);

        return $category;
    }

    /**
     * Toggle status aktif/nonaktif kategori.
     *
     * @param int $id ID kategori yang akan di-toggle
     * @return TransactionCategory
     */
    public function toggleStatus(int $id): TransactionCategory
    {
        $category = TransactionCategory::findOrFail($id);
        $category->is_active = !$category->is_active;
        $category->save();

        return $category;
    }

    /**
     * Menghapus beberapa kategori sekaligus (bulk delete) dengan pengecekan constraint.
     *
     * Kategori yang sedang digunakan di expense reports tidak akan dihapus.
     *
     * @param array<int> $selectedIds Array berisi ID kategori yang akan dihapus
     * @return array{deleted: int, used: array<string>}
     */
    public function deleteSelected(array $selectedIds): array
    {
        $usedCategories = TransactionCategory::whereIn('id', $selectedIds)
            ->has('expenseRecaps')
            ->pluck('name')
            ->toArray();

        if (!empty($usedCategories)) {
            return [
                'deleted' => 0,
                'used' => $usedCategories,
            ];
        }

        $deletedCount = TransactionCategory::whereIn('id', $selectedIds)->delete();

        return [
            'deleted' => $deletedCount,
            'used' => [],
        ];
    }

    /**
     * Melakukan reordering kategori lain ketika sort_order berubah.
     *
     * - Jika pindah ke atas (sort_order mengecil): kategori di antara posisi baru dan lama digeser +1
     * - Jika pindah ke bawah (sort_order membesar): kategori di antara posisi lama dan baru digeser -1
     *
     * @param int $categoryId   ID kategori yang sedang diupdate
     * @param int $oldSortOrder Sort order lama
     * @param int $newSortOrder Sort order baru
     * @return void
     */
    private function reorderCategories(int $categoryId, int $oldSortOrder, int $newSortOrder): void
    {
        if ($newSortOrder < $oldSortOrder) {
            TransactionCategory::where('id', '!=', $categoryId)
                ->whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])
                ->increment('sort_order');
        } else {
            TransactionCategory::where('id', '!=', $categoryId)
                ->whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder])
                ->decrement('sort_order');
        }
    }
}
