<?php

namespace App\Services\Finance;

use App\Models\Finance\InvoiceBarang;
use App\Models\Inventory\Items;
use App\Models\Report\SalesRecap;
use App\Services\InputNormalizer;
use App\Services\StockService;

/**
 * Service layer untuk operasi bisnis Invoice Barang.
 *
 * Menangani semua logika bisnis terkait Invoice Barang termasuk:
 * - Normalisasi dan validasi item
 * - Proses item untuk penyimpanan (termasuk pengurangan stok)
 * - Perhitungan total
 * - Generasi nomor invoice
 * - Restorasi stok saat update/delete
 */
class ProductInvoiceService
{
    public function __construct(
        private StockService $stockService
    ) {}

    /**
     * Membangun query dasar untuk listing invoice barang.
     *
     * Eager-loads relasi salesRecap dan menerapkan filter search, month, year.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function baseQuery($request): \Illuminate\Database\Eloquent\Builder
    {
        return InvoiceBarang::query()->with('salesRecap')
            ->when($request->filled('search'), function ($builder) use ($request) {
                $search = $request->search;
                $builder->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('recipient', 'like', "%{$search}%")
                        ->orWhere('regarding', 'like', "%{$search}%")
                        ->orWhere('project_description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('month'), fn($builder) => $builder->whereMonth('invoice_date', $request->month))
            ->when($request->filled('year'), fn($builder) => $builder->whereYear('invoice_date', $request->year))
            ->orderByDesc('invoice_date');
    }

    /**
     * Membangun ringkasan totals dari collection invoices.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $invoices
     * @return object
     */
    public function buildTotals($invoices): object
    {
        return (object) [
            'total_invoice' => $invoices->sum(fn($invoice) => (int) $invoice->getNetAmount()),
            'invoice_count' => $invoices->count(),
            'paid_count' => $invoices->filter(fn($invoice) => $invoice->salesRecap?->status === 'Lunas')->count(),
            'total_profit' => $invoices->sum(fn($invoice) => (int) ($invoice->total_profit ?? 0)),
        ];
    }

    /**
     * Normalisasi item invoice dari input request.
     *
     * Mengkonversi format input (string JSON / array) menjadi array bersih
     * dengan field: name_item, quantity, capital_price, selling_price, from_stock, id_item.
     *
     * @param  mixed  $items  Item dari request (JSON string atau array)
     * @return array  Item yang sudah dinormalisasi
     */
    public function normalizeInvoiceItems($items): array
    {
        if (is_string($items)) {
            $items = json_decode($items, true);
        }

        if (!is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized[] = [
                'name_item' => trim((string) ($item['name_item'] ?? '')),
                'quantity' => (int) ($item['quantity'] ?? 0),
                'capital_price' => InputNormalizer::normalizeCurrency($item['capital_price'] ?? 0),
                'selling_price' => InputNormalizer::normalizeCurrency($item['selling_price'] ?? 0),
                'from_stock' => filter_var($item['from_stock'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'id_item' => $item['id_item'] ?? null,
            ];
        }

        return array_values($normalized);
    }

    /**
     * Memproses item untuk penyimpanan: validasi stok, kurangi stok, hitung profit.
     *
     * @param  array  $items  Item yang sudah dinormalisasi
     * @return array  Item yang sudah diproses (termasuk field profit)
     *
     * @throws \RuntimeException  Jika stok tidak cukup atau validasi gagal
     */
    public function processItemsForStore(array $items): array
    {
        $processedItems = [];

        foreach ($items as $item) {
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($quantity < 1) {
                throw new \RuntimeException('Qty minimal 1 untuk setiap item.');
            }

            $fromStock = filter_var($item['from_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $idItem = !empty($item['id_item']) ? (string) $item['id_item'] : null;
            $nameItem = trim((string) ($item['name_item'] ?? ''));
            $capitalPrice = (int) ($item['capital_price'] ?? 0);
            $sellingPrice = (int) ($item['selling_price'] ?? 0);

            if ($fromStock) {
                $result = $this->processStockItem($idItem, $quantity);
                $nameItem = $result['name'];
                $capitalPrice = $result['capital_price'];
                $sellingPrice = $result['selling_price'];
            } else {
                if ($nameItem === '') {
                    throw new \RuntimeException('Nama barang tidak boleh kosong.');
                }

                if ($capitalPrice >= $sellingPrice) {
                    throw new \RuntimeException('Harga modal harus lebih kecil dari harga jual.');
                }

                $idItem = null;
            }

            $processedItems[] = [
                'name_item' => $nameItem,
                'quantity' => $quantity,
                'capital_price' => $capitalPrice,
                'selling_price' => $sellingPrice,
                'from_stock' => $fromStock,
                'id_item' => $idItem,
                'profit' => ($sellingPrice - $capitalPrice) * $quantity,
            ];
        }

        return $processedItems;
    }

    /**
     * Memproses satu item dari stok: kurangi stok, ambil data harga.
     *
     * @param  string|null  $idItem    ID barang
     * @param  int          $quantity  Kuantitas yang akan dikurangi
     * @return array{string, int, int}  [name, capital_price, selling_price]
     *
     * @throws \RuntimeException  Jika barang tidak ditemukan atau stok tidak cukup
     */
    private function processStockItem(?string $idItem, int $quantity): array
    {
        if (empty($idItem)) {
            throw new \RuntimeException('Barang dari stok harus dipilih dari daftar barang.');
        }

        $stockItem = Items::lockForUpdate()->where('id_item', $idItem)->first();

        if (!$stockItem) {
            throw new \RuntimeException('Barang dengan ID "' . $idItem . '" tidak ditemukan!');
        }

        if ($stockItem->quantity < $quantity) {
            throw new \RuntimeException('Stok barang "' . $stockItem->name_item . '" tidak cukup.');
        }

        $stockItem->quantity -= $quantity;
        $stockItem->save();

        return [
            'name' => $stockItem->name_item,
            'capital_price' => (int) $stockItem->capital_price,
            'selling_price' => (int) $stockItem->selling_price,
        ];
    }

    /**
     * Menghitung total capital, selling, dan profit dari array items.
     *
     * @param  array  $items  Item yang sudah diproses
     * @return array{total_capital: int, total_selling: int, total_profit: int}
     */
    public function calculateTotals(array $items): array
    {
        $totalCapital = 0;
        $totalSelling = 0;

        foreach ($items as $item) {
            $quantity = (int) ($item['quantity'] ?? 0);
            $capitalPrice = (int) ($item['capital_price'] ?? 0);
            $sellingPrice = (int) ($item['selling_price'] ?? 0);

            $totalCapital += $capitalPrice * $quantity;
            $totalSelling += $sellingPrice * $quantity;
        }

        return [
            'total_capital' => $totalCapital,
            'total_selling' => $totalSelling,
            'total_profit' => $totalSelling - $totalCapital,
        ];
    }

    /**
     * Menghasilkan nomor invoice unik berformat: {n}/{n}/PT.AKI/{yy}.
     *
     * @return string  Nomor invoice berikutnya
     */
    public function generateInvoiceNumber(): string
    {
        $year = date('y');

        $lastInvoice = InvoiceBarang::where('invoice_number', 'like', "%/PT.AKI/{$year}")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            preg_match('/^(\d+)\//', $lastInvoice->invoice_number, $matches);
            $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "{$nextNumber}/{$nextNumber}/PT.AKI/{$year}";
    }

    /**
     * Menghasilkan ID Sales Recap unik berformat: SR-{nnnnn}.
     *
     * @return string  ID Sales Recap berikutnya
     */
    public function generateSalesRecapId(): string
    {
        $lastSalesRecap = SalesRecap::orderBy('id_sales_recap', 'desc')->first();

        if ($lastSalesRecap) {
            $lastNumber = intval(substr($lastSalesRecap->id_sales_recap, 3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $newId = 'SR-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);

        while (SalesRecap::where('id_sales_recap', $newId)->exists()) {
            $newNumber++;
            $newId = 'SR-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
        }

        return $newId;
    }

    /**
     * Merestorasi stok barang dari array items.
     *
     * @param  array  $items  Item yang stoknya akan direstorasi
     */
    public function restoreStockFromItems(array $items): void
    {
        $this->stockService->increaseStockFromItems($items);
    }

    /**
     * Mengambil items dari invoice atau sales recap.
     *
     * @param  \App\Models\Report\SalesRecap|null  $salesRecap
     * @param  \App\Models\Finance\InvoiceBarang   $invoice
     * @return array
     */
    public function getItemsFromSource(?SalesRecap $salesRecap, InvoiceBarang $invoice): array
    {
        $source = $salesRecap ?? $invoice;
        $items = $source->items;

        return is_string($items) ? json_decode($items, true) : ($items ?? []);
    }

    /**
     * Mengecek apakah invoice bisa diubah (belum lunas).
     *
     * @param  \App\Models\Report\SalesRecap|null  $salesRecap
     * @return bool  true jika bisa diubah
     */
    public function isEditable(?SalesRecap $salesRecap): bool
    {
        return !($salesRecap && $salesRecap->isLunas());
    }
}
