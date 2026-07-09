<?php

namespace App\Services;

use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockIn;
use App\Models\Inventory\ItemStockOut;
use App\Models\Inventory\ItemReturn;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockReportService
{
    public function generateReport(string $startDate, string $endDate, ?string $itemId = null): Collection
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $query = Items::query();
        if ($itemId) {
            $query->where('id_item', $itemId);
        }
        $items = $query->get();

        $stockInsBefore = ItemStockIn::where('date', '<', $start)
            ->when($itemId, fn($q) => $q->where('id_item', $itemId))
            ->groupBy('id_item')
            ->select('id_item', DB::raw('SUM(quantity) as total'))
            ->pluck('total', 'id_item');

        $stockOutsBefore = ItemStockOut::where('date', '<', $start)
            ->when($itemId, fn($q) => $q->where('id_item', $itemId))
            ->groupBy('id_item')
            ->select('id_item', DB::raw('SUM(quantity) as total'))
            ->pluck('total', 'id_item');

        $returnsBefore = ItemReturn::where('date', '<', $start)
            ->when($itemId, fn($q) => $q->where('id_item', $itemId))
            ->groupBy('id_item')
            ->select('id_item', DB::raw('SUM(quantity) as total'))
            ->pluck('total', 'id_item');

        $stockInsPeriod = ItemStockIn::whereBetween('date', [$start, $end])
            ->when($itemId, fn($q) => $q->where('id_item', $itemId))
            ->groupBy('id_item')
            ->select('id_item', DB::raw('SUM(quantity) as total'))
            ->pluck('total', 'id_item');

        $stockOutsPeriod = ItemStockOut::whereBetween('date', [$start, $end])
            ->when($itemId, fn($q) => $q->where('id_item', $itemId))
            ->groupBy('id_item')
            ->select('id_item', DB::raw('SUM(quantity) as total'))
            ->pluck('total', 'id_item');

        $returnsPeriod = ItemReturn::whereBetween('date', [$start, $end])
            ->when($itemId, fn($q) => $q->where('id_item', $itemId))
            ->groupBy('id_item')
            ->select('id_item', DB::raw('SUM(quantity) as total'))
            ->pluck('total', 'id_item');

        $reportData = collect();

        foreach ($items as $item) {
            $beginningStock = max(0,
                ($stockInsBefore[$item->id_item] ?? 0)
                - ($stockOutsBefore[$item->id_item] ?? 0)
                - ($returnsBefore[$item->id_item] ?? 0)
            );

            $stockIn = $stockInsPeriod[$item->id_item] ?? 0;
            $stockOut = $stockOutsPeriod[$item->id_item] ?? 0;
            $returns = $returnsPeriod[$item->id_item] ?? 0;

            $endingStock = max(0, $beginningStock + $stockIn - $stockOut - $returns);
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
