<?php

namespace App\Services\Administrasi;

use App\Models\Administrasi\AluminiumQuotation;
use App\Models\Finance\InvoiceAlumunium;
use App\Services\Finance\AlumuniumInvoiceService;
use App\Services\Finance\InvoiceCalculatorService;
use App\Services\Finance\PaymentAccountService;
use App\Services\InputNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk mengelola business logic Penawaran Aluminium (Aluminium Quotation).
 *
 * Service ini bertanggung jawab atas operasi CRUD, generasi nomor penawaran,
 * perhitungan total/discount/DP, pengelolaan items (format flat JSON seperti
 * Invoice Alumunium), dan pembuatan Invoice Alumunium otomatis (snapshot)
 * setiap kali penawaran baru disimpan.
 *
 * Alur best practice:
 * - Penawaran yang disimpan otomatis membuat Invoice Alumunium (status belum
 *   lunas) pada transaksi DB yang sama.
 * - Invoice tetap dapat dibuat mandiri tanpa penawaran (modul Finance).
 * - Edit/hapus penawaran TIDAK mengubah invoice yang sudah dibuat (snapshot).
 */
class AluminiumQuotationService
{
    protected PaymentAccountService $paymentAccountService;

    public function __construct(
        PaymentAccountService $paymentAccountService,
        private InvoiceCalculatorService $calculator,
        private AlumuniumInvoiceService $invoiceService
    ) {
        $this->paymentAccountService = $paymentAccountService;
    }

    /**
     * Mendapatkan daftar penawaran dengan paginasi dan pencarian.
     *
     * @param  string|null  $search  Kata kunci pencarian
     * @return LengthAwarePaginator
     */
    public function getPaginatedSearch(?string $search): LengthAwarePaginator
    {
        return AluminiumQuotation::query()
            ->when($search, function ($query, $search) {
                $query->where('quotation_number', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            })
            ->orderBy('sequence_number', 'desc')
            ->paginate(15);
    }

    /**
     * Mendapatkan seluruh rekening pembayaran aktif (menggunakan cache dari PaymentAccountService).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActivePaymentAccounts()
    {
        return $this->paymentAccountService->getActiveAccounts();
    }

    /**
     * Generate nomor penawaran berikutnya.
     *
     * @return string  Format: {A}/{B}/ALU/{yy}
     */
    public function generateQuotationNumber(): string
    {
        return AluminiumQuotation::generateQuotationNumber();
    }

    /**
     * Menyimpan penawaran baru beserta items (flat JSON) dan otomatis
     * membuat Invoice Alumunium (snapshot) dalam satu transaksi DB.
     *
     * @param  array  $validated  Data yang sudah divalidasi oleh FormRequest
     * @return AluminiumQuotation
     */
    public function create(array $validated): AluminiumQuotation
    {
        $items = $this->normalizeItems($validated['items'] ?? '[]');

        $quotationNumber = AluminiumQuotation::generateQuotationNumber();
        preg_match('/^(\d+)\//', $quotationNumber, $matches);
        $seqNumber = (int) $matches[1];

        $totalAmount = $this->calculateItemsTotal($items);
        $calculations = $this->calculateDiscountAndDp($validated, $totalAmount);

        return DB::transaction(function () use ($validated, $quotationNumber, $seqNumber, $totalAmount, $calculations, $items) {
            $quotation = AluminiumQuotation::create([
                'quotation_number' => $quotationNumber,
                'sequence_number' => $seqNumber,
                'date' => $validated['date'],
                'subject' => $validated['subject'] ?? 'Penawaran Harga',
                'recipient' => $validated['recipient'],
                'project_description' => $validated['project_description'] ?? 'Ditempat',
                'total_amount' => $totalAmount,
                'items' => $items,
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => $validated['discount_value'] ?? null,
                'total_after_discount' => $calculations['totalAfterDiscount'],
                'dp_type' => $validated['dp_type'] ?? null,
                'dp_value' => $validated['dp_value'] ?? null,
                'dp_amount' => $calculations['dpAmount'] > 0 ? $calculations['dpAmount'] : null,
                'amount_in_words' => ucwords(terbilang($totalAmount)) . ' rupiah',
                'selected_payment_accounts' => $validated['selected_payment_accounts'] ?? [],
                'signed_by_id' => $validated['signed_by_id'] ?? null,
                'division_id' => $validated['division_id'] ?? null,
            ]);

            $this->createLinkedInvoice($quotation, $validated, $items, $totalAmount, $calculations);

            return $quotation;
        });
    }

    /**
     * Memperbarui penawaran yang sudah ada.
     *
     * Catatan: snapshot — memperbarui penawaran TIDAK mengubah invoice
     * otomatis yang telah dibuat sebelumnya.
     *
     * @param  AluminiumQuotation  $quotation  Model penawaran
     * @param  array                $validated  Data yang sudah divalidasi oleh FormRequest
     * @return AluminiumQuotation
     */
    public function update(AluminiumQuotation $quotation, array $validated): AluminiumQuotation
    {
        $items = $this->normalizeItems($validated['items'] ?? []);
        $totalAmount = $this->calculateItemsTotal($items);
        $calculations = $this->calculateDiscountAndDp($validated, $totalAmount);

        return DB::transaction(function () use ($quotation, $validated, $totalAmount, $calculations, $items) {
            $quotation->update([
                'date' => $validated['date'],
                'subject' => $validated['subject'] ?? 'Penawaran Harga',
                'recipient' => $validated['recipient'],
                'project_description' => $validated['project_description'] ?? 'Ditempat',
                'total_amount' => $totalAmount,
                'items' => $items,
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => $validated['discount_value'] ?? null,
                'total_after_discount' => $calculations['totalAfterDiscount'],
                'dp_type' => $validated['dp_type'] ?? null,
                'dp_value' => $validated['dp_value'] ?? null,
                'dp_amount' => $calculations['dpAmount'] > 0 ? $calculations['dpAmount'] : null,
                'amount_in_words' => ucwords(terbilang($totalAmount)) . ' rupiah',
                'selected_payment_accounts' => $validated['selected_payment_accounts'] ?? [],
                'signed_by_id' => $validated['signed_by_id'] ?? null,
                'division_id' => $validated['division_id'] ?? null,
            ]);

            return $quotation->fresh();
        });
    }

    /**
     * Menghapus beberapa penawaran sekaligus (bulk delete).
     *
     * @param  array  $ids  Array nomor penawaran
     * @return int    Jumlah data yang dihapus
     */
    public function deleteByIds(array $ids): int
    {
        return AluminiumQuotation::whereIn('quotation_number', $ids)->delete();
    }

    /**
     * Mendapatkan penawaran berdasarkan array IDs untuk export.
     *
     * @param  array  $ids  Array nomor penawaran
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByIds(array $ids)
    {
        return AluminiumQuotation::query()
            ->whereIn('quotation_number', $ids)
            ->orderBy('sequence_number')
            ->get();
    }

    /**
     * Mendapatkan satu penawaran berdasarkan nomor.
     *
     * @param  string  $quotationNumber
     * @return AluminiumQuotation|null
     */
    public function findByNumber(string $quotationNumber): ?AluminiumQuotation
    {
        return AluminiumQuotation::find($quotationNumber);
    }

    // ═══════════════════════════════════════════════════════════════
    // PUBLIC HELPERS (dipakai controller/views)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Normalisasi items penawaran dari input request.
     *
     * Format item identik dengan Invoice Alumunium:
     * {keterangan, volume, satuan, harga}. Menerima input JSON string
     * (form tambah) maupun array (form edit).
     *
     * @param  mixed  $items  Item dari request (JSON string atau array)
     * @return array  Item yang sudah dinormalisasi
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

    // ═══════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Menghitung discount & DP dari data form (selaras InvoiceCalculatorService).
     *
     * @param  array  $data  Data penawaran (discount_type, discount_value, dp_type, dp_value)
     * @param  int    $totalAmount  Total amount sebelum discount
     * @return array{discountAmount: int, totalAfterDiscount: int|null, dpAmount: int}
     */
    private function calculateDiscountAndDp(array $data, int $totalAmount): array
    {
        $discountAmount = $this->calculator->calculateDiscountAmount(
            $totalAmount,
            $data['discount_type'] ?? null,
            isset($data['discount_value']) && $data['discount_value'] !== '' ? (float) $data['discount_value'] : null
        );

        $totalAfterDiscount = ($discountAmount > 0 && $discountAmount < $totalAmount)
            ? $totalAmount - (int) $discountAmount
            : null;

        $dpAmount = $this->calculator->calculateDpAmount(
            $totalAmount,
            $totalAfterDiscount ?? $totalAmount,
            $data['dp_type'] ?? null,
            isset($data['dp_value']) && $data['dp_value'] !== '' ? (float) $data['dp_value'] : null
        );

        return [
            'discountAmount' => (int) $discountAmount,
            'totalAfterDiscount' => $totalAfterDiscount,
            'dpAmount' => (int) $dpAmount,
        ];
    }

    /**
     * Membuat Invoice Alumunium (snapshot) dari penawaran yang baru dibuat.
     *
     * Seluruh field disalin dari penawaran; invoice berstatus "Belum Lunas".
     * Kolom quotation_number menautkan invoice ke penawaran asal.
     *
     * @param  AluminiumQuotation  $quotation  Penawaran yang baru dibuat
     * @param  array               $data       Data form (sudah divalidasi)
     * @param  array               $items      Items ter-normalisasi
     * @param  int                 $totalAmount  Total amount
     * @param  array               $calculations  Hasil kalkulasi discount/DP
     * @return InvoiceAlumunium
     */
    private function createLinkedInvoice(AluminiumQuotation $quotation, array $data, array $items, int $totalAmount, array $calculations): InvoiceAlumunium
    {
        return InvoiceAlumunium::create([
            'invoice_number' => $this->invoiceService->generateInvoiceNumber(),
            'quotation_number' => $quotation->quotation_number,
            'invoice_date' => $data['date'],
            'recipient' => $data['recipient'],
            'regarding' => $data['subject'] ?? 'Penawaran Harga',
            'project_description' => $quotation->project_description,
            'items' => $items,
            'total_amount' => $totalAmount,
            'discount_type' => $data['discount_type'] ?? null,
            'discount_value' => $data['discount_value'] ?? null,
            'total_after_discount' => $calculations['totalAfterDiscount'],
            'dp_type' => $data['dp_type'] ?? null,
            'dp_value' => $data['dp_value'] ?? null,
            'dp_amount' => $calculations['dpAmount'] > 0 ? $calculations['dpAmount'] : null,
            'selected_payment_accounts' => $data['selected_payment_accounts'] ?? [],
            'signed_by_id' => $data['signed_by_id'] ?? null,
            'division_id' => $data['division_id'] ?? null,
        ]);
    }
}
