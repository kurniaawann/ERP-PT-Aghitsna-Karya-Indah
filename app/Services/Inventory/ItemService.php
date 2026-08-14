<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Items;
use App\Services\InputNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk mengelola business logic Data Barang.
 *
 * Service ini bertanggung jawab atas operasi CRUD,
 * normalisasi input harga, dan generasi ID otomatis.
 *
 * CATATAN:
 * - getAll() memakai cache 'inventory:items:all'. Cache dibersihkan OTOMATIS lewat
 *   ItemsObserver (event created/updated/deleted) — jadi jangan panggil flushCache()
 *   secara manual kecuali perlu, dan jangan ubah mass delete menjadi query builder.
 * - Semua harga masuk lewat InputNormalizer::normalizeCurrency() agar seragam format.
 */
class ItemService
{
    /**
     * Mendapatkan daftar barang dengan paginasi dan pencarian.
     *
     * @param  string|null  $search  Kata kunci pencarian (nama atau ID barang)
     * @return LengthAwarePaginator
     */
    public function getPaginatedSearch(?string $search): LengthAwarePaginator
    {
        return Items::query()
            ->search($search)
            ->orderBy('id_item', 'desc')
            ->paginate(15);
    }

    /**
     * Mendapatkan seluruh data barang diurutkan berdasarkan ID.
     *
     * Menggunakan cache untuk menghindari query berulang pada dropdown.
     * Cache di-invalidate saat ada create/update/delete item.
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        try {
            return Cache::remember(
                'inventory:items:all',
                now()->addDay(),
                fn () => Items::orderBy('id_item', 'asc')->get()
            );
        } catch (\Exception $e) {
            Log::warning('Cache READ error [inventory:items:all]: ' . $e->getMessage());
            return Items::orderBy('id_item', 'asc')->get();
        }
    }

    /**
     * Invalidate cache daftar barang.
     *
     * @return void
     */
    public function flushCache(): void
    {
        try {
            Cache::forget('inventory:items:all');
        } catch (\Exception $e) {
            Log::warning('Cache DELETE error [inventory:items:all]: ' . $e->getMessage());
        }
    }

    /**
     * Mencari barang berdasarkan ID.
     *
     * @param  string     $idItem
     * @return Items|null
     */
    public function findById(string $idItem): ?Items
    {
        return Items::where('id_item', $idItem)->first();
    }

    /**
     * Menyimpan barang baru dengan ID otomatis dan normalisasi harga.
     *
     * @param  array   $data  Data yang sudah divalidasi
     * @return Items
     */
    public function store(array $data): Items
    {
        return Items::create([
            'id_item' => $this->generateNextId(),
            'name_item' => $data['name_item'],
            'quantity' => $data['quantity'],
            'capital_price' => InputNormalizer::normalizeCurrency($data['capital_price']),
            'selling_price' => InputNormalizer::normalizeCurrency($data['selling_price']),
            'keterangan' => $data['keterangan'] ?? null,
        ]);
    }

    /**
     * Memperbarui data barang yang sudah ada.
     *
     * @param  Items  $item  Model barang yang akan diperbarui
     * @param  array  $data  Data yang sudah divalidasi
     * @return bool
     */
    public function update(Items $item, array $data): bool
    {
        return $item->update([
            'name_item' => $data['name_item'],
            'quantity' => $data['quantity'],
            'capital_price' => InputNormalizer::normalizeCurrency($data['capital_price']),
            'selling_price' => InputNormalizer::normalizeCurrency($data['selling_price']),
            'keterangan' => $data['keterangan'] ?? null,
        ]);
    }

    /**
     * Menghapus beberapa barang sekaligus (bulk delete).
     *
     * PENTING (kenapa pakai foreach, bukan Items::whereIn(...)->delete()):
     * - whereIn()->delete() adalah "mass delete" yang TIDAK memicu event model.
     * - foreach + $item->delete() memicu event 'deleted' → ItemsObserver::deleted()
     *   berjalan → cache 'inventory:items:all' otomatis ter-bersihkan.
     * - Jika pakai mass delete, cache akan menyimpan data lama yang sudah tidak ada di DB.
     *
     * @param  array  $ids  Daftar id_item yang akan dihapus
     * @return int  Jumlah record yang dihapus
     */
    public function destroySelected(array $ids): int
    {
        $items = Items::whereIn('id_item', $ids)->get();

        foreach ($items as $item) {
            $item->delete();
        }

        return $items->count();
    }

    /**
     * Generate ID barang berikutnya.
     *
     * @return string
     */
    private function generateNextId(): string
    {
        return Items::generateNextId();
    }
}
