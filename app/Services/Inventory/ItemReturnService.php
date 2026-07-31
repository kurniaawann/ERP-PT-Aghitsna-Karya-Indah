<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockOut;
use App\Models\Inventory\ItemStockIn;
use App\Models\Inventory\ItemReturn;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk business logic Pengembalian Barang (Item Return).
 *
 * Menangani:
 * - Pembuatan return baru (tipe masuk & keluar)
 * - Update return yang sudah ada
 * - Generasi ID return
 * - Kalkulasi ulang capital price
 */
class ItemReturnService
{
    /**
     * Generate ID return baru dengan format RTN-YYYYMMDD-NNNN.
     *
     * @return string
     */
    public function generateIdReturn(): string
    {
        $lastRecord = ItemReturn::orderBy('id_return', 'desc')->first();

        if (!$lastRecord) {
            return 'RTN-' . date('Ymd') . '-0001';
        }

        $lastNumber = (int) substr($lastRecord->id_return, -4);
        return 'RTN-' . date('Ymd') . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Membuat record pengembalian baru dan menyesuaikan stok.
     *
     * Tipe 'masuk': mengurangi stok item & stok barang masuk, menghitung ulang capital price.
     * Tipe 'keluar': menambah stok item, mengurangi stok barang keluar.
     *
     * @param  array<string, mixed> $data Data yang sudah divalidasi
     * @return \App\Models\Inventory\ItemReturn
     *
     * @throws \RuntimeException Jumlah return melebihi stok yang tersedia.
     */
    public function createReturn(array $data): ItemReturn
    {
        $returnType = $data['return_type'];

        return DB::transaction(function () use ($data, $returnType) {
            $item = Items::lockForUpdate()->findOrFail($data['id_item']);

            if ($returnType === 'masuk') {
                return $this->createMasukReturn($data, $item);
            }

            return $this->createKeluarReturn($data, $item);
        });
    }

    /**
     * Update record pengembalian yang sudah ada dan menyesuaikan stok.
     *
     * @param  \App\Models\Inventory\ItemReturn $return
     * @param  array<string, mixed> $data Data yang sudah divalidasi
     * @return \App\Models\Inventory\ItemReturn
     *
     * @throws \RuntimeException Jumlah return melebihi stok yang tersedia.
     */
    public function updateReturn(ItemReturn $return, array $data): ItemReturn
    {
        return DB::transaction(function () use ($return, $data) {
            $item = Items::lockForUpdate()->findOrFail($return->id_item);

            if ($return->return_type === 'masuk') {
                return $this->updateMasukReturn($return, $data, $item);
            }

            return $this->updateKeluarReturn($return, $data, $item);
        });
    }

    /**
     * Membuat return tipe masuk (barang dikembalikan ke supplier).
     *
     * Logika:
     * - Stok barang DIKURANGI karena barang keluar dari gudang kembali ke supplier.
     * - Stok stock-in (catatan barang masuk) juga DIKURANGI.
     * - Harga modal rata-rata dihitung ulang: nilai stok sebelum retur dikurangi
     *   nilai barang yang diretur (quantity × harga modal stock-in terkait).
     * - Max(0, ...) mencegah stok negatif jika data lama tidak konsisten.
     *
     * @param  array<string, mixed> $data
     * @param  \App\Models\Inventory\Items $item
     * @return \App\Models\Inventory\ItemReturn
     */
    private function createMasukReturn(array $data, Items $item): ItemReturn
    {
        $stockIn = ItemStockIn::lockForUpdate()->findOrFail($data['id_stock_in']);

        if ($data['quantity'] > $stockIn->quantity) {
            throw new \RuntimeException(
                "Jumlah return melebihi jumlah barang masuk! (Tersedia: " . $stockIn->quantity . ")"
            );
        }

        $return = ItemReturn::create([
            'id_return' => $this->generateIdReturn(),
            'id_item' => $data['id_item'],
            'id_stock_in' => $data['id_stock_in'],
            'quantity' => $data['quantity'],
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'return_type' => 'masuk',
            'date' => $data['date'],
        ]);

        $item->quantity -= $data['quantity'];
        if ($item->quantity < 0) {
            $item->quantity = 0;
        }

        $stockIn->quantity -= $data['quantity'];
        if ($stockIn->quantity < 0) {
            $stockIn->quantity = 0;
        }

        $currentItemValue = (($item->quantity + $data['quantity']) * $item->capital_price);
        // Nilai stok SEBELUM retur = ($item->quantity + qty_retur) * harga_modal.
        // Lalu kurangi nilai barang yang diretur (qty_retur × harga stock-in asal).
        $returnedValue = $data['quantity'] * $stockIn->capital_price;
        $newValue = $currentItemValue - $returnedValue;

        $item->capital_price = $item->quantity > 0 ? (int) round($newValue / $item->quantity) : 0;

        $stockIn->save();
        $item->save();

        return $return;
    }

    /**
     * Membuat return tipe keluar (barang dikembalikan dari proyek/konsumen).
     *
     * Logika:
     * - Stok barang DITAMBAH karena barang kembali masuk ke gudang.
     * - Stok stock-out (catatan barang keluar) DIKURANGI karena pengeluarannya dibatalkan.
     * - Tidak ada hitung ulang harga modal karena tidak ada nilai baru yang masuk ke gudang.
     *
     * @param  array<string, mixed> $data
     * @param  \App\Models\Inventory\Items $item
     * @return \App\Models\Inventory\ItemReturn
     */
    private function createKeluarReturn(array $data, Items $item): ItemReturn
    {
        $stockOut = ItemStockOut::lockForUpdate()->findOrFail($data['id_stock_out']);

        if ($data['quantity'] > $stockOut->quantity) {
            throw new \RuntimeException(
                "Jumlah return melebihi jumlah barang keluar! (Tersedia: " . $stockOut->quantity . ")"
            );
        }

        $return = ItemReturn::create([
            'id_return' => $this->generateIdReturn(),
            'id_item' => $data['id_item'],
            'id_stock_out' => $data['id_stock_out'],
            'quantity' => $data['quantity'],
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'return_type' => 'keluar',
            'date' => $data['date'],
        ]);

        $item->quantity += $data['quantity'];

        $stockOut->quantity -= $data['quantity'];
        if ($stockOut->quantity < 0) {
            $stockOut->quantity = 0;
        }

        $stockOut->save();
        $item->save();

        return $return;
    }

    /**
     * Update return tipe masuk.
     *
     * Logika:
     * - maxQuantity = sisa stock-in + qty return lama (sebab qty return lama masih "terpakai").
     * - qtyDifference = qty baru - qty lama → dipakai untuk mengubah stok item & stock-in
     *   secara relatif (bukan set ulang total).
     * - Harga modal dihitung ulang berbasis nilai yang berubah (qtyDifference × harga stock-in).
     *
     * @param  \App\Models\Inventory\ItemReturn $return
     * @param  array<string, mixed> $data
     * @param  \App\Models\Inventory\Items $item
     * @return \App\Models\Inventory\ItemReturn
     */
    private function updateMasukReturn(ItemReturn $return, array $data, Items $item): ItemReturn
    {
        $stockIn = ItemStockIn::lockForUpdate()->findOrFail($return->id_stock_in);

        $maxQuantity = $stockIn->quantity + $return->quantity;
        if ($data['quantity'] > $maxQuantity) {
            throw new \RuntimeException(
                "Jumlah return melebihi jumlah barang masuk! (Tersedia: {$maxQuantity})"
            );
        }

        $qtyDifference = $data['quantity'] - $return->quantity;

        $item->quantity -= $qtyDifference;
        if ($item->quantity < 0) {
            $item->quantity = 0;
        }

        $stockIn->quantity -= $qtyDifference;
        if ($stockIn->quantity < 0) {
            $stockIn->quantity = 0;
        }

        $currentItemValue = (($item->quantity + $qtyDifference) * $item->capital_price);
        $adjustedValue = $qtyDifference * $stockIn->capital_price;
        $newValue = $currentItemValue - $adjustedValue;

        $item->capital_price = $item->quantity > 0 ? (int) round($newValue / $item->quantity) : 0;

        $stockIn->save();
        $item->save();

        $return->update([
            'quantity' => $data['quantity'],
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'date' => $data['date'],
        ]);

        return $return;
    }

    /**
     * Update return tipe keluar.
     *
     * Logika:
     * - maxQuantity = sisa stock-out + qty return lama (qty return lama masih "terpakai").
     * - qtyDifference = qty baru - qty lama → item ditambah & stock-out dikurangi secara relatif.
     * - Tidak ada hitung ulang harga modal (barang kembali tanpa nilai baru).
     *
     * @param  \App\Models\Inventory\ItemReturn $return
     * @param  array<string, mixed> $data
     * @param  \App\Models\Inventory\Items $item
     * @return \App\Models\Inventory\ItemReturn
     */
    private function updateKeluarReturn(ItemReturn $return, array $data, Items $item): ItemReturn
    {
        $stockOut = ItemStockOut::lockForUpdate()->findOrFail($return->id_stock_out);

        $maxQuantity = $stockOut->quantity + $return->quantity;
        if ($data['quantity'] > $maxQuantity) {
            throw new \RuntimeException(
                "Jumlah return melebihi jumlah barang keluar! (Tersedia: {$maxQuantity})"
            );
        }

        $qtyDifference = $data['quantity'] - $return->quantity;
        $item->quantity += $qtyDifference;

        $stockOut->quantity -= $qtyDifference;
        if ($stockOut->quantity < 0) {
            $stockOut->quantity = 0;
        }

        $stockOut->save();
        $item->save();

        $return->update([
            'quantity' => $data['quantity'],
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'date' => $data['date'],
        ]);

        return $return;
    }
}
