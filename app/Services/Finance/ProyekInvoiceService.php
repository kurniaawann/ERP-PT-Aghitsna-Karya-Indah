<?php

namespace App\Services\Finance;

use App\Models\Finance\InvoiceProyek;
use App\Services\InputNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

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
    public function baseQuery($request): Builder
    {
        return InvoiceProyek::query()->with('paymentProofs')
            ->where('created_by', auth()->id())
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
     * Menghasilkan nomor invoice unik berformat: {A}/{B}/PT.AKI/{yy}.
     * Kedua angka (A dan B) diincrement secara terpisah.
     *
     * Logika:
     * - Cari invoice terakhir tahun ini (filter '%/PT.AKI/{yy}').
     * - Parse dua angka depan lewat regex /^(\d+)\/(\d+)\//, lalu increment keduanya.
     * - Jika belum ada invoice tahun ini, mulai dari A=1, B=6.
     *
     * @return string  Nomor invoice berikutnya
     */
    public function generateInvoiceNumber(): string
    {
        $year = date('y');

        $lastInvoice = InvoiceProyek::where('invoice_number', 'like', "%/PT.AKI/{$year}")
            ->orderByDesc('invoice_number')
            ->first();

        if ($lastInvoice && preg_match('/^(\d+)\/(\d+)\//', $lastInvoice->invoice_number, $matches)) {
            $nextA = (int) $matches[1] + 1;
            $nextB = (int) $matches[2] + 1;
        } else {
            $nextA = 1;
            $nextB = 6;
        }

        return "{$nextA}/{$nextB}/PT.AKI/{$year}";
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
            new Request($data),
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
        $data['created_by'] = auth()->id();

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
            new Request($data),
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
            'signed_by' => $data['signed_by'] ?? null,
            'division' => $data['division'] ?? null,
        ]);
    }

    /**
     * Menghapus beberapa invoice proyek sekaligus (bulk delete).
     *
     * PENTING (kenapa foreach, bukan mass delete):
     * - InvoiceProyek punya InvoiceProyekObserver. Event 'deleted' di observer
     *   membersihkan InvoiceProyekReminder dan file bukti pembayaran terkait.
     * - foreach + $invoice->delete() memicu observer tersebut; mass delete tidak.
     *
     * @param  array  $ids  Daftar invoice_number yang akan dihapus
     * @return int  Jumlah record yang dihapus
     */
    public function destroySelected(array $ids): int
    {
        $invoices = InvoiceProyek::whereIn('invoice_number', $ids)->get();

        foreach ($invoices as $invoice) {
            $invoice->delete();
        }

        return $invoices->count();
    }
}
