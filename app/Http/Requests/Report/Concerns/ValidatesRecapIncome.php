<?php

namespace App\Http\Requests\Report\Concerns;

use App\Models\Finance\ProjectRecap;
use App\Models\Report\TransactionCategory;
use App\Services\InputNormalizer;

/**
 * Validasi nominal "uang masuk" (kategori INCOME) pada Laporan Keuangan
 * Proyek agar tidak melebihi sisa pembayaran rekap proyek.
 *
 * Identifikasi uang masuk lewat tipe kategori INCOME (bukan kode UANG_MASUK)
 * sehingga konsisten dengan ProjectRecap::getIncomePayments(). Item yang
 * berasal dari bukti pembayaran (payment_proof_id terisi) dikecualikan karena
 * sudah dihitung lewat payment_proofs.
 */
trait ValidatesRecapIncome
{
    /**
     * Total income dari item "Bon" yang disubmit.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  \Illuminate\Support\Collection<int, \App\Models\Report\ProjectFinancialReportItem>|null  $existingItems
     */
    protected function submittedIncomeTotal(array $items, $existingItems = null): int
    {
        $categoryIds = collect($items)
            ->pluck('transaction_category_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $types = empty($categoryIds)
            ? collect()
            : TransactionCategory::whereIn('id', $categoryIds)->pluck('type', 'id');

        $existingById = $existingItems
            ? $existingItems->keyBy(fn ($item) => (int) $item->id)
            : collect();

        return collect($items)->sum(function ($item) use ($types, $existingById) {
            $type = $types->get($item['transaction_category_id'] ?? null);

            if ($type !== TransactionCategory::TYPE_INCOME) {
                return 0;
            }

            $itemId = trim((string) ($item['id'] ?? ''));

            if ($itemId !== '') {
                $existing = $existingById->get((int) $itemId);

                if ($existing && $existing->payment_proof_id) {
                    return 0;
                }
            }

            return (int) InputNormalizer::normalizeCurrency($item['expense_amount'] ?? null);
        });
    }

    /**
     * Sisa yang boleh dibayar lewat "uang masuk" Laporan Keuangan:
     * Total RAB - DP (uang masuk RAB) - bukti pembayaran.
     */
    protected function recapAllowedIncome(ProjectRecap $recap, ?int $submittedTotal = null): int
    {
        $total = $submittedTotal ?? $recap->getTotalAmount();

        if ($recap->rab_number && ($rabTotal = $recap->rab?->total_amount) !== null) {
            $total = (int) $rabTotal;
        }

        $proofTotal = (int) max(0, $recap->paymentProofs()->sum('amount'));

        return max(0, $total - $recap->getDpAmount() - $proofTotal);
    }
}
