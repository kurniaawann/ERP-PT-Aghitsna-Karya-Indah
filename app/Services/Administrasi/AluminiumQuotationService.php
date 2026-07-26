<?php

namespace App\Services\Administrasi;

use App\Models\Administrasi\AluminiumQuotation;
use App\Models\Administrasi\AluminiumQuotationGroup;
use App\Models\Administrasi\AluminiumQuotationItem;
use App\Models\Finance\PaymentAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk mengelola business logic Penawaran Aluminium (Aluminium Quotation).
 *
 * Service ini bertanggung jawab atas operasi CRUD,
 * generasi nomor penawaran, perhitungan total,
 * dan pengelolaan relasi group + item.
 */
class AluminiumQuotationService
{
    /**
     * Mendapatkan daftar penawaran dengan paginasi dan pencarian.
     *
     * @param  string|null  $search  Kata kunci pencarian
     * @return LengthAwarePaginator
     */
    public function getPaginatedSearch(?string $search): LengthAwarePaginator
    {
        return AluminiumQuotation::with(['groups.items'])
            ->when($search, function ($query, $search) {
                $query->where('quotation_number', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            })
            ->orderBy('sequence_number', 'desc')
            ->paginate(15);
    }

    /**
     * Mendapatkan seluruh rekening pembayaran aktif.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActivePaymentAccounts()
    {
        return PaymentAccount::active()->get();
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
     * Menyimpan penawaran baru beserta groups dan items.
     *
     * @param  array  $validated  Data yang sudah divalidasi oleh FormRequest
     * @return AluminiumQuotation
     */
    public function create(array $validated): AluminiumQuotation
    {
        $groups = json_decode($validated['groups_json'], true);

        $quotationNumber = AluminiumQuotation::generateQuotationNumber();
        preg_match('/^(\d+)\//', $quotationNumber, $matches);
        $seqNumber = (int) $matches[1];

        $totalAmount = $this->calculateGrandTotal($groups);

        return DB::transaction(function () use ($validated, $quotationNumber, $seqNumber, $totalAmount, $groups) {
            $quotation = AluminiumQuotation::create([
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
            ]);

            $this->syncGroups($quotation, $groups);

            return $quotation;
        });
    }

    /**
     * Memperbarui penawaran yang sudah ada.
     *
     * @param  AluminiumQuotation  $quotation  Model penawaran
     * @param  array                $validated  Data yang sudah divalidasi oleh FormRequest
     * @return AluminiumQuotation
     */
    public function update(AluminiumQuotation $quotation, array $validated): AluminiumQuotation
    {
        $groups = json_decode($validated['groups_json'], true);
        $totalAmount = $this->calculateGrandTotal($groups);

        return DB::transaction(function () use ($quotation, $validated, $totalAmount, $groups) {
            // Hapus groups lama (items terhapus otomatis via cascade di DB,
            // tapi kita delete manual untuk keamanan karena cascade mungkin belum dijalankan)
            $quotation->groups()->each(function ($group) {
                $group->items()->delete();
            });
            $quotation->groups()->delete();

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

            // Buat ulang groups + items
            $this->syncGroups($quotation, $groups);

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
        return AluminiumQuotation::with(['groups.items'])
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
        return AluminiumQuotation::with(['groups.items'])->find($quotationNumber);
    }

    // ═══════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Menghitung grand total dari semua groups dan items.
     *
     * @param  array  $groups  Data groups (decoded dari JSON)
     * @return int
     */
    private function calculateGrandTotal(array $groups): int
    {
        $total = 0;
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $total += (int) ($item['total_price'] ?? 0);
            }
        }
        return $total;
    }

    /**
     * Membuat groups dan items untuk sebuah penawaran.
     *
     * @param  AluminiumQuotation  $quotation
     * @param  array                $groups  Data groups (decoded dari JSON)
     * @return void
     */
    private function syncGroups(AluminiumQuotation $quotation, array $groups): void
    {
        foreach ($groups as $groupIndex => $groupData) {
            $subtotal = 0;
            foreach ($groupData['items'] as $item) {
                $subtotal += (int) ($item['total_price'] ?? 0);
            }

            $group = AluminiumQuotationGroup::create([
                'quotation_number' => $quotation->quotation_number,
                'order_number' => $groupIndex + 1,
                'name' => $groupData['name'],
                'subtotal' => $subtotal,
            ]);

            foreach ($groupData['items'] as $itemIndex => $itemData) {
                AluminiumQuotationItem::create([
                    'group_id' => $group->id,
                    'order_number' => $itemIndex + 1,
                    'description' => $itemData['description'],
                    'volume' => $itemData['volume'] ?? null,
                    'unit' => $itemData['unit'] ?? null,
                    'unit_price' => (int) ($itemData['unit_price'] ?? 0),
                    'total_price' => (int) ($itemData['total_price'] ?? 0),
                ]);
            }
        }
    }
}
