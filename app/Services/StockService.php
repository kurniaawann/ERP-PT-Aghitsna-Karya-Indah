<?php

namespace App\Services;

use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockIn;
use App\Models\Inventory\ItemStockOut;
use App\Models\Inventory\ItemReturn;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StockService
{
    /**
     * Mendapatkan seluruh data Barang Keluar diurutkan berdasarkan ID descending.
     *
     * Menggunakan cache untuk dropdown di halaman Pengembalian Barang.
     * Cache di-invalidate saat ada transaksi stock-out.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllStockOuts()
    {
        try {
            return Cache::remember(
                'inventory:stock-outs:all',
                now()->addHour(),
                fn () => ItemStockOut::orderBy('id_stock_out', 'desc')->get()
            );
        } catch (\Exception $e) {
            Log::warning('Cache READ error [inventory:stock-outs:all]: ' . $e->getMessage());
            return ItemStockOut::orderBy('id_stock_out', 'desc')->get();
        }
    }

    /**
     * Invalidate cache stock-out, stock-in, dan stock report.
     *
     * @return void
     */
    public function flushCache(): void
    {
        try {
            Cache::forget('inventory:stock-outs:all');
            Cache::forget('inventory:stock-ins:all');
        } catch (\Exception $e) {
            Log::warning('Cache DELETE error [inventory:stock]: ' . $e->getMessage());
        }
    }

    /**
     * Increase stock quantities for items array (used when restoring stock on delete).
     * Each item array is expected to have 'from_stock', 'id_item', and 'quantity'.
     */
    public function increaseStockFromItems(array $items): void
    {
        foreach ($items as $item) {
            $isFromStock = isset($item['from_stock']) && ($item['from_stock'] === true || $item['from_stock'] === 'true' || $item['from_stock'] === 1 || $item['from_stock'] === '1');

            if (!$isFromStock || empty($item['id_item'])) {
                continue;
            }

            $stockItem = Items::lockForUpdate()->where('id_item', $item['id_item'])->first();

            if (!$stockItem) {
                continue;
            }

            $stockItem->quantity += (int) ($item['quantity'] ?? 0);
            $stockItem->save();
        }
    }

    /**
     * Decrease stock quantities for items array (used when consuming stock on create/update).
     * Throws RuntimeException if stock insufficient or item not found.
     */
    public function decreaseStockFromItems(array $items): void
    {
        foreach ($items as $item) {
            $isFromStock = isset($item['from_stock']) && ($item['from_stock'] === true || $item['from_stock'] === 'true' || $item['from_stock'] === 1 || $item['from_stock'] === '1');

            if (!$isFromStock || empty($item['id_item'])) {
                continue;
            }

            $stockItem = Items::lockForUpdate()->where('id_item', $item['id_item'])->first();

            if (!$stockItem) {
                throw new \RuntimeException('Barang dengan ID "' . $item['id_item'] . '" tidak ditemukan!');
            }

            $qty = (int) ($item['quantity'] ?? 0);

            if ($stockItem->quantity < $qty) {
                throw new \RuntimeException('Stok barang "' . $stockItem->name_item . '" tidak cukup.');
            }

            $stockItem->quantity -= $qty;
            $stockItem->save();
        }
    }

    /**
     * Process deletion of a stock-in record: adjust item quantity and recalculate capital price, then delete the stock-in.
     * Assumes caller manages DB transaction if needed.
     */
    public function processStockInDeletion(ItemStockIn $stockIn): void
    {
        $item = Items::lockForUpdate()->find($stockIn->id_item);

        if (!$item) {
            // nothing to do
            $stockIn->delete();
            return;
        }

        // Calculate remaining value after removing this stock-in
        $currentItemValue = $item->quantity * $item->capital_price;
        $removedValue = $stockIn->quantity * $stockIn->capital_price;
        $remainingValue = $currentItemValue - $removedValue;

        // Reduce item quantity
        $item->quantity -= $stockIn->quantity;
        if ($item->quantity < 0) {
            $item->quantity = 0;
        }

        // Recalculate average price based on remaining stock
        if ($item->quantity > 0) {
            $item->capital_price = (int) round($remainingValue / $item->quantity);
        }

        $item->save();

        // delete the stock-in record
        $stockIn->delete();

        $this->flushCache();
    }

    /**
     * Process deletion of an ItemReturn record. Handles both 'masuk' and 'keluar' return types.
     * Assumes caller manages DB transaction if needed.
     */
    public function processReturnDeletion(ItemReturn $return): void
    {
        $item = Items::lockForUpdate()->find($return->id_item);

        if (!$item) {
            $return->delete();
            return;
        }

        if ($return->return_type === 'masuk') {
            $stockIn = ItemStockIn::lockForUpdate()->find($return->id_stock_in);

            // restore item quantity and stockIn quantity
            $item->quantity += $return->quantity;
            if ($stockIn) {
                $stockIn->quantity += $return->quantity;
            }

            // Recalculate weighted average cost
            $currentItemValue = (($item->quantity - $return->quantity) * $item->capital_price);
            $restoredValue = $return->quantity * ($stockIn?->capital_price ?? 0);
            $newValue = $currentItemValue + $restoredValue;
            $newQuantity = $item->quantity;

            if ($newQuantity > 0) {
                $item->capital_price = (int) round($newValue / $newQuantity);
            } else {
                $item->capital_price = 0;
            }

            if ($stockIn) {
                $stockIn->save();
            }
        } else {
            // return_type === 'keluar'
            $item->quantity -= $return->quantity;
            if ($item->quantity < 0) {
                $item->quantity = 0;
            }

            $stockOut = ItemStockOut::lockForUpdate()->find($return->id_stock_out);
            if ($stockOut) {
                $stockOut->quantity += $return->quantity;
                $stockOut->save();
            }
        }

        $item->save();
        $return->delete();

        $this->flushCache();
    }
}
