<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Items;
use App\Repositories\Inventory\ItemRepository;
use App\Services\InputNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Service untuk mengelola business logic Data Barang.
 *
 * Service ini bertanggung jawab atas operasi CRUD,
 * normalisasi input harga, dan generasi ID otomatis.
 */
class ItemService
{
    public function __construct(
        private readonly ItemRepository $repository
    ) {}

    /**
     * Mendapatkan daftar barang dengan paginasi dan pencarian.
     *
     * @param  string|null  $search  Kata kunci pencarian (nama atau ID barang)
     * @return LengthAwarePaginator
     */
    public function getPaginatedSearch(?string $search): LengthAwarePaginator
    {
        return $this->repository->search($search);
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
        return Cache::remember(
            'inventory:items:all',
            now()->addDay(),
            fn () => $this->repository->getAllOrderedById()
        );
    }

    /**
     * Mencari barang berdasarkan ID.
     *
     * @param  string     $idItem
     * @return Items|null
     */
    public function findById(string $idItem): ?Items
    {
        return $this->repository->findById($idItem);
    }

    /**
     * Menyimpan barang baru dengan ID otomatis dan normalisasi harga.
     *
     * @param  array   $data  Data yang sudah divalidasi
     * @return Items
     */
    public function store(array $data): Items
    {
        return $this->repository->create([
            'id_item' => $this->generateNextId(),
            'name_item' => $data['name_item'],
            'quantity' => $data['quantity'],
            'capital_price' => InputNormalizer::normalizeCurrency($data['capital_price']),
            'selling_price' => InputNormalizer::normalizeCurrency($data['selling_price']),
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
        return $this->repository->update($item, [
            'name_item' => $data['name_item'],
            'quantity' => $data['quantity'],
            'capital_price' => InputNormalizer::normalizeCurrency($data['capital_price']),
            'selling_price' => InputNormalizer::normalizeCurrency($data['selling_price']),
        ]);
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
