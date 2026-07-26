<?php

namespace App\Services\Finance;

use App\Models\Finance\InvoiceAlumunium;
use App\Services\InputNormalizer;

/**
 * Service layer untuk operasi bisnis Invoice Alumunium.
 *
 * Menangani semua logika bisnis terkait Invoice Alumunium termasuk:
 * - Query builder untuk listing
 * - Normalisasi item
 * - Perhitungan total
 * - Generasi nomor invoice
 */
class AlumuniumInvoiceService
{
    public function __construct(
        private InvoiceCalculatorService $calculator
    ) {}

    /**
     * Membangun query dasar untuk listing invoice alumunium.
     *
     * Eager-loads relasi paymentProofs dan menerapkan filter search, month, year.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function baseQuery($request): \Illuminate\Database\Eloquent\Builder
    {
        return InvoiceAlumunium::query()->with('paymentProofs')
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
     * Menghitung total_amount dari array items (volume × harga).
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
     * @return array{totalAfterDiscount: int, dpAmount: int}
     */
    public function calculateFromRequest($request, int $totalAmount): array
    {
        return $this->calculator->calculateFromRequest($request, $totalAmount);
    }

    /**
     * Menghasilkan nomor invoice unik berformat: {A}/{B}/ALU/{yy}.
     * Kedua angka (A dan B) diincrement secara terpisah.
     *
     * @return string  Nomor invoice berikutnya
     */
    public function generateInvoiceNumber(): string
    {
        $year = date('y');

        $lastInvoice = InvoiceAlumunium::where('invoice_number', 'like', "%/ALU/{$year}")
            ->orderByDesc('invoice_number')
            ->first();

        if ($lastInvoice && preg_match('/^(\d+)\/(\d+)\//', $lastInvoice->invoice_number, $matches)) {
            $nextA = (int) $matches[1] + 1;
            $nextB = (int) $matches[2] + 1;
        } else {
            $nextA = 53;
            $nextB = 53;
        }

        return "{$nextA}/{$nextB}/ALU/{$year}";
    }
}
