<?php

namespace App\Repositories\Inventory;

use App\Models\Inventory\Items;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository untuk akses data Item (Barang).
 *
 * Menangani query database terkait data barang menggunakan Eloquent.
 * Scope pencarian delegasi ke Model scope untuk menghindari duplikasi.
 */
class ItemRepository
{
    /**
     * Mencari barang dengan paginasi berdasarkan kata kunci.
     * Menggunakan scope search dari Model Items.
     *
     * @param  string|null            $search
     * @return LengthAwarePaginator
     */
    public function search(?string $search): LengthAwarePaginator
    {
        return Items::query()
            ->search($search)
            ->orderBy('id_item', 'desc')
            ->paginate(15);
    }

    /**
     * Mendapatkan seluruh data barang diurutkan berdasarkan ID ascending.
     *
     * @return Collection
     */
    public function getAllOrderedById(): Collection
    {
        return Items::orderBy('id_item', 'asc')->get();
    }

    /**
     * Mencari satu barang berdasarkan ID.
     *
     * @param  string      $idItem
     * @return Items|null
     */
    public function findById(string $idItem): ?Items
    {
        return Items::where('id_item', $idItem)->first();
    }

    /**
     * Membuat data barang baru.
     *
     * @param  array  $data
     * @return Items
     */
    public function create(array $data): Items
    {
        return Items::create($data);
    }

    /**
     * Memperbarui data barang yang sudah ada.
     *
     * @param  Items  $item
     * @param  array  $data
     * @return bool
     */
    public function update(Items $item, array $data): bool
    {
        return $item->update($data);
    }
}
