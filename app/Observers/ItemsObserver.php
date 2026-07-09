<?php

namespace App\Observers;

use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockIn;
use Illuminate\Support\Str;

class ItemsObserver
{
    const OPENING_STOCK_DATE = '1970-01-01';
    const OPENING_STOCK_DESCRIPTION = 'Opening stock (auto)';

    public function created(Items $item): void
    {
        $alreadyExists = ItemStockIn::where('id_item', $item->id_item)
            ->whereDate('date', self::OPENING_STOCK_DATE)
            ->where('notes', self::OPENING_STOCK_DESCRIPTION)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        $qty = (int) ($item->quantity ?? 0);
        if ($qty <= 0) {
            return;
        }

        $idStockIn = 'OPN-' . date('Ymd') . '-' . Str::upper(Str::random(8));

        ItemStockIn::create([
            'id_stock_in' => $idStockIn,
            'id_item' => $item->id_item,
            'quantity' => $qty,
            'capital_price' => (int) ($item->capital_price ?? 0),
            'notes' => self::OPENING_STOCK_DESCRIPTION,
            'date' => self::OPENING_STOCK_DATE,
        ]);
    }
}
