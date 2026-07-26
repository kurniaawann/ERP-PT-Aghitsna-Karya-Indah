<?php

namespace App\Observers;

use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockIn;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Observer untuk Model Items.
 *
 * Menangani event created untuk membuat catatan stok awal
 * (opening stock) secara otomatis ketika barang baru ditambahkan.
 */
class ItemsObserver
{
    /**
     * Tanggal stok awal (1970-01-01) sebagai penanda data inisial.
     */
    const OPENING_STOCK_DATE = '1970-01-01';

    /**
     * Deskripsi/catatan untuk stok awal.
     */
    const OPENING_STOCK_DESCRIPTION = 'Opening stock (auto)';

    /**
     * Dipanggil saat barang baru berhasil dibuat.
     *
     * Membuat catatan ItemStockIn dengan ID awalan OPN- sebagai
     * stok awal barang, asalkan belum ada catatan serupa.
     *
     * @param  Items  $item
     * @return void
     */
    public function created(Items $item): void
    {
        Cache::forget('inventory:items:all');

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

    /**
     * Dipanggil saat barang diperbarui.
     *
     * @param  Items  $item
     * @return void
     */
    public function updated(Items $item): void
    {
        Cache::forget('inventory:items:all');
    }

    /**
     * Dipanggil saat barang dihapus.
     *
     * @param  Items  $item
     * @return void
     */
    public function deleted(Items $item): void
    {
        Cache::forget('inventory:items:all');
    }
}
