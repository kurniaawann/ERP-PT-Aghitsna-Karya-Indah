<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Items;
use App\Models\Inventory\ItemStockIn;
use App\Models\Inventory\ItemStockOut;
use App\Models\Inventory\ItemReturn;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk mengelola business logic Laporan Stok Barang.
 *
 * Service ini bertanggung jawab atas:
 * - Generasi data laporan stok berdasarkan periode tertentu
 * - Perhitungan stok awal, stok masuk, stok keluar, retur, dan stok akhir
 * - Perhitungan nilai stok berdasarkan harga modal
 * - Aggregasi summary laporan
 *
 * Business logic perhitungan stok:
 * - Stok Awal = (Total Stock In sebelum periode) - (Total Stock Out sebelum periode) - (Total Return sebelum periode)
 * - Stok Akhir = Stok Awal + Stok Masuk - Stok Keluar - Retur
 * - Nilai Stok = Stok Akhir x Harga Modal
 */
class StockReportService
{
    /**
     * Menghasilkan data laporan stok untuk periode tertentu.
     *
     * Method ini menghitung stok awal (sebelum periode), stok masuk/keluar/retur
     * (selama periode), dan stok akhir untuk setiap barang. Jika $itemId diberikan,
     * hanya barang tersebut yang diproses.
     *
     * Query yang dijalankan:
     * 1. Query Items (1 query)
     * 2. Agregasi Stock In sebelum periode (1 query)
     * 3. Agregasi Stock Out sebelum periode (1 query)
     * 4. Agregasi Return sebelum periode (1 query)
     * 5. Agregasi Stock In selama periode (1 query)
     * 6. Agregasi Stock Out selama periode (1 query)
     * 7. Agregasi Return selama periode (1 query)
     *
     * Total: 7 query — sudah optimal, tidak ada N+1.
     *
     * @param  string       $startDate  Tanggal mulai laporan (format Y-m-d)
     * @param  string       $endDate    Tanggal akhir laporan (format Y-m-d)
     * @param  string|null  $itemId     ID barang spesifik (opsional, null untuk semua barang)
     * @return Collection   Collection of array berisi data laporan per barang
     */
    public function generateReport(string $startDate, string $endDate, ?string $itemId = null): Collection
    {
        return $this->computeReport($startDate, $endDate, $itemId);
    }

    /**
     * Menghasilkan data laporan stok (computation tanpa cache).
     *
     * @param  string       $startDate
     * @param  string       $endDate
     * @param  string|null  $itemId
     * @return Collection
     */
    private function computeReport(string $startDate, string $endDate, ?string $itemId = null): Collection
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Ambil data barang (dengan filter item jika ada)
        $query = Items::query();
        if ($itemId) {
            $query->where('id_item', $itemId);
        }
        $items = $query->get();

        // ─── Agregasi Stok Sebelum Periode (untuk Stok Awal) ────────
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

        // ─── Agregasi Stok Selama Periode ──────────────────────────
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

        // ─── Komputasi Data Laporan per Barang ─────────────────────
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

    /**
     * Menghitung summary (agregasi) dari data laporan stok.
     *
     * Summary mencakup:
     * - total_items: Jumlah barang
     * - total_beginning_stock: Total stok awal
     * - total_stock_in: Total stok masuk selama periode
     * - total_stock_out: Total stok keluar selama periode
     * - total_returns: Total retur selama periode
     * - total_ending_stock: Total stok akhir
     * - total_stock_value: Total nilai stok (rupiah)
     *
     * @param  Collection  $reportData  Data laporan dari method generateReport()
     * @return array       Array berisi summary dengan kunci-kunci di atas
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

    /**
     * Membangun data untuk export PDF/Excel.
     *
     * Mengembalikan array dengan struktur:
     * - reportData: Collection data laporan stok per barang
     * - summary: Array summary (total stok awal, masuk, keluar, retur, akhir, nilai)
     * - periodTitle: Label periode untuk header
     * - startDate: Tanggal mulai
     * - endDate: Tanggal akhir
     *
     * @param  string       $startDate  Tanggal mulai laporan
     * @param  string       $endDate    Tanggal akhir laporan
     * @param  string|null  $itemId     ID barang spesifik (opsional)
     * @return array{reportData: Collection, summary: array, periodTitle: string, startDate: string, endDate: string}
     */
    public function buildExportData(string $startDate, string $endDate, ?string $itemId = null): array
    {
        $reportData = $this->generateReport($startDate, $endDate, $itemId);
        $summary = $this->getSummary($reportData);
        $periodTitle = $this->buildPeriodTitle($startDate, $endDate);

        return [
            'reportData' => $reportData,
            'summary' => $summary,
            'periodTitle' => $periodTitle,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    /**
     * Membangun label periode untuk header PDF/Excel.
     *
     * @param  string  $startDate  Tanggal mulai
     * @param  string  $endDate    Tanggal akhir
     * @return string Label periode (contoh: "01/01/2026 - 31/01/2026")
     */
    public function buildPeriodTitle(string $startDate, string $endDate): string
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        return $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');
    }
}
