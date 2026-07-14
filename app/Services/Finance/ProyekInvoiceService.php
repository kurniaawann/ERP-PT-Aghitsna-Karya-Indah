<?php

namespace App\Services\Finance;

use App\Models\Finance\InvoiceProyek;
use App\Services\InputNormalizer;

/**
 * Service layer untuk operasi bisnis Invoice Proyek.
 *
 * Menangani semua logika bisnis terkait Invoice Proyek termasuk:
 * - Query builder untuk listing
 * - Normalisasi item
 * - Perhitungan total
 * - Generasi nomor invoice
 */
class ProyekInvoiceService
{
    public function __construct(
        private InvoiceCalculatorService $calculator
    ) {}

    /**
     * Membangun query dasar untuk listing invoice proyek.
     *
     * Eager-loads relasi paymentProofs dan menerapkan filter search, month, year.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function baseQuery($request): \Illuminate\Database\Eloquent\Builder
    {
        return InvoiceProyek::query()->with('paymentProofs')
            ->when($request->filled('search'), function ($builder) use ($request) {
                $search = $request->search;
                $builder->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('recipient', 'like', "%{$search}%")
                        ->orWhere('project_description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('month'), fn($builder) => $builder->whereMonth('invoice_date', $request->month))
            ->when($request->filled('year'), fn($builder) => $builder->whereYear('invoice_date', $request->year))
            ->orderByDesc('invoice_date');
    }

    /**
     * Normalisasi item invoice dari input request.
     *
     * Mengkonversi format input (JSON string / array) menjadi array bersih
     * dengan field: keterangan, volume, satuan, harga.
     *
     * @param  mixed  $items  Item dari request (JSON string atau array)
     * @return array  Item yang sudah dinormalisasi
     */
    public function normalizeInvoiceItems($items): array
    {
        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }

        if (!is_array($items)) {
            return [];
        }

        return array_map(function ($item) {
            $item['volume'] = InputNormalizer::normalizeDecimal($item['volume'] ?? 0);
            $item['harga'] = InputNormalizer::normalizeCurrency($item['harga'] ?? 0);

            return $item;
        }, $items);
    }

    /**
     * Menghitung total_amount dari array items (volume x harga).
     *
     * @param  array  $items  Item yang sudah dinormalisasi
     * @return int  Total amount
     */
    public function calculateItemsTotal(array $items): int
    {
        $total = 0;

        foreach ($items as $item) {
            $total += ($item['volume'] ?? 0) * ($item['harga'] ?? 0);
        }

        return (int) round($total);
    }

    /**
     * Menghitung diskon dan DP menggunakan InvoiceCalculatorService.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $totalAmount
     * @return array{discountAmount: float, totalAfterDiscount: float, dpAmount: float}
     */
    public function calculateFromRequest($request, int $totalAmount): array
    {
        return $this->calculator->calculateFromRequest($request, $totalAmount);
    }

    /**
     * Menghasilkan nomor invoice unik berformat: {n}/{n}/PT.AKI/{yy}.
     *
     * @return string  Nomor invoice berikutnya
     */
    public function generateInvoiceNumber(): string
    {
        $year = date('y');

        $lastInvoice = InvoiceProyek::where('invoice_number', 'like', "%/PT.AKI/{$year}")
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
     * Menyimpan invoice proyek baru ke database.
     *
     * @param  array  $data  Data invoice yang sudah divalidasi
     * @param  array  $items  Item yang sudah dinormalisasi
     * @return \App\Models\Finance\InvoiceProyek
     */
    public function createInvoice(array $data, array $items): InvoiceProyek
    {
        $totalAmount = $this->calculateItemsTotal($items);
        $calculations = $this->calculateFromRequest(
            new \Illuminate\Http\Request($data),
            $totalAmount
        );

        $data['items'] = $items;
        $data['total_amount'] = $totalAmount;
        $data['total_after_discount'] = $calculations['totalAfterDiscount'] > 0
            && $calculations['totalAfterDiscount'] != $totalAmount
            ? $calculations['totalAfterDiscount']
            : null;
        $data['dp_amount'] = $calculations['dpAmount'] > 0
            ? $calculations['dpAmount']
            : null;

        return InvoiceProyek::create($data);
    }

    /**
     * Mengupdate invoice proyek yang sudah ada.
     *
     * @param  \App\Models\Finance\InvoiceProyek  $invoice
     * @param  array  $data  Data invoice yang sudah divalidasi
     * @param  array  $items  Item yang sudah dinormalisasi
     * @return bool
     */
    public function updateInvoice(InvoiceProyek $invoice, array $data, array $items): bool
    {
        $totalAmount = $this->calculateItemsTotal($items);
        $calculations = $this->calculateFromRequest(
            new \Illuminate\Http\Request($data),
            $totalAmount
        );

        return InvoiceProyek::where('invoice_number', $invoice->invoice_number)->update([
            'invoice_date' => $data['invoice_date'],
            'recipient' => $data['recipient'],
            'regarding' => $data['regarding'] ?? null,
            'project_description' => $data['project_description'],
            'items' => json_encode($items),
            'total_amount' => $totalAmount,
            'discount_type' => $data['discount_type'] ?? null,
            'discount_value' => $data['discount_value'] ?? null,
            'total_after_discount' => $calculations['totalAfterDiscount'] > 0
                && $calculations['totalAfterDiscount'] != $totalAmount
                ? $calculations['totalAfterDiscount']
                : null,
            'dp_type' => $data['dp_type'] ?? null,
            'dp_value' => $data['dp_value'] ?? null,
            'dp_amount' => $calculations['dpAmount'] > 0
                ? $calculations['dpAmount']
                : null,
            'selected_payment_accounts' => json_encode($data['selected_payment_accounts'] ?? []),
        ]);
    }
}
