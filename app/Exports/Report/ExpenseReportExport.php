<?php

namespace App\Exports\Report;

use App\Services\Report\ExpenseMonthlySections;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Style\Style;

/**
 * Export class untuk Laporan Pengeluaran (Laporan Akhir) ke Excel.
 *
 * Format per sheet mengikuti ExpenseMonthSheet varian "laporan" (per kategori,
 * SUB TOTAL, JUMLAH, rekapitulasi, tanda tangan).
 *
 * Laporan bulanan: bila data mencakup lebih dari satu bulan, dibuat satu sheet
 * per bulan (nama sheet mis. "September 2026", total per bulan, saldo dibawa
 * ke bulan berikutnya) ditambah sheet "Rekap Per Bulan". Export satu bulan
 * tetap satu sheet seperti sebelumnya.
 */
class ExpenseReportExport implements WithMultipleSheets, WithDefaultStyles
{
    protected $expenseRecaps;
    protected $periodTitle;
    protected $totals;

    /**
     * @param  \Illuminate\Support\Collection $expenseRecaps  Data export (urut tanggal menaik)
     * @param  string                         $periodTitle    Label periode (mis. "BULAN SEPTEMBER 2026", "TAHUN 2026")
     * @param  object                         $totals         total_income, total_expense, balance
     */
    public function __construct($expenseRecaps, $periodTitle, $totals)
    {
        $this->expenseRecaps = $expenseRecaps;
        $this->periodTitle = $periodTitle;
        $this->totals = $totals;
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
                    ExpenseMonthSheet::VARIANT_LAPORAN,
                    'Laporan_Pengeluaran'
                ),
            ];
        }

        $sheets = [];
        foreach ($sections as $index => $section) {
            $sheets[] = new ExpenseMonthSheet(
                $section['records'],
                'BULAN ' . strtoupper($section['label']),
                $section['totals'],
                ExpenseMonthSheet::VARIANT_LAPORAN,
                $section['label'],
                $index > 0 ? $section : null
            );
        }

        $sheets[] = new ExpenseMonthlySummarySheet($sections, $this->periodTitle, $this->totals, '9EA974', 'E5C327');

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
