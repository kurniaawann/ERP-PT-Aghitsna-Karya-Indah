<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Items;
use App\Repositories\Inventory\ItemRepository;
use App\Services\InputNormalizer;

class ItemService
{
    public function __construct(
        private readonly ItemRepository $repository
    ) {}

    public function getPaginatedSearch(?string $search): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->repository->search($search);
    }

    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getAllOrderedById();
    }

    public function findById(string $idItem): ?Items
    {
        return $this->repository->findById($idItem);
    }

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

    public function update(Items $item, array $data): bool
    {
        return $this->repository->update($item, [
            'name_item' => $data['name_item'],
            'quantity' => $data['quantity'],
            'capital_price' => InputNormalizer::normalizeCurrency($data['capital_price']),
            'selling_price' => InputNormalizer::normalizeCurrency($data['selling_price']),
        ]);
    }

    private function generateNextId(): string
    {
        return Items::generateNextId();
    }
}
