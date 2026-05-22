<?php

namespace App\Services;

use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockIn;
use App\Models\Inventory\ItemStockOut;
use App\Models\Inventory\ItemReturn;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StockReportService
{
    /**
     * Generate stock report for a given date range
     * 
     * @param string $startDate
     * @param string $endDate
     * @param string|null $itemId Filter by specific item
     * @return Collection
     */
    public function generateReport(string $startDate, string $endDate, ?string $itemId = null): Collection
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Get all items or specific item
        $query = Items::query();
        if ($itemId) {
            $query->where('id_item', $itemId);
        }
        $items = $query->get();

        $reportData = collect();

        foreach ($items as $item) {
            // Calculate beginning stock
            $beginningStock = $this->calculateBeginningStock($item->id_item, $start);

            // Calculate stock in during period
            $stockIn = ItemStockIn::where('id_item', $item->id_item)
                ->whereBetween('tanggal', [$start, $end])
                ->sum('quantity');

            // Calculate stock out during period
            $stockOut = ItemStockOut::where('id_item', $item->id_item)
                ->whereBetween('tanggal', [$start, $end])
                ->sum('quantity');

            // Calculate returns during period (by return date, not by stock out/in date)
            $returns = ItemReturn::where('id_item', $item->id_item)
                ->whereBetween('tanggal', [$start, $end])
                ->sum('quantity');

            // Calculate ending stock
            $endingStock = $beginningStock + $stockIn - $stockOut - $returns;

            // Ensure ending stock is not negative
            $endingStock = max(0, $endingStock);

            // Calculate stock value (using capital price)
            $stockValue = $endingStock * $item->capital_price;

            $reportData->push([
                'id_item' => $item->id_item,
                'name_item' => $item->name_item,
                'beginning_stock' => $beginningStock,
                'stock_in' => $stockIn,
                'stock_out' => $stockOut,
                'returns' => $returns,
                'ending_stock' => $endingStock,
                'capital_price' => $item->capital_price,
                'stock_value' => $stockValue,
                'selling_price' => $item->selling_price,
            ]);
        }

        return $reportData;
    }

    /**
     * Calculate beginning stock (stok awal)
     * Cumulative dari semua transaksi sebelum tanggal mulai
     * 
     * @param string $itemId
     * @param Carbon $beforeDate
     * @return int
     */
    private function calculateBeginningStock(string $itemId, Carbon $beforeDate): int
    {
        // Stock in sebelum periode
        $stockInBefore = ItemStockIn::where('id_item', $itemId)
            ->where('tanggal', '<', $beforeDate)
            ->sum('quantity');

        // Stock out sebelum periode
        $stockOutBefore = ItemStockOut::where('id_item', $itemId)
            ->where('tanggal', '<', $beforeDate)
            ->sum('quantity');

        // Returns sebelum periode (by return date, not stock out/in date)
        $returnsBefore = ItemReturn::where('id_item', $itemId)
            ->where('tanggal', '<', $beforeDate)
            ->sum('quantity');

        return max(0, $stockInBefore - $stockOutBefore - $returnsBefore);
    }

    /**
     * Get summary of the report
     * 
     * @param Collection $reportData
     * @return array
     */
    public function getSummary(Collection $reportData): array
    {
        return [
            'total_items' => $reportData->count(),
            'total_beginning_stock' => $reportData->sum('beginning_stock'),
            'total_stock_in' => $reportData->sum('stock_in'),
            'total_stock_out' => $reportData->sum('stock_out'),
            'total_returns' => $reportData->sum('returns'),
            'total_ending_stock' => $reportData->sum('ending_stock'),
            'total_stock_value' => $reportData->sum('stock_value'),
        ];
    }
}
