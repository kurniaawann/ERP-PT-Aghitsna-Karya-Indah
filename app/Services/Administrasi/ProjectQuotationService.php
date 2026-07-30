<?php

namespace App\Services\Administrasi;

use App\Models\Administrasi\ProjectQuotation;
use App\Models\Administrasi\ProjectQuotationItem;
use App\Services\Finance\PaymentAccountService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk mengelola business logic Penawaran Proyek (Project Quotation).
 *
 * Service ini bertanggung jawab atas operasi CRUD,
 * generasi nomor penawaran, perhitungan total,
 * dan pengelolaan relasi items.
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

    public function __construct(PaymentAccountService $paymentAccountService)
    {
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
        return ProjectQuotation::with(['items'])
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
     * Menyimpan penawaran baru beserta items.
     *
     * @param  array  $validated  Data yang sudah divalidasi oleh FormRequest
     * @return ProjectQuotation
     */
    public function create(array $validated): ProjectQuotation
    {
        $items = json_decode($validated['items_json'], true);

        $quotationNumber = ProjectQuotation::generateQuotationNumber();
        preg_match('/^(\d+)\//', $quotationNumber, $matches);
        $seqNumber = (int) $matches[1];

        $totalAmount = $this->calculateGrandTotal($items);

        return DB::transaction(function () use ($validated, $quotationNumber, $seqNumber, $totalAmount, $items) {
            $quotation = ProjectQuotation::create([
                'quotation_number' => $quotationNumber,
                'sequence_number' => $seqNumber,
                'date' => $validated['date'],
                'subject' => $validated['subject'] ?? 'Penawaran Harga',
                'recipient' => $validated['recipient'],
                'project_description' => $validated['project_description'] ?? null,
                'total_amount' => $totalAmount,
                'amount_in_words' => ucwords(terbilang($totalAmount)) . ' rupiah',
                'selected_payment_accounts' => $validated['selected_payment_accounts'] ?? [],
                'signed_by' => $validated['signed_by'] ?? null,
                'division' => $validated['division'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $this->syncItems($quotation, $items);

            return $quotation;
        });
    }

    /**
     * Memperbarui penawaran yang sudah ada.
     *
     * @param  ProjectQuotation  $quotation  Model penawaran
     * @param  array              $validated  Data yang sudah divalidasi oleh FormRequest
     * @return ProjectQuotation
     */
    public function update(ProjectQuotation $quotation, array $validated): ProjectQuotation
    {
        $items = json_decode($validated['items_json'], true);
        $totalAmount = $this->calculateGrandTotal($items);

        return DB::transaction(function () use ($quotation, $validated, $totalAmount, $items) {
            // Hapus items lama
            $quotation->items()->delete();

            // Update header
            $quotation->update([
                'date' => $validated['date'],
                'subject' => $validated['subject'] ?? 'Penawaran Harga',
                'recipient' => $validated['recipient'],
                'project_description' => $validated['project_description'] ?? null,
                'total_amount' => $totalAmount,
                'amount_in_words' => ucwords(terbilang($totalAmount)) . ' rupiah',
                'selected_payment_accounts' => $validated['selected_payment_accounts'] ?? [],
                'signed_by' => $validated['signed_by'] ?? null,
                'division' => $validated['division'] ?? null,
            ]);

            // Buat ulang items
            $this->syncItems($quotation, $items);

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
        return ProjectQuotation::with(['items'])
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
        return ProjectQuotation::with(['items'])
            ->where('created_by', auth()->id())
            ->find($quotationNumber);
    }

    // ═══════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Menghitung grand total dari semua items.
     *
     * @param  array  $items  Data items (decoded dari JSON)
     * @return int
     */
    private function calculateGrandTotal(array $items): int
    {
        $total = 0;
        foreach ($items as $item) {
            $total += (int) ($item['total_price'] ?? 0);
        }
        return $total;
    }

    /**
     * Membuat items untuk sebuah penawaran.
     *
     * @param  ProjectQuotation  $quotation
     * @param  array              $items  Data items (decoded dari JSON)
     * @return void
     */
    private function syncItems(ProjectQuotation $quotation, array $items): void
    {
        foreach ($items as $index => $itemData) {
            ProjectQuotationItem::create([
                'quotation_number' => $quotation->quotation_number,
                'order_number' => $index + 1,
                'description' => $itemData['description'],
                'volume' => $itemData['volume'] ?? null,
                'unit' => $itemData['unit'] ?? null,
                'unit_price' => (int) ($itemData['unit_price'] ?? 0),
                'total_price' => (int) ($itemData['total_price'] ?? 0),
            ]);
        }
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
