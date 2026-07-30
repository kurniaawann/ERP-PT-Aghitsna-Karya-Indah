<?php

namespace App\Services\Finance;

use App\Models\Inventory\Items;
use App\Models\Report\SalesRecap;
use App\Services\InputNormalizer;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service layer untuk operasi bisnis Rekap Penjualan.
 *
 * Menangani semua logika bisnis terkait Sales Recap termasuk:
 * - Pencarian dan filter
 * - Pembuatan ID unik
 * - Manajemen stock (create/update)
 * - Perhitungan total
 */
class RecapSalesService
{
    /**
     * Kolom yang diizinkan untuk sorting pada SalesReportController.
     */
    private const ALLOWED_SORT_COLUMNS = [
        'date',
        'name_proyek',
        'total_capital',
        'total_selling',
        'total_profit',
        'status',
        'created_at',
    ];

    public function __construct(
        private StockService $stockService
    ) {}

    /**
     * Membangun query dasar untuk listing rekap penjualan.
     *
     * @param  \Illuminate\Http\Request $request  Request yang berisi parameter filter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildFilteredQuery(Request $request): Builder
    {
        $search = $request->get('search');
        $month = $request->get('month');
        $year = $request->get('year');

        return SalesRecap::query()
            ->when($search, function ($query, $search) {
                $query->where('id_sales_recap', 'like', "%{$search}%")
                    ->orWhere('name_proyek', 'like', "%{$search}%");
            })
            ->when($month, function ($query, $month) {
                $query->whereMonth('date', $month);
            })
            ->when($year, function ($query, $year) {
                $query->whereYear('date', $year);
            });
    }

    /**
     * Membangun query untuk grand totals (ringkasan).
     *
     * @param  \Illuminate\Http\Request $request  Request yang berisi parameter filter
     * @return object|null  Object berisi grand_total_capital, grand_total_selling, grand_total_profit
     */
    public function getGrandTotals(Request $request)
    {
        return $this->buildFilteredQuery($request)
            ->select(
                DB::raw('SUM(total_capital) as grand_total_capital'),
                DB::raw('SUM(total_selling) as grand_total_selling'),
                DB::raw('SUM(total_profit) as grand_total_profit')
            )->first();
    }

    /**
     * Membuat rekap penjualan baru dengan manajemen stock.
     *
     * @param  array<string, mixed> $data  Data yang sudah validasi dari FormRequest
     * @return \App\Models\Report\SalesRecap
     *
     * @throws \RuntimeException  Jika stock tidak cukup atau barang tidak ditemukan
     */
    public function createRecap(array $data): SalesRecap
    {
        $items = $this->normalizeItems($data['items']);

        if (empty($items)) {
            throw new \RuntimeException('Minimal harus ada 1 item!');
        }

        $this->processStockForItems($items);

        $this->calculateItemProfits($items);

        $totals = $this->calculateTotals($items);

        return SalesRecap::create([
            'id_sales_recap' => $this->generateId(),
            'date' => $data['date'],
            'name_proyek' => $data['name_proyek'],
            'items' => $items,
            'status' => 'Belum Lunas',
            'total_capital' => $totals['total_capital'],
            'total_selling' => $totals['total_selling'],
            'total_profit' => $totals['total_profit'],
        ]);
    }

    /**
     * Mengupdate rekap penjualan dengan rekonsiliasi stock.
     *
     * @param  \App\Models\Report\SalesRecap $salesRecap  Model yang akan diupdate
     * @param  array<string, mixed>          $data        Data yang sudah validasi dari FormRequest
     * @return bool
     *
     * @throws \RuntimeException  Jika stock tidak cukup atau barang tidak ditemukan
     */
    public function updateRecap(SalesRecap $salesRecap, array $data): bool
    {
        $newItems = $this->normalizeItems($data['items']);

        if (empty($newItems)) {
            throw new \RuntimeException('Minimal harus ada 1 item!');
        }

        $oldItems = is_string($salesRecap->items)
            ? json_decode($salesRecap->items, true)
            : $salesRecap->items;

        $this->reconcileStock($oldItems, $newItems);

        $this->processStockForItems($newItems);

        $this->validateItemPrices($newItems);

        $this->calculateItemProfits($newItems);

        $totals = $this->calculateTotals($newItems);

        $salesRecap->date = $data['date'];
        $salesRecap->name_proyek = $data['name_proyek'];
        $salesRecap->items = $newItems;
        $salesRecap->total_capital = $totals['total_capital'];
        $salesRecap->total_selling = $totals['total_selling'];
        $salesRecap->total_profit = $totals['total_profit'];

        return $salesRecap->save();
    }

    /**
     * Hapus beberapa rekap penjualan sekaligus (bulk delete) dengan pengembalian stock.
     *
     * @param  array<int, string> $ids  Daftar ID rekap penjualan
     * @return int  Jumlah rekap yang dihapus
     */
    public function bulkDelete(array $ids): int
    {
        $salesRecaps = SalesRecap::whereIn('id_sales_recap', $ids)->get();
        $deletedCount = 0;

        foreach ($salesRecaps as $salesRecap) {
            $items = is_string($salesRecap->items)
                ? json_decode($salesRecap->items, true)
                : $salesRecap->items;

            $this->stockService->increaseStockFromItems($items ?? []);
            $salesRecap->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    /**
     * Normalisasi item rekap penjualan agar harga rupiah yang tampil di form tetap dihitung sebagai angka.
     *
     * @param  mixed $items  Array item atau JSON string
     * @return array<int, array<string, mixed>>
     */
    public function normalizeItems($items): array
    {
        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }

        if (!is_array($items)) {
            return [];
        }

        return array_map(function ($item) {
            $item['quantity'] = (int) ($item['quantity'] ?? 0);
            $item['capital_price'] = InputNormalizer::normalizeCurrency($item['capital_price'] ?? 0);
            $item['selling_price'] = InputNormalizer::normalizeCurrency($item['selling_price'] ?? 0);

            return $item;
        }, $items);
    }

    /**
     * Menghitung profit per item.
     *
     * @param  array<int, array<string, mixed>> &$items  Items (by reference)
     * @return void
     */
    private function calculateItemProfits(array &$items): void
    {
        foreach ($items as &$item) {
            $item['profit'] = ((int) ($item['selling_price'] ?? 0) - (int) ($item['capital_price'] ?? 0)) * (int) ($item['quantity'] ?? 0);
        }
        unset($item);
    }

    /**
     * Menghitung total capital, selling, dan profit dari items.
     *
     * @param  array<int, array<string, mixed>> $items
     * @return array{total_capital: int, total_selling: int, total_profit: int}
     */
    private function calculateTotals(array $items): array
    {
        $totalCapital = 0;
        $totalSelling = 0;

        foreach ($items as $item) {
            $totalCapital += (int) ($item['capital_price'] ?? 0) * (int) ($item['quantity'] ?? 0);
            $totalSelling += (int) ($item['selling_price'] ?? 0) * (int) ($item['quantity'] ?? 0);
        }

        return [
            'total_capital' => $totalCapital,
            'total_selling' => $totalSelling,
            'total_profit' => $totalSelling - $totalCapital,
        ];
    }

    /**
     * Proses stock untuk items yang dari stock (from_stock = true).
     *
     * @param  array<int, array<string, mixed>> &$items  Items (by reference)
     * @return void
     *
     * @throws \RuntimeException  Jika stock tidak cukup atau barang tidak ditemukan
     */
    private function processStockForItems(array &$items): void
    {
        foreach ($items as &$item) {
            if (!empty($item['from_stock']) && !empty($item['id_item'])) {
                $stockItem = Items::lockForUpdate()->where('id_item', $item['id_item'])->first();

                if (!$stockItem) {
                    throw new \RuntimeException('Barang "' . ($item['name_item'] ?? '') . '" tidak ditemukan!');
                }

                if ($stockItem->quantity < $item['quantity']) {
                    throw new \RuntimeException('Stok Barang Tidak Cukup Silahkan Sesuaikan Dengan Stok Yang Tersedia');
                }

                $stockItem->quantity -= $item['quantity'];
                $stockItem->save();

                $item['capital_price'] = (int) $stockItem->capital_price;
                $item['selling_price'] = (int) $stockItem->selling_price;
                $item['from_stock'] = true;
            } else {
                $item['from_stock'] = false;
                $item['id_item'] = null;
            }
        }
        unset($item);
    }

    /**
     * Rekonsiliasi stock: kembalikan stock lama, lalu kurangi stock baru.
     *
     * @param  array<int, array<string, mixed>> $oldItems  Items lama
     * @param  array<int, array<string, mixed>> $newItems  Items baru
     * @return void
     *
     * @throws \RuntimeException  Jika stock tidak cukup
     */
    private function reconcileStock(array $oldItems, array $newItems): void
    {
        $stockChanges = [];

        // Kembalikan stock dari items lama
        foreach ($oldItems as $oldItem) {
            if (filter_var($oldItem['from_stock'] ?? false, FILTER_VALIDATE_BOOLEAN) && !empty($oldItem['id_item'])) {
                $itemId = $oldItem['id_item'];
                $stockChanges[$itemId] = ($stockChanges[$itemId] ?? 0) + (int) ($oldItem['quantity'] ?? 0);
            }
        }

        // Kurangi stock untuk items baru
        foreach ($newItems as $item) {
            $isFromStock = filter_var($item['from_stock'] ?? false, FILTER_VALIDATE_BOOLEAN) && !empty($item['id_item']);

            if ($isFromStock) {
                $itemId = $item['id_item'];
                $stockChanges[$itemId] = ($stockChanges[$itemId] ?? 0) - (int) ($item['quantity'] ?? 0);
            }
        }

        // Terapkan perubahan stock
        foreach ($stockChanges as $itemId => $delta) {
            if ($delta === 0) continue;

            $stockItem = Items::lockForUpdate()->where('id_item', $itemId)->first();

            if (!$stockItem) {
                throw new \RuntimeException('Barang dengan ID "' . $itemId . '" tidak ditemukan!');
            }

            $newStock = $stockItem->quantity + $delta;
            if ($newStock < 0) {
                throw new \RuntimeException('Stok barang yang diambil melebihi stok tersedia!');
            }

            $stockItem->quantity = $newStock;
            $stockItem->save();
        }
    }

    /**
     * Validasi harga modal harus lebih kecil dari harga jual untuk items manual.
     *
     * @param  array<int, array<string, mixed>> $items
     * @return void
     *
     * @throws \RuntimeException  Jika harga modal >= harga jual
     */
    private function validateItemPrices(array $items): void
    {
        foreach ($items as $item) {
            if (empty($item['from_stock'])) {
                $capitalPrice = (int) ($item['capital_price'] ?? 0);
                $sellingPrice = (int) ($item['selling_price'] ?? 0);

                if ($capitalPrice >= $sellingPrice) {
                    throw new \RuntimeException('Harga modal harus lebih kecil dari harga jual untuk item');
                }
            }
        }
    }

    /**
     * Generate unique sales recap ID (format: SR-xxxxx).
     *
     * Menggunakan database lock untuk mencegah race condition.
     *
     * @return string
     */
    private function generateId(): string
    {
        // Lock table untuk mencegah race condition
        $lastSalesRecap = SalesRecap::lockForUpdate()
            ->orderBy('id_sales_recap', 'desc')
            ->first();

        if ($lastSalesRecap) {
            $lastNumber = intval(substr($lastSalesRecap->id_sales_recap, 3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $newId = 'SR-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);

        // Double-check for uniqueness (in case of race condition)
        while (SalesRecap::where('id_sales_recap', $newId)->exists()) {
            $newNumber++;
            $newId = 'SR-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
        }

        return $newId;
    }

    /**
     * Mendapatkan kolom sorting yang diizinkan.
     *
     * @param  string $sortBy  Kolom yang diminta
     * @return string           Kolom yang valid atau default 'date'
     */
    public function getAllowedSortColumn(string $sortBy): string
    {
        return in_array($sortBy, self::ALLOWED_SORT_COLUMNS) ? $sortBy : 'date';
    }
}
