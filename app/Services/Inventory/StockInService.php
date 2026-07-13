<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockIn;
use App\Services\InputNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk mengelola business logic Barang Masuk (Stock In).
 *
 * Menangani operasi CRUD, perhitungan rata-rata berbobot (weighted average),
 * dan manajemen stok barang terkait penerimaan barang.
 */
class StockInService
{
    /**
     * Generate ID Barang Masuk berikutnya dengan format SIN-YYYYMMDD-XXXX.
     *
     * Menggunakan lockForUpdate untuk mencegah race condition pada
     * generate ID secara concurrent.
     *
     * @return string
     */
    public function generateNextId(): string
    {
        $lastRecord = ItemStockIn::lockForUpdate()
            ->orderBy('id_stock_in', 'desc')
            ->first();

        if (!$lastRecord) {
            return 'SIN-' . date('Ymd') . '-0001';
        }

        $lastNumber = (int) substr($lastRecord->id_stock_in, -4);

        return 'SIN-' . date('Ymd') . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Menyimpan data Barang Masuk baru beserta penyesuaian stok.
     *
     * Setiap item akan:
     * 1. Mencari barang yang sudah ada atau membuat baru
     * 2. Menambah quantity barang
     * 3. Menghitung ulang rata-rata berbobot (weighted average) harga modal
     * 4. Membuat catatan ItemStockIn
     *
     * @param  array   $items    Data items yang sudah dinormalisasi
     * @param  string  $date     Tanggal penerimaan
     * @param  string|null $notes Keterangan
     * @return void
     *
     * @throws \Exception
     */
    public function store(array $items, string $date, ?string $notes): void
    {
        DB::beginTransaction();
        try {
            foreach ($items as $itemData) {
                if (empty($itemData['name_item']) || empty($itemData['quantity']) || $itemData['quantity'] < 1) {
                    DB::rollBack();
                    throw new \InvalidArgumentException('Semua item harus memiliki nama dan quantity minimal 1!');
                }

                $idItem = $itemData['id_item'] ?? null;
                $quantity = (int) $itemData['quantity'];
                $capitalPrice = InputNormalizer::normalizeCurrency($itemData['capital_price'] ?? 0);
                $fromStock = $itemData['from_stock'] ?? false;

                $item = $this->resolveItem($idItem, $itemData['name_item'] ?? null, $capitalPrice, $fromStock);

                $this->adjustItemStock($item, $quantity, $capitalPrice);

                ItemStockIn::create([
                    'id_stock_in' => $this->generateNextId(),
                    'id_item' => $item->id_item,
                    'quantity' => $quantity,
                    'capital_price' => $capitalPrice,
                    'notes' => $notes,
                    'date' => $date,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Memperbarui data Barang Masuk beserta penyesuaian stok.
     *
     * Menangani dua skenario:
     * 1. Item yang sama - sesuaikan selisih quantity dan harga modal
     * 2. Item berbeda - kurangi stok item lama, tambah stok item baru
     *
     * @param  ItemStockIn  $stockIn   Record Barang Masuk yang akan diupdate
     * @param  array        $itemData  Data item baru
     * @param  string       $date      Tanggal baru
     * @param  string|null  $notes     Keterangan baru
     * @return void
     *
     * @throws \Exception
     */
    public function update(ItemStockIn $stockIn, array $itemData, string $date, ?string $notes): void
    {
        $newQuantity = (int) $itemData['quantity'];
        $newCapitalPrice = InputNormalizer::normalizeCurrency($itemData['capital_price'] ?? 0);
        $newItemId = $itemData['id_item'] ?? null;
        $newItemName = $itemData['name_item'] ?? null;

        DB::beginTransaction();
        try {
            $stockIn = ItemStockIn::lockForUpdate()->findOrFail($stockIn->id_stock_in);
            $oldItem = Items::lockForUpdate()->find($stockIn->id_item);

            if ($newItemId && $newItemId === $stockIn->id_item) {
                // Item yang sama - sesuaikan selisih
                $this->adjustSameItemStock($oldItem, $stockIn, $newQuantity, $newCapitalPrice);
            } else {
                // Item berbeda - kurangi item lama, tambah item baru
                $newItem = $this->resolveNewItemForUpdate($newItemId, $newItemName, $newCapitalPrice);
                $this->swapItemStock($oldItem, $newItem, $stockIn, $newQuantity, $newCapitalPrice);
                $stockIn->id_item = $newItem->id_item;
            }

            $stockIn->update([
                'quantity' => $newQuantity,
                'capital_price' => $newCapitalPrice,
                'notes' => $notes,
                'date' => $date,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ─── Private Helper Methods ────────────────────────────────────────

    /**
     * Menentukan item yang akan digunakan (existing atau baru).
     *
     * @param  string|null  $idItem
     * @param  string|null  $itemName
     * @param  int          $capitalPrice
     * @param  bool         $fromStock
     * @return Items
     *
     * @throws \Exception
     */
    private function resolveItem(?string $idItem, ?string $itemName, int $capitalPrice, bool $fromStock): Items
    {
        if ($fromStock && $idItem) {
            $item = Items::lockForUpdate()->find($idItem);
            if (!$item) {
                throw new \Exception('Barang dengan ID ' . $idItem . ' tidak ditemukan!');
            }
            return $item;
        }

        $item = $idItem ? Items::lockForUpdate()->find($idItem) : null;

        if (!$item) {
            $item = Items::create([
                'id_item' => Items::generateNextId(),
                'name_item' => $itemName,
                'quantity' => 0,
                'capital_price' => $capitalPrice,
                'selling_price' => 0,
            ]);
        }

        return $item;
    }

    /**
     * Menyesuaikan stok barang dengan weighted average price.
     *
     * @param  Items  $item
     * @param  int    $quantity
     * @param  int    $capitalPrice
     * @return void
     */
    private function adjustItemStock(Items $item, int $quantity, int $capitalPrice): void
    {
        $existingValue = $item->quantity * $item->capital_price;
        $newValue = $quantity * $capitalPrice;
        $totalQuantity = $item->quantity + $quantity;
        $newAveragePrice = $totalQuantity > 0
            ? (int) round(($existingValue + $newValue) / $totalQuantity)
            : $capitalPrice;

        $item->quantity += $quantity;
        $item->capital_price = $newAveragePrice;
        $item->save();
    }

    /**
     * Menyesuaikan stok barang yang sama saat update (selisih quantity).
     *
     * @param  Items        $item
     * @param  ItemStockIn  $stockIn
     * @param  int          $newQuantity
     * @param  int          $newCapitalPrice
     * @return void
     */
    private function adjustSameItemStock(Items $item, ItemStockIn $stockIn, int $newQuantity, int $newCapitalPrice): void
    {
        $qtyDifference = $newQuantity - $stockIn->quantity;

        $oldValue = $stockIn->quantity * $stockIn->capital_price;
        $currentItemValue = $item->quantity * $item->capital_price;
        $itemValueWithoutThisStockIn = $currentItemValue - $oldValue;

        $newValue = $newQuantity * $newCapitalPrice;
        $totalQuantity = ($item->quantity - $stockIn->quantity) + $newQuantity;
        $newAveragePrice = $totalQuantity > 0
            ? (int) round(($itemValueWithoutThisStockIn + $newValue) / $totalQuantity)
            : $newCapitalPrice;

        $item->quantity += $qtyDifference;
        $item->capital_price = $newAveragePrice;
        $item->save();
    }

    /**
     * Menukar stok dari item lama ke item baru saat update.
     *
     * @param  Items        $oldItem
     * @param  Items        $newItem
     * @param  ItemStockIn  $stockIn
     * @param  int          $newQuantity
     * @param  int          $newCapitalPrice
     * @return void
     */
    private function swapItemStock(Items $oldItem, Items $newItem, ItemStockIn $stockIn, int $newQuantity, int $newCapitalPrice): void
    {
        // Kurangi stok item lama
        $oldValue = $stockIn->quantity * $stockIn->capital_price;
        $currentItemValue = $oldItem->quantity * $oldItem->capital_price;
        $itemValueWithoutThisStockIn = $currentItemValue - $oldValue;

        $oldItem->quantity -= $stockIn->quantity;
        if ($oldItem->quantity < 0) {
            $oldItem->quantity = 0;
        }

        if ($oldItem->quantity > 0) {
            $oldItem->capital_price = (int) round($itemValueWithoutThisStockIn / $oldItem->quantity);
        } else {
            $oldItem->capital_price = 0;
        }
        $oldItem->save();

        // Tambah stok item baru
        $this->adjustItemStock($newItem, $newQuantity, $newCapitalPrice);
    }

    /**
     * Menentukan item baru saat update dengan item berbeda.
     *
     * @param  string|null  $newItemId
     * @param  string|null  $newItemName
     * @param  int          $newCapitalPrice
     * @return Items
     *
     * @throws \Exception
     */
    private function resolveNewItemForUpdate(?string $newItemId, ?string $newItemName, int $newCapitalPrice): Items
    {
        if ($newItemId) {
            $newItem = Items::lockForUpdate()->find($newItemId);
            if (!$newItem) {
                throw new \Exception('Barang dengan ID ' . $newItemId . ' tidak ditemukan!');
            }
            return $newItem;
        }

        if ($newItemName) {
            $newItem = Items::where('name_item', $newItemName)->first();
            if (!$newItem) {
                $newItem = Items::create([
                    'id_item' => Items::generateNextId(),
                    'name_item' => $newItemName,
                    'quantity' => 0,
                    'capital_price' => $newCapitalPrice,
                    'selling_price' => 0,
                ]);
            }
            return $newItem;
        }

        throw new \Exception('Gagal menentukan item untuk diupdate!');
    }
}
