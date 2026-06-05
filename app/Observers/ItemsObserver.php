<?php

namespace App\Observers;

use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockIn;
use Illuminate\Support\Str;

class ItemsObserver
{
    /**
     * Handle the Items "created" event.
     *
     * Auto-create opening stock (stok awal) as an ItemStockIn record so that
     * StockReportService (which relies on item_stock_ins) can calculate beginning stock.
     */
    public function created(Items $item): void
    {
        // Prevent duplicate opening stock inserts (in case of re-seeding / manual creation).
        // We tag opening stock using a fixed keterangan and tanggal.
        $openingTanggal = '1970-01-01';
        $openingKeterangan = 'Opening stock (auto)';

        $alreadyExists = ItemStockIn::where('id_item', $item->id_item)
            ->whereDate('tanggal', $openingTanggal)
            ->where('keterangan', $openingKeterangan)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        // Only insert opening stock if quantity > 0 (avoid clutter).
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
            'keterangan' => $openingKeterangan,
            'tanggal' => $openingTanggal,
        ]);
    }
}
