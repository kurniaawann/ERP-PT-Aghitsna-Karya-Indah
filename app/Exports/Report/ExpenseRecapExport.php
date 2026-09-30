<?php

namespace App\Exports\Report;

use App\Services\Report\ExpenseMonthlySections;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Style\Style;

/**
 * Export class untuk rekap pengeluaran ke Excel.
 *
 * Menghasilkan file Excel dengan format (per sheet, lihat ExpenseMonthSheet varian "rekap"):
 * - Header: PT. AGHITSNA KARYA INDAH / LAPORAN PENGELUARAN / PERIODE
 * - Data: Grouped by kategori dengan subtotal per kategori
 * - Grand Total: Jumlah keseluruhan
 * - Rekapitulasi: Ringkasan uang masuk, uang keluar, saldo
 * - Tanda tangan: Dibuat/Diperiksa & Direktur
 *
 * Laporan bulanan: bila data mencakup lebih dari satu bulan, dibuat satu sheet
 * per bulan (nama sheet mis. "September 2026", total per bulan, saldo dibawa
 * ke bulan berikutnya) ditambah sheet "Rekap Per Bulan". Export satu bulan
 * tetap satu sheet seperti sebelumnya.
 *
 * @property \Illuminate\Database\Eloquent\Collection $expenseRecaps
 * @property string                                   $periodTitle
 * @property object                                   $totals
 */
class ExpenseRecapExport implements WithMultipleSheets, WithDefaultStyles
{
    /** @var \Illuminate\Database\Eloquent\Collection Data rekap pengeluaran */
    protected $expenseRecaps;

    /** @var string Judul periode untuk header */
    protected $periodTitle;

    /** @var object Total income, expense, dan balance */
    protected $totals;

    /**
     * @param  \Illuminate\Database\Eloquent\Collection $expenseRecaps  Data rekap pengeluaran (urut tanggal menaik)
     * @param  int|null                                 $month           Filter bulan
     * @param  int|null                                 $year            Filter tahun
     * @param  string|null                              $categoryName    Nama kategori (unused)
     * @param  object|null                              $totals          Total income, expense, balance
     */
    public function __construct($expenseRecaps, $month = null, $year = null, $categoryName = null, $totals = null)
    {
        $this->expenseRecaps = $expenseRecaps;
        $this->totals = $totals ?? (object) [
            'total_income' => $expenseRecaps->sum('income_amount'),
            'total_expense' => $expenseRecaps->sum('expense_amount'),
            'balance' => $expenseRecaps->sum('income_amount') - $expenseRecaps->sum('expense_amount'),
        ];

        // Build period title
        $periodParts = [];

        if ($month && $year) {
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');
            $periodParts[] = strtoupper($monthName) . ' ' . $year;
        } elseif ($month) {
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');
            $periodParts[] = 'BULAN ' . strtoupper($monthName);
        } elseif ($year) {
            $periodParts[] = 'TAHUN ' . $year;
        }

        $this->periodTitle = !empty($periodParts) ? implode(' - ', $periodParts) : 'SEMUA PERIODE';
    }

    /**
     * Satu sheet per bulan (+ sheet rekap) bila data lintas bulan; selain itu satu sheet.
     *
     * @return array<int, object>
     */
    public function sheets(): array
    {
        $sections = ExpenseMonthlySections::build($this->expenseRecaps);

        if (count($sections) <= 1) {
            return [
                new ExpenseMonthSheet(
                    $this->expenseRecaps,
                    $this->periodTitle,
                    $this->totals,
                    ExpenseMonthSheet::VARIANT_REKAP,
                    'Laporan_Pengeluaran'
                ),
            ];
        }

        $sheets = [];
        foreach ($sections as $index => $section) {
            $sheets[] = new ExpenseMonthSheet(
                $section['records'],
                strtoupper($section['label']),
                $section['totals'],
                ExpenseMonthSheet::VARIANT_REKAP,
                $section['label'],
                $index > 0 ? $section : null
            );
        }

        $sheets[] = new ExpenseMonthlySummarySheet($sections, 'PERIODE ' . $this->periodTitle, $this->totals, 'FFFF00', 'FFCC00');

        return $sheets;
    }

    /**
     * Font default workbook: Times New Roman 11pt (seragam dengan PDF).
     *
     * @param  \PhpOffice\PhpSpreadsheet\Style\Style $defaultStyle
     * @return array
     */
    public function defaultStyles(Style $defaultStyle)
    {
        return ['font' => ['name' => 'Times New Roman', 'size' => 11]];
    }
}
