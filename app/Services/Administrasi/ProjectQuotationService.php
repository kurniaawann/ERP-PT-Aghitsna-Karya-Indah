<?php

namespace App\Services\Administrasi;

use App\Exports\Administrasi\ProjectQuotationAdminExport;
use App\Exports\Administrasi\ProjectQuotationExport;
use App\Exports\Administrasi\ProjectQuotationMultiExport;
use App\Models\Administrasi\ProjectQuotation;
use App\Models\Finance\InvoiceProyek;
use App\Services\Finance\InvoiceCalculatorService;
use App\Services\Finance\PaymentAccountService;
use App\Services\Finance\ProyekInvoiceService;
use App\Services\InputNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk mengelola business logic Penawaran Proyek (Project Quotation).
 *
 * Service ini bertanggung jawab atas operasi CRUD, generasi nomor penawaran,
 * perhitungan total & discount, pengelolaan items (format flat JSON seperti
 * Invoice Proyek), dan pembuatan Invoice Proyek otomatis (snapshot) setiap
 * kali penawaran baru disimpan.
 *
 * Alur best practice:
 * - Penawaran yang disimpan otomatis membuat Invoice Proyek (status belum
 *   lunas) pada transaksi DB yang sama.
 * - Invoice tetap dapat dibuat mandiri tanpa penawaran (modul Finance).
 * - Edit/hapus penawaran TIDAK mengubah invoice yang sudah dibuat (snapshot).
 * - DP & PPN dapat diisi pada penawaran dan ikut terbawa ke invoice otomatis
 *   (snapshot). Invoice tetap dapat dibuat mandiri tanpa penawaran.
 */
class ProjectQuotationService
{
    /**
     * Karakter wildcard LIKE yang perlu di-escape untuk pencarian.
     */
    private const LIKE_WILDCARDS = ['%', '_'];

    /**
     * Jumlah data per halaman untuk paginasi.
     */
    private const PER_PAGE = 15;

    protected PaymentAccountService $paymentAccountService;

    public function __construct(
        PaymentAccountService $paymentAccountService,
        private InvoiceCalculatorService $calculator,
        private ProyekInvoiceService $invoiceService
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
        return ProjectQuotation::query()
            ->where('created_by', auth()->id())
            ->when($search, function ($query, $search) {
                $escapedSearch = $this->escapeLikeWildcards($search);
                $query->where('quotation_number', 'like', "%{$escapedSearch}%")
                    ->orWhere('recipient', 'like', "%{$escapedSearch}%")
                    ->orWhere('subject', 'like', "%{$escapedSearch}%");
            })
            ->orderBy('sequence_number', 'desc')
            ->paginate(self::PER_PAGE);
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
     * @return string  Format: {A}/{B}/PT.AKI/{yy}
     */
    public function generateQuotationNumber(): string
    {
        return ProjectQuotation::generateQuotationNumber();
    }

    /**
     * Menyimpan penawaran baru beserta items (flat JSON) dan otomatis
     * membuat Invoice Proyek (snapshot) dalam satu transaksi DB.
     *
     * @param  array  $validated  Data yang sudah divalidasi oleh FormRequest
     * @return ProjectQuotation
     */
    public function create(array $validated): ProjectQuotation
    {
        $items = $this->normalizeItems($validated['items'] ?? '[]');

        $quotationNumber = ProjectQuotation::generateQuotationNumber();
        preg_match('/^(\d+)\//', $quotationNumber, $matches);
        $seqNumber = (int) $matches[1];

        $totalAmount = $this->calculateItemsTotal($items);
        $discountAmount = $this->calculator->calculateDiscountAmount(
            $totalAmount,
            $validated['discount_type'] ?? null,
            isset($validated['discount_value']) && $validated['discount_value'] !== '' ? (float) $validated['discount_value'] : null
        );
        $totalAfterDiscount = ($discountAmount > 0 && $discountAmount < $totalAmount)
            ? $totalAmount - (int) $discountAmount
            : null;
        $dpAmount = $this->calculator->calculateDpAmount(
            $totalAmount,
            $totalAfterDiscount,
            $validated['dp_type'] ?? null,
            isset($validated['dp_value']) && $validated['dp_value'] !== '' ? (float) $validated['dp_value'] : null
        );

        return DB::transaction(function () use ($validated, $quotationNumber, $seqNumber, $totalAmount, $discountAmount, $totalAfterDiscount, $dpAmount, $items) {
            $quotation = ProjectQuotation::create([
                'quotation_number' => $quotationNumber,
                'sequence_number' => $seqNumber,
                'date' => $validated['date'],
                'subject' => $validated['subject'] ?? 'Penawaran Harga',
                'attachment' => $validated['attachment'] ?? null,
                'recipient' => $validated['recipient'],
                'project_description' => $validated['project_description'] ?? 'Ditempat',
                'location' => $validated['location'] ?? null,
                'total_amount' => $totalAmount,
                'items' => $items,
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => $validated['discount_value'] ?? null,
                'ppn' => $validated['ppn'] ?? null,
                'total_after_discount' => $totalAfterDiscount,
                'dp_type' => $validated['dp_type'] ?? null,
                'dp_value' => $validated['dp_value'] ?? null,
                'dp_amount' => $dpAmount > 0 ? (int) $dpAmount : null,
                'amount_in_words' => ucwords(terbilang($totalAmount)) . ' rupiah',
                'selected_payment_accounts' => $validated['selected_payment_accounts'] ?? [],
                'signed_by_id' => $validated['signed_by_id'] ?? null,
                'division_id' => $validated['division_id'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $this->createLinkedInvoice($quotation, $items, $totalAmount, $discountAmount, $totalAfterDiscount);

            return $quotation;
        });
    }

    /**
     * Memperbarui penawaran yang sudah ada.
     *
     * Catatan: snapshot — memperbarui penawaran TIDAK mengubah invoice
     * otomatis yang telah dibuat sebelumnya.
     *
     * @param  ProjectQuotation  $quotation  Model penawaran
     * @param  array              $validated  Data yang sudah divalidasi oleh FormRequest
     * @return ProjectQuotation
     */
    public function update(ProjectQuotation $quotation, array $validated): ProjectQuotation
    {
        $items = $this->normalizeItems($validated['items'] ?? []);
        $totalAmount = $this->calculateItemsTotal($items);
        $discountAmount = $this->calculator->calculateDiscountAmount(
            $totalAmount,
            $validated['discount_type'] ?? null,
            isset($validated['discount_value']) && $validated['discount_value'] !== '' ? (float) $validated['discount_value'] : null
        );
        $totalAfterDiscount = ($discountAmount > 0 && $discountAmount < $totalAmount)
            ? $totalAmount - (int) $discountAmount
            : null;
        $dpAmount = $this->calculator->calculateDpAmount(
            $totalAmount,
            $totalAfterDiscount,
            $validated['dp_type'] ?? null,
            isset($validated['dp_value']) && $validated['dp_value'] !== '' ? (float) $validated['dp_value'] : null
        );

        return DB::transaction(function () use ($quotation, $validated, $totalAmount, $totalAfterDiscount, $dpAmount, $items) {
            $quotation->update([
                'date' => $validated['date'],
                'subject' => $validated['subject'] ?? 'Penawaran Harga',
                'attachment' => $validated['attachment'] ?? null,
                'recipient' => $validated['recipient'],
                'project_description' => $validated['project_description'] ?? 'Ditempat',
                'location' => $validated['location'] ?? null,
                'total_amount' => $totalAmount,
                'items' => $items,
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => $validated['discount_value'] ?? null,
                'ppn' => $validated['ppn'] ?? null,
                'total_after_discount' => $totalAfterDiscount,
                'dp_type' => $validated['dp_type'] ?? null,
                'dp_value' => $validated['dp_value'] ?? null,
                'dp_amount' => $dpAmount > 0 ? (int) $dpAmount : null,
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
        return ProjectQuotation::whereIn('quotation_number', $ids)
            ->where('created_by', auth()->id())
            ->delete();
    }

    /**
     * Mendapatkan penawaran berdasarkan array IDs untuk export.
     *
     * @param  array  $ids  Array nomor penawaran
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByIds(array $ids)
    {
        return ProjectQuotation::query()
            ->whereIn('quotation_number', $ids)
            ->where('created_by', auth()->id())
            ->orderBy('sequence_number')
            ->get();
    }

    /**
     * Mendapatkan satu penawaran berdasarkan nomor.
     *
     * @param  string  $quotationNumber
     * @return ProjectQuotation|null
     */
    public function findByNumber(string $quotationNumber): ?ProjectQuotation
    {
        return ProjectQuotation::query()
            ->where('created_by', auth()->id())
            ->find($quotationNumber);
    }

    /**
     * Menentukan view PDF yang dipakai saat cetak berdasarkan role user.
     *
     * Role 'admin' menggunakan desain khusus (project-quotation-admin-pdf).
     *
     * @return string
     */
    public function getPdfView(): string
    {
        return auth()->check() && auth()->user()->role === 'admin'
            ? 'exports.administrasi.project-quotation-admin-pdf'
            : 'exports.administrasi.project-quotation-pdf';
    }

    /**
     * Menentukan class export Excel yang dipakai saat cetak berdasarkan role user.
     *
     * Role 'admin' menggunakan desain khusus (ProjectQuotationAdminExport).
     *
     * @param  string  $quotationNumber
     * @return ProjectQuotationExport|ProjectQuotationAdminExport
     */
    public function getExcelExport(string $quotationNumber)
    {
        $exportClass = auth()->check() && auth()->user()->role === 'admin'
            ? ProjectQuotationAdminExport::class
            : ProjectQuotationExport::class;

        return new $exportClass($quotationNumber);
    }

    /**
     * Membuat multi-export Excel (beberapa penawaran) sesuai role user.
     *
     * @param  array  $quotationNumbers
     * @return ProjectQuotationMultiExport
     */
    public function getExcelMultiExport(array $quotationNumbers): ProjectQuotationMultiExport
    {
        $exportClass = auth()->check() && auth()->user()->role === 'admin'
            ? ProjectQuotationAdminExport::class
            : ProjectQuotationExport::class;

        return new ProjectQuotationMultiExport($quotationNumbers, $exportClass);
    }

    // ═══════════════════════════════════════════════════════════════
    // PUBLIC HELPERS (dipakai controller/views)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Normalisasi items penawaran dari input request.
     *
     * Format item identik dengan Invoice Proyek:
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
     * Membuat Invoice Proyek (snapshot) dari penawaran yang baru dibuat.
     *
     * Seluruh field disalin dari penawaran; invoice berstatus "Belum Lunas".
     * Kolom quotation_number menautkan invoice ke penawaran asal.
     * Discount, DP, dan PPN dari penawaran ikut terbawa ke invoice (snapshot).
     *
     * @param  ProjectQuotation  $quotation  Penawaran yang baru dibuat
     * @param  array             $items      Items ter-normalisasi
     * @param  int               $totalAmount  Total amount
     * @param  int               $discountAmount  Besaran diskon (0 bila tidak ada)
     * @param  int|null          $totalAfterDiscount  Total setelah diskon
     * @return InvoiceProyek
     */
    private function createLinkedInvoice(
        ProjectQuotation $quotation,
        array $items,
        int $totalAmount,
        int $discountAmount,
        ?int $totalAfterDiscount
    ): InvoiceProyek {
        return InvoiceProyek::create([
            'invoice_number' => $this->invoiceService->generateInvoiceNumber(),
            'quotation_number' => $quotation->quotation_number,
            'invoice_date' => $quotation->date,
            'recipient' => $quotation->recipient,
            'regarding' => $quotation->subject,
            'project_description' => $quotation->project_description,
            'location' => $quotation->location,
            'items' => $items,
            'total_amount' => $totalAmount,
            'discount_type' => $quotation->discount_type,
            'discount_value' => $quotation->discount_value,
            'total_after_discount' => $totalAfterDiscount,
            'dp_type' => $quotation->dp_type,
            'dp_value' => $quotation->dp_value,
            'dp_amount' => $quotation->dp_amount,
            'ppn' => $quotation->ppn,
            'selected_payment_accounts' => $quotation->selected_payment_accounts ?? [],
            'signed_by_id' => $quotation->signed_by_id,
            'division_id' => $quotation->division_id,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Meng-escape karakter wildcard LIKE untuk mencegah hasil pencarian yang tidak diinginkan.
     *
     * @param  string  $value  Nilai yang akan di-escape
     * @return string Nilai yang sudah di-escape
     */
    private function escapeLikeWildcards(string $value): string
    {
        foreach (self::LIKE_WILDCARDS as $wildcard) {
            $value = str_replace($wildcard, '\\'.$wildcard, $value);
        }

        return $value;
    }
}
