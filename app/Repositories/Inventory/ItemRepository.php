<?php

namespace App\Repositories\Inventory;

use App\Models\Inventory\Items;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ItemRepository
{
    public function search(?string $search): LengthAwarePaginator
    {
        return Items::query()
            ->when($search, fn ($query, $search) => $query->where(function ($q) use ($search) {
                $q->where('name_item', 'like', "%{$search}%")
                  ->orWhere('id_item', 'like', "%{$search}%");
            }))
            ->orderBy('id_item', 'desc')
            ->paginate(15);
    }

    public function getAllOrderedById(): \Illuminate\Database\Eloquent\Collection
    {
        return Items::orderBy('id_item', 'asc')->get();
    }

    public function findById(string $idItem): ?Items
    {
        return Items::where('id_item', $idItem)->first();
    }

    public function create(array $data): Items
    {
        return Items::create($data);
    }

    public function update(Items $item, array $data): bool
    {
        return $item->update($data);
    }
}
