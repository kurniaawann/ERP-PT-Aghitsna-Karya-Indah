<?php

namespace App\Services\Report;

use App\Models\Report\TransactionCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
     * Logika:
     * - when($type) menambah filter type hanya jika tidak kosong; when($search)
     *   membungkus pencarian dalam closure agar LIKE name/code digabung dengan
     *   OR dalam satu grup (tidak membatalkan filter type).
     * - when($module) menambah filter module (expense_recap / project_finance).
     * - Urutan tampil: sort_order naik (urutan yang bisa diubah user) lalu name
     *   sebagai tie-breaker agar deterministik saat sort_order sama.
     *
     * @param string|null $search  Kata kunci pencarian (nama atau kode)
     * @param string|null $type    Tipe kategori (INCOME/EXPENSE)
     * @param string|null $module  Modul kategori (expense_recap / project_finance)
     * @return LengthAwarePaginator
     */
    public function getPaginatedCategories(?string $search = null, ?string $type = null, ?string $module = null): LengthAwarePaginator
{
    return TransactionCategory::query()
        ->where('created_by', auth()->id())
        ->when($type, fn ($query, $type) => $query->where('type', $type))
        ->when($module, fn ($query, $module) => $query->where('module', $module))
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
     * Logika:
     * - pluck('code', 'id') menghasilkan map [id => code] sekali query, lalu
     *   di-cache 1 hari dengan key 'report:category-codes'.
     * - Dipakai frontend untuk menolak kode duplikat tanpa reload server.
     * - Jika cache error, fallback query langsung agar validasi tetap jalan.
     *
     * @return array<int, string> Array dengan format [id => code]
     */
    public function getExistingCodes(): array
    {
        try {
            return (array) Cache::remember(
                'report:category-codes',
                now()->addDay(),
                fn () => TransactionCategory::pluck('code', 'id')->toArray()
            );
        } catch (\Exception $e) {
            Log::warning('Cache READ error [report:category-codes]: ' . $e->getMessage());
            return TransactionCategory::pluck('code', 'id')->toArray();
        }
    }

    /**
     * Mengambil ID kategori yang sedang digunakan di expense reports atau
     * Laporan Keuangan Proyek.
     *
     * Logika:
     * - whereHas('expenseRecaps') menghasilkan subquery EXISTS — hanya kategori
     *   yang punya minimal satu expense recap milik user saat ini yang diambil.
     *   Scoping per-user konsisten dengan buildFilteredQuery milik
     *   RecapExpenseService: setiap user hanya melihat rekap pengeluaran
     *   (created_by = user), termasuk rekap otomatis yang kini mencatat pemilik.
     * - whereHas('projectFinancialReportItems') menangkap kategori yang dipakai
     *   item "Bon" Laporan Keuangan Proyek — termasuk kategori UANG_MASUK yang
     *   dibuat otomatis saat bukti pembayaran dibuat. Kategori ini tidak boleh
     *   dihapus selama masih ada transaksi yang menggunakannya; hanya bisa
     *   hilang bila bukti pembayaran dihapus di modul Bukti Pembayaran.
     * - Cache 1 JAM (lebih pendek dari kategori) karena data ini berubah setiap
     *   ada transaksi baru, bukan hanya saat CRUD kategori.
     *
     * @return array<int> Array berisi ID kategori yang sedang digunakan
     */
    public function getUsedCategoryIds(): array
    {
        $cacheKey = 'report:category-used-ids:' . auth()->id();

        try {
            return (array) Cache::remember(
                $cacheKey,
                now()->addHour(),
                fn () => $this->resolveUsedCategoryIds()
            );
        } catch (\Exception $e) {
            Log::warning('Cache READ error [' . $cacheKey . ']: ' . $e->getMessage());
            return $this->resolveUsedCategoryIds();
        }
    }

    /**
     * Menentukan ID kategori yang sedang dipakai transaksi (expense recap
     * maupun item Laporan Keuangan Proyek) milik user yang sedang login.
     *
     * @return array<int> Array berisi ID kategori yang sedang digunakan
     */
    private function resolveUsedCategoryIds(): array
    {
        $expenseCategoryIds = TransactionCategory::whereHas('expenseRecaps', function ($query) {
            $query->where('created_by', auth()->id());
        })->pluck('id');

        $pfrCategoryIds = TransactionCategory::whereHas('projectFinancialReportItems', function ($query) {
            $query->where('created_by', auth()->id());
        })->pluck('id');

        return $expenseCategoryIds
            ->merge($pfrCategoryIds)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Invalidate semua cache kategori transaksi.
     *
     * Menghapus key per-user milik modul Report (expense-categories,
     * category-lookup, category-used-ids) plus key per-user milik modul Finance
     * (finance:expense-categories) untuk user yang bersangkutan, dan key global
     * (category-codes). Wajib dipanggil di setiap operasi yang mengubah data
     * kategori atau rekap pengeluaran agar modul tidak menyajikan data basi.
     *
     * @param  string|null $userId  ID user yang cache-nya di-invalidate.
     *                              Default: user yang sedang login.
     * @return void
     */
    public function flushCache(?string $userId = null): void
    {
        $userId = $userId ?? auth()->id();

        try {
            Cache::forget('report:expense-categories:' . $userId);
            Cache::forget('report:category-lookup:' . $userId);
            Cache::forget('report:category-codes');
            Cache::forget('report:category-used-ids:' . $userId);
            Cache::forget('finance:expense-categories:' . $userId);
            Cache::forget('finance:project-finance-categories:' . $userId);
        } catch (\Exception $e) {
            Log::warning('Cache DELETE error [report:categories]: ' . $e->getMessage());
        }
    }

    /**
     * Membuat kategori transaksi baru dengan auto sort_order.
     *
     * Logika:
     * - sort_order di-generate otomatis = MAX(sort_order) + 1 sehingga kategori
     *   baru selalu menempel di posisi terakhir tanpa input manual.
     * - Default is_active = true dan created_by = user saat ini.
     * - Pemanggil wajib memanggil flushCache() setelah operasi ini.
     *
     * @param array{name: string, code: string, type: string, module?: string} $data
     * @return TransactionCategory
     */
    public function createCategory(array $data): TransactionCategory
    {
        $maxSortOrder = TransactionCategory::max('sort_order') ?? 0;

        return TransactionCategory::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'type' => $data['type'],
            'module' => $data['module'] ?? TransactionCategory::MODULE_EXPENSE_RECAP,
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
     * Logika:
     * - Urutan langkah: geser kategori lain DULU (reorderCategories) baru update
     *   kategori ini — menghindari tabrakan nilai sort_order sesaat.
     * - Pemanggil wajib memanggil flushCache() setelah operasi ini.
     *
     * @param int   $id   ID kategori yang akan diupdate
     * @param array{name: string, code: string, type: string, module?: string, sort_order: int} $data
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
            'module' => $data['module'] ?? $category->module,
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
     * Kategori yang sedang digunakan di expense reports atau item Laporan
     * Keuangan Proyek tidak akan dihapus. Ini melindungi kategori UANG_MASUK
     * yang dibuat otomatis saat bukti pembayaran dibuat — kategori tersebut
     * baru bisa dihapus setelah bukti pembayaran (beserta item otomatisnya)
     * dihapus di modul Bukti Pembayaran.
     *
     * Logika:
     * - Pengecekan constraint dilakukan SATU query (whereIn + has) di awal;
     *   jika ada kategori terpakai, seluruh proses dibatalkan (deleted = 0)
     *   dan nama-nama kategori dikembalikan untuk ditampilkan ke user.
     * - Hanya jika tidak ada yang terpakai, mass delete dijalankan.
     * - Pemanggil wajib memanggil flushCache() setelah operasi ini.
     *
     * @param  array<int> $selectedIds Array berisi ID kategori yang akan dihapus
     * @return array{deleted: int, used: array<string>}
     */
    public function deleteSelected(array $selectedIds): array
    {
        $usedCategories = TransactionCategory::whereIn('id', $selectedIds)
            ->where(function ($query) {
                $query->whereHas('expenseRecaps', function ($q) {
                    $q->where('created_by', auth()->id());
                })->orWhereHas('projectFinancialReportItems', function ($q) {
                    $q->where('created_by', auth()->id());
                });
            })
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
