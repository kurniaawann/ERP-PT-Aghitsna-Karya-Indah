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
            ->when($request->filled('status'), function ($builder) use ($request) {
                $this->applyStatusFilter($builder, $request->status);
            })
            ->orderByDesc('invoice_date');
    }

    /**
     * Menerapkan filter status pembayaran invoice proyek.
     *
     * Status "lunas" mengikuti isFullyPaid() dari kalkulator: sisa tagihan
     * = (grand_total - discount - dp - total_payment) <= 0, dengan
     * grand_total = total_amount + ppn. Karena sisa dipastikan non-negatif
     * (max(0, ...)), lunas berarti (grand_total - discount - dp - paid) <= 0.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  string  $status  'lunas' atau 'belum'
     * @return void
     */
    private function applyStatusFilter($builder, string $status): void
    {
        $base = '(CASE WHEN total_after_discount IS NOT NULL AND total_after_discount <> total_amount
            THEN total_after_discount ELSE total_amount END)';
        $ppn = '(CASE WHEN ppn IS NOT NULL AND ppn > 0 THEN ROUND(' . $base . ' * ppn / 100) ELSE 0 END)';
        $discount = '(CASE WHEN discount_value IS NOT NULL AND discount_value > 0 THEN
            (CASE WHEN discount_type = \'percentage\' THEN ROUND(total_amount * discount_value / 100)
                  ELSE ROUND(discount_value) END) ELSE 0 END)';
        $dp = '(CASE WHEN dp_value IS NOT NULL AND dp_value > 0 THEN
            (CASE WHEN dp_type = \'percentage\' THEN ROUND(' . $base . ' * dp_value / 100)
                  ELSE ROUND(dp_value) END) ELSE 0 END)';
        $paid = '(SELECT COALESCE(SUM(pp.amount), 0) FROM payment_proofs pp
            WHERE pp.invoice_type = \'proyek\'
              AND pp.invoice_number = proyek_invoices.invoice_number)';
        $remaining = '((total_amount + ' . $ppn . ') - ' . $discount . ' - ' . $dp . ' - ' . $paid . ')';

        $builder->whereRaw($remaining . ($status === 'lunas' ? ' <= 0' : ' > 0'));
    }

    /**
     * Normalisasi item invoice dari input request.
     *
     * Mendukung dua format:
     * - Superadmin: keterangan, volume, satuan, harga
     * - Admin: deskripsi, harga, persentase
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
            $item['harga'] = InputNormalizer::normalizeCurrency($item['harga'] ?? 0);

            if (array_key_exists('persentase', $item)) {
                $item['persentase'] = InputNormalizer::normalizeDecimal($item['persentase'] ?? 0);
            } else {
                $item['volume'] = InputNormalizer::normalizeDecimal($item['volume'] ?? 0);
            }

            return $item;
        }, $items);
    }

    /**
     * Menghitung total_amount dari array items.
     *
     * Format superadmin: volume x harga
     * Format admin: harga x (persentase / 100)
     *
     * @param  array  $items  Item yang sudah dinormalisasi
     * @return int  Total amount
     */
    public function calculateItemsTotal(array $items): int
    {
        $total = 0;

        foreach ($items as $item) {
            if (array_key_exists('persentase', $item)) {
                $total += ($item['harga'] ?? 0) * (($item['persentase'] ?? 0) / 100);
            } else {
                $total += ($item['volume'] ?? 0) * ($item['harga'] ?? 0);
            }
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
     * Menghasilkan nomor invoice berikutnya berdasarkan role user.
     *
     * Format admin: {seq}/SPK/AKI/{bulan romawi}/{yy}
     *   - seq: 3-digit increment mulai dari 060
     *   - Bulan dalam numerals Romawi (I-XII)
     *   - Contoh: 060/SPK/AKI/VII/26
     *
     * Format superadmin: {A}/{B}/PT.AKI/{yy}
     *   - Kedua angka diincrement secara terpisah
     *
     * @param  bool|null  $isAdmin  true untuk admin, null = cek auth()
     * @return string  Nomor invoice berikutnya
     */
    public function generateInvoiceNumber(?bool $isAdmin = null): string
    {
        if ($isAdmin === null) {
            $isAdmin = auth()->check() && auth()->user()->isAdmin();
        }

        if ($isAdmin) {
            return $this->generateAdminInvoiceNumber();
        }

        return $this->generateSuperadminInvoiceNumber();
    }

    /**
     * Format nomor invoice untuk admin: {seq}/SPK/AKI/{bulan romawi}/{yy}.
     *
     * @return string
     */
    private function generateAdminInvoiceNumber(): string
    {
        $year = date('y');
        $month = (int) date('n');
        $romanMonth = self::toRoman($month);
        $suffix = "/SPK/AKI/{$romanMonth}/{$year}";

        $lastInvoice = InvoiceProyek::where('invoice_number', 'like', "%{$suffix}")
            ->orderByRaw('LENGTH(invoice_number) DESC')
            ->orderByDesc('invoice_number')
            ->first();

        if ($lastInvoice && preg_match('/^(\d+)\//', $lastInvoice->invoice_number, $matches)) {
            $next = (int) $matches[1] + 1;
        } else {
            $next = 60;
        }

        return str_pad($next, 3, '0', STR_PAD_LEFT) . $suffix;
    }

    /**
     * Format nomor invoice untuk superadmin: {A}/{B}/PT.AKI/{yy}.
     *
     * @return string
     */
    private function generateSuperadminInvoiceNumber(): string
    {
        $year = date('y');

        $lastInvoice = InvoiceProyek::where('invoice_number', 'like', "%/PT.AKI/{$year}")
            ->orderByRaw('LENGTH(invoice_number) DESC')
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
     * Konversi angka 1-3999 ke numerals Romawi.
     *
     * @param  int  $number
     * @return string
     */
    private static function toRoman(int $number): string
    {
        $map = [
            1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD',
            100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL',
            10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I',
        ];
        $result = '';
        foreach ($map as $value => $symbol) {
            while ($number >= $value) {
                $result .= $symbol;
                $number -= $value;
            }
        }
        return $result;
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
        $data['proyek'] = (auth()->check() && auth()->user()->role === 'superadmin') ? ($data['proyek'] ?? null) : null;

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
            'proyek' => (auth()->check() && auth()->user()->role === 'superadmin') ? ($data['proyek'] ?? null) : null,
            'location' => $data['location'] ?? null,
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
            'ppn' => $data['ppn'] ?? null,
            'selected_payment_accounts' => json_encode($data['selected_payment_accounts'] ?? []),
            'signed_by_id' => $data['signed_by_id'] ?? null,
            'division_id' => $data['division_id'] ?? null,
        ]);
    }

    /**
     * Menghapus beberapa invoice proyek sekaligus (bulk delete).
     *
     * PENTING (kenapa foreach, bukan mass delete):
     * - InvoiceProyek punya InvoiceProyekObserver. Event 'deleted' di observer
     *   membersihkan file bukti pembayaran terkait.
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
