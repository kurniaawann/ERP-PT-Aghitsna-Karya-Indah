<?php

namespace App\Services\Report;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Helper pemecah data rekap pengeluaran per bulan untuk export bulanan.
 *
 * Dipakai oleh export PDF/Excel Rekap Pengeluaran (Finance) dan Laporan
 * Pengeluaran (Report). Laporan pengeluaran adalah laporan bulanan, sehingga
 * export yang mencakup lebih dari satu bulan ditampilkan per bulan (satu
 * bagian/halaman PDF atau satu sheet Excel per bulan) dengan total per bulan.
 *
 * Logika:
 * - Dikelompokkan berdasarkan bulan transaction_date (format Y-m), urut menaik.
 * - Total per bulan dihitung dengan cara yang sama seperti total export
 *   (sum income_amount / expense_amount, balance = income - expense), sehingga
 *   jumlah seluruh bagian = total keseluruhan export.
 * - Saldo dibawa ke bulan berikutnya (opening_balance → closing_balance) hanya
 *   dalam rentang data yang di-export; saldo awal sebelum rentang = 0 (sama
 *   seperti laporan sebelumnya yang tidak menghitung saldo awal antar periode).
 */
class ExpenseMonthlySections
{
    /**
     * Memecah collection rekap pengeluaran menjadi bagian per bulan.
     *
     * @param  \Illuminate\Support\Collection  $expenseRecaps  Data export (sudah terurut tanggal menaik)
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     records: \Illuminate\Support\Collection,
     *     totals: object,
     *     opening_balance: int,
     *     closing_balance: int
     * }>
     */
    public static function build(Collection $expenseRecaps): array
    {
        $sections = [];
        $runningBalance = 0;

        $groups = $expenseRecaps
            ->groupBy(fn ($expense) => Carbon::parse($expense->transaction_date)->format('Y-m'))
            ->sortKeys();

        foreach ($groups as $key => $records) {
            $totalIncome = (int) $records->sum('income_amount');
            $totalExpense = (int) $records->sum('expense_amount');
            $balance = $totalIncome - $totalExpense;

            $sections[] = [
                'key' => $key,
                // Contoh: "September 2026" — juga dipakai sebagai nama sheet Excel (≤ 31 karakter)
                'label' => Carbon::createFromFormat('Y-m-d', $key . '-01')->locale('id')->translatedFormat('F Y'),
                'records' => $records->values(),
                'totals' => (object) [
                    'total_income' => $totalIncome,
                    'total_expense' => $totalExpense,
                    'balance' => $balance,
                ],
                'opening_balance' => $runningBalance,
                'closing_balance' => $runningBalance + $balance,
            ];

            $runningBalance += $balance;
        }

        return $sections;
    }
}
