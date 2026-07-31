<?php

namespace App\Services;

use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockIn;
use App\Models\Inventory\ItemStockOut;
use App\Models\Inventory\ItemReturn;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk business logic Stok Barang Keluar (Stock Out) dan kalkulasi stok.
 *
 * Tanggung jawab utama:
 * - Menyediakan data Barang Keluar (dengan cache untuk dropdown)
 * - Mengurangi/menambah quantity stok item (dipakai saat buat/hapus transaksi)
 * - Menghitung ulang harga modal rata-rata (weighted average) saat stok berubah
 * - Membersihkan cache stok setelah ada perubahan
 *
 * CATATAN PENTING:
 * Semua perubahan stok memakai lockForUpdate() agar dua request yang bersamaan
 * tidak saling menimpa quantity (race condition). Selalu panggil method ini
 * di dalam DB transaction supaya kalau error, perubahan stok ikut di-rollback.
 */
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
     * Menaikkan quantity stok untuk daftar item (dipakai saat restore stok pada delete).
     *
     * Setiap elemen array harus memiliki: 'from_stock', 'id_item', dan 'quantity'.
     *
     * @param  array  $items  Daftar item yang akan dikembalikan stoknya
     * @return void
     */
    public function increaseStockFromItems(array $items): void
    {
        foreach ($items as $item) {
            // 'from_stock' bisa berisi true (boolean), 'true' (string), 1 (int), atau '1' (string)
            // tergantung dari mana data dikirim (form/API). Kita cek semua kemungkinan.
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
     * Menurunkan quantity stok untuk daftar item (dipakai saat konsumsi stok pada create/update).
     *
     * Melempar RuntimeException jika stok tidak cukup atau barang tidak ditemukan.
     *
     * @param  array  $items  Daftar item yang akan mengurangi stok
     * @return void
     *
     * @throws \RuntimeException
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
     * Proses penghapusan record Barang Masuk (Stock In): sesuaikan quantity item
     * dan hitung ulang harga modal, lalu hapus record stock-in.
     *
     * Asumsi: pemanggil sudah mengelola DB transaction bila diperlukan.
     *
     * Rumus hitung ulang harga modal (weighted average):
     * Harga modal baru = (Nilai stok sisa - Nilai stock-in yang dihapus) / Sisa quantity
     *
     * @param  ItemStockIn  $stockIn  Record Barang Masuk yang akan dihapus
     * @return void
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
     * Proses penghapusan record Pengembalian Barang (Item Return). Menangani tipe 'masuk' dan 'keluar'.
     *
     * Asumsi: pemanggil sudah mengelola DB transaction bila diperlukan.
     *
     * Logika:
     * - Return tipe 'masuk' (mengembalikan barang ke supplier):
     *   stok item DITAMBAH, dan quantity stock-in DITAMBAH kembali. Harga modal dihitung ulang
     *   dengan weighted average agar barang yang dikembalikan "dikeluarkan" dari perhitungan harga.
     * - Return tipe 'keluar' (barang dikembalikan dari proyek/konsumen):
     *   stok item DIKURANGI, dan quantity stock-out DITAMBAH kembali.
     *
     * @param  ItemReturn  $return  Record Pengembalian Barang yang akan dihapus
     * @return void
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
            // $stockIn?->capital_price adalah nullsafe operator:
            // jika $stockIn null, ekspresi ini langsung mengembalikan null (tanpa error),
            // lalu '?? 0' mengubah null menjadi 0. Jadi aman walau stock-in sudah tidak ada.
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
