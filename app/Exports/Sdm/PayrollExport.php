<?php

namespace App\Exports\Sdm;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Export class for generating payroll Excel reports.
 *
 * Generates a formatted Excel (.xlsx) report with:
 * - Header section with company name, project, period, and print date
 * - Data table with attendance summary and salary breakdown
 * - Summary section with additional expenses and fund recap
 * - Grand total row
 *
 * Uses Maatwebsite Excel with PhpSpreadsheet for formatting.
 */
class PayrollExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    /**
     * Payroll collection to export.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $payrolls;

    /**
     * Formatted period text for the report header.
     *
     * @var string
     */
    protected $periodText;

    /**
     * Project name for the report header.
     *
     * @var string|null
     */
    protected $projectName;

    /**
     * Pengeluaran operasional proyek (satu record per periode).
     *
     * @var \Illuminate\Support\Collection|null
     */
    protected $operationalExpenses;

    /**
     * Rekap kasbon divisi (team) untuk section REKAPITULASI DANA.
     *
     * @var \Illuminate\Support\Collection|null
     */
    protected $teamKasbon;

    /**
     * Calculated totals for the summary section.
     *
     * @var array|null
     */
    protected $totals;

    /**
     * Create a new PayrollExport instance.
     *
     * @param  \Illuminate\Support\Collection  $payrolls   Payroll data with employee relation loaded
     * @param  int|null                        $month      Filter by month (optional)
     * @param  int|null                        $year       Filter by year (optional)
     * @param  string|null                     $projectName  Project name for header (optional)
     * @param  \Illuminate\Support\Collection|null $operationalExpenses  Biaya operasional proyek per periode (optional)
     * @param  \Illuminate\Support\Collection|null $teamKasbon  Rekap kasbon divisi (optional)
     */
    public function __construct($payrolls, $month = null, $year = null, $projectName = null, $operationalExpenses = null, $teamKasbon = null)
    {
        $this->payrolls = $payrolls;
        $this->projectName = $projectName;
        $this->operationalExpenses = $operationalExpenses ?? collect();
        $this->teamKasbon = $teamKasbon ?? collect();

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        if ($month && $year) {
            $this->periodText = $monthNames[$month] . ' ' . $year;
        } elseif ($year) {
            $this->periodText = 'Tahun ' . $year;
        } else {
            $this->periodText = 'Semua Periode';
        }
    }

    /**
     * Generate the data collection for export.
     *
     * Iterates through all payrolls, calculates totals, and
     * prepares expense details for the summary section.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $data = [];
        $no = 1;

        $totalBaseSalary = 0;
        $totalKerja = 0;
        $totalOvertime = 0;
        $totalKasbon = 0;
        $totalNetSalary = 0;

        foreach ($this->payrolls as $payroll) {
            $data[] = [
                'no' => $no++,
                'name' => $payroll->employee->name ?? '-',
                'present_days' => $payroll->present_days,
                'overtime_days' => $payroll->overtime_days,
                'permission_days' => $payroll->permission_days,
                'sick_days' => $payroll->sick_days,
                'leave_days' => $payroll->leave_days,
                'base_salary' => $payroll->base_salary,
                'overtime_total' => $payroll->overtime_total,
                'kasbon_deduction' => $payroll->kasbon_deduction,
                'net_salary' => $payroll->net_salary,
            ];

            $totalBaseSalary += $payroll->base_salary;
            $totalKerja += $payroll->base_salary * $payroll->present_days;
            $totalOvertime += $payroll->overtime_total;
            $totalKasbon += $payroll->kasbon_deduction;
            $totalNetSalary += $payroll->net_salary;
        }

        // Aggregate expense items dari biaya operasional proyek per periode
        // (satu record per periode) plus data legacy additional_expenses_notes.
        $allExpenses = [];
        foreach ($this->operationalExpenses as $expense) {
            $items = is_array($expense->expense_items) ? $expense->expense_items : [];
            foreach ($items as $exp) {
                $name = $exp['name'] ?? 'Lain-lain';
                $amount = (int) ($exp['amount'] ?? 0);
                if (!isset($allExpenses[$name])) {
                    $allExpenses[$name] = 0;
                }
                $allExpenses[$name] += $amount;
            }
        }
        foreach ($this->payrolls as $payroll) {
            if ($payroll->additional_expenses_notes) {
                $expenses = json_decode($payroll->additional_expenses_notes, true);
                if ($expenses && is_array($expenses)) {
                    foreach ($expenses as $exp) {
                        $name = $exp['name'] ?? 'Lain-lain';
                        $amount = $exp['amount'] ?? 0;
                        if (!isset($allExpenses[$name])) {
                            $allExpenses[$name] = 0;
                        }
                        $allExpenses[$name] += $amount;
                    }
                }
            }
        }
        $totalExpenses = array_sum($allExpenses);

        $this->totals = [
            'base_salary' => $totalBaseSalary,
            'total_kerja' => $totalKerja,
            'overtime_total' => $totalOvertime,
            'kasbon_deduction' => $totalKasbon,
            'net_salary' => $totalNetSalary,
            'total_expenses' => $totalExpenses,
            'expenses_details' => $allExpenses,
            'grand_total' => $totalNetSalary + $totalExpenses,
        ];

        return collect($data);
    }

    /**
     * Get the header rows for the Excel sheet.
     *
     * Includes company name, report title, metadata, and column headers.
     *
     * @return array<int, array<int|string>>
     */
    public function headings(): array
    {
        return [
            ['PT. AGHITSNA KARYA INDAH'],
            ['DAFTAR ABSENSI & PENGGAJIAN PEKERJA'],
            [''],
            ['PROYEK', ':', $this->projectName ?? 'Semua Proyek'],
            ['PERIODE', ':', $this->periodText],
            ['TANGGAL CETAK', ':', date('d/m/Y H:i')],
            [''],
            [
                'NO', 'NAMA PEKERJA', 'HADIR', 'LEMBUR', 'IZIN',
                'SAKIT', 'CUTI', 'UPAH HARIAN', 'BONUS LEMBUR', 'POT. KASBON', 'DITERIMA',
            ],
        ];
    }

    /**
     * Apply styles to the worksheet (header formatting).
     *
     * @param  Worksheet  $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->mergeCells('A1:K1');

        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->mergeCells('A2:K2');

        $sheet->mergeCells('A4:B4');
        $sheet->mergeCells('C4:K4');
        $sheet->mergeCells('A5:B5');
        $sheet->mergeCells('C5:K5');
        $sheet->mergeCells('A6:B6');
        $sheet->mergeCells('C6:K6');

        $sheet->getStyle('A4:A6')->applyFromArray(['font' => ['bold' => true]]);
        $sheet->getStyle('A4:C6')->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]]);

        $headerRow = 8;
        $sheet->getStyle("A{$headerRow}:K{$headerRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F4F4']],
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(25);

        return [];
    }

    /**
     * Register Excel events for post-sheet processing.
     *
     * Handles data row formatting, summary section with expenses
     * and fund recap, grand total, and footer.
     *
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $dataStartRow = 9;

                // Format data rows
                $sheet->getStyle("A{$dataStartRow}:K{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                $sheet->getStyle("A{$dataStartRow}:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$dataStartRow}:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("H{$dataStartRow}:K{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

                $sheet->getStyle("B{$dataStartRow}:B{$lastRow}")->getFont()->setBold(true);

                $sheet->getStyle("J{$dataStartRow}:J{$lastRow}")->getFont()->setColor(
                    new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED)
                );

                // === SUMMARY SECTION ===
                $startSummaryRow = $lastRow + 2;

                // Left: Additional Expenses
                $sheet->setCellValue("B{$startSummaryRow}", "PENGELUARAN TAMBAHAN (OPERASIONAL)");
                $sheet->getStyle("B{$startSummaryRow}")->getFont()->setBold(true);

                $currentRow = $startSummaryRow + 1;
                if (count($this->totals['expenses_details']) > 0) {
                    foreach ($this->totals['expenses_details'] as $name => $amount) {
                        $sheet->setCellValue("B{$currentRow}", $name);
                        $sheet->setCellValue("C{$currentRow}", $amount);
                        $sheet->getStyle("C{$currentRow}")->getNumberFormat()->setFormatCode('#,##0');
                        $currentRow++;
                    }
                    $sheet->setCellValue("B{$currentRow}", "Total Tambahan");
                    $sheet->setCellValue("C{$currentRow}", $this->totals['total_expenses']);
                    $sheet->getStyle("B{$currentRow}")->getFont()->setBold(true);
                    $sheet->getStyle("C{$currentRow}")->getFont()->setBold(true);
                    $sheet->getStyle("C{$currentRow}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("B{$currentRow}:C{$currentRow}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_DASHED);
                } else {
                    $sheet->setCellValue("B{$currentRow}", "- Tidak ada pengeluaran tambahan -");
                    $sheet->getStyle("B{$currentRow}")->getFont()->setItalic(true);
                }

                // Right: Fund Recap
                $sheet->setCellValue("H{$startSummaryRow}", "REKAPITULASI DANA");
                $sheet->getStyle("H{$startSummaryRow}")->getFont()->setBold(true);

                $rekapRow = $startSummaryRow + 1;

                $sheet->setCellValue("H{$rekapRow}", "Total Kerja");
                $sheet->setCellValue("K{$rekapRow}", $this->totals['total_kerja']);
                $sheet->getStyle("K{$rekapRow}")->getNumberFormat()->setFormatCode('#,##0');
                $rekapRow++;

                $sheet->setCellValue("H{$rekapRow}", "Total Lembur");
                $sheet->setCellValue("K{$rekapRow}", $this->totals['overtime_total']);
                $sheet->getStyle("K{$rekapRow}")->getNumberFormat()->setFormatCode('+#,##0;-#,##0');
                $rekapRow++;

                $sheet->setCellValue("H{$rekapRow}", "Total Kasbon");
                $sheet->setCellValue("K{$rekapRow}", -$this->totals['kasbon_deduction']);
                $sheet->getStyle("H{$rekapRow}")->getFont()->setColor(
                    new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED)
                );
                $sheet->getStyle("K{$rekapRow}")->getFont()->setColor(
                    new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED)
                );
                $sheet->getStyle("K{$rekapRow}")->getNumberFormat()->setFormatCode('#,##0');
                $rekapRow++;

                foreach ($this->teamKasbon as $divisionName => $amount) {
                    $sheet->setCellValue("H{$rekapRow}", "Kasbon Divisi " . $divisionName);
                    $sheet->setCellValue("K{$rekapRow}", $amount);
                    $sheet->getStyle("H{$rekapRow}")->getFont()->setColor(
                        new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED)
                    );
                    $sheet->getStyle("K{$rekapRow}")->getFont()->setColor(
                        new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED)
                    );
                    $sheet->getStyle("K{$rekapRow}")->getNumberFormat()->setFormatCode('#,##0');
                    $rekapRow++;
                }

                $sheet->setCellValue("H{$rekapRow}", "Total Upah Pekerja");
                $sheet->setCellValue("K{$rekapRow}", $this->totals['net_salary']);
                $sheet->getStyle("H{$rekapRow}")->getFont()->setBold(true);
                $sheet->getStyle("K{$rekapRow}")->getFont()->setBold(true);
                $sheet->getStyle("K{$rekapRow}")->getNumberFormat()->setFormatCode('#,##0');
                $rekapRow++;

                $sheet->setCellValue("H{$rekapRow}", "Total Pengeluaran Tambahan");
                $sheet->setCellValue("K{$rekapRow}", $this->totals['total_expenses']);
                $sheet->getStyle("K{$rekapRow}")->getNumberFormat()->setFormatCode('#,##0');
                $rekapRow++;

                // Grand Total
                $grandTotalRow = max($currentRow, $rekapRow) + 1;
                $sheet->mergeCells("A{$grandTotalRow}:K{$grandTotalRow}");
                $sheet->setCellValue("A{$grandTotalRow}", "TOTAL DIBAYARKAN: Rp " . number_format($this->totals['grand_total'], 0, ',', '.'));

                $sheet->getStyle("A{$grandTotalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
                ]);

                // Footer
                $footerRow = $grandTotalRow + 2;
                $sheet->mergeCells("A{$footerRow}:K{$footerRow}");
                $sheet->setCellValue("A{$footerRow}", "Dicetak otomatis oleh Sistem ERP PT. Aghitsna Karya Indah pada " . date('d/m/Y H:i'));
                $sheet->getStyle("A{$footerRow}")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 8, 'color' => ['rgb' => 'AAAAAA']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            },
        ];
    }

    /**
     * Get column widths for the worksheet.
     *
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 6,    // NO
            'B' => 35,   // NAMA PEKERJA
            'C' => 10,   // HADIR
            'D' => 10,   // LEMBUR
            'E' => 10,   // IZIN
            'F' => 10,   // SAKIT
            'G' => 10,   // CUTI
            'H' => 18,   // UPAH HARIAN
            'I' => 18,   // BONUS LEMBUR
            'J' => 18,   // KASBON
            'K' => 22,   // DITERIMA
        ];
    }

    /**
     * Get the worksheet title.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Laporan_Payroll';
    }
}
