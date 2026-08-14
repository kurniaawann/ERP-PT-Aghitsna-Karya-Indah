<?php

namespace App\Exports\Sdm;

use App\Models\Sdm\Attendance;
use Illuminate\Support\Carbon;
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
use PhpOffice\PhpSpreadsheet\Style\Color;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Export class for generating payroll Excel reports.
 *
 * Generates a formatted Excel (.xlsx) report with:
 * - Header section with company name, project, period, and print date
 * - Data table with daily attendance (MING, SEN, SEL, RAB, KAM, JUM, SAB)
 *   and salary breakdown
 * - Summary section with fund recap
 * - Grand total row
 *
 * Uses Maatwebsite Excel with PhpSpreadsheet for formatting.
 */
class PayrollExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    /**
     * Urutan kolom hari seperti yang diminta: MING, SEN, SEL, RAB, KAM, JUM, SAB.
     *
     * Periode kalender dimulai hari Minggu (offset 0), sehingga offset
     * mengikuti urutan hari alami: Minggu, Senin, Selasa, dst.
     *
     * @var array<int, int>
     */
    const DAY_OFFSETS = [0, 1, 2, 3, 4, 5, 6];

    /**
     * Kolom terakhir tabel data (15 kolom: NO s.d DITERIMA).
     */
    const LAST_COLUMN = 'O';

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
     * @param  \Illuminate\Support\Collection|null $teamKasbon  Rekap kasbon divisi (optional)
     */
    public function __construct($payrolls, $month = null, $year = null, $projectName = null, $teamKasbon = null)
    {
        $this->payrolls = $payrolls;
        $this->projectName = $projectName;
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
     * Symbol absensi untuk satu kolom hari, mengikuti template PDF.
     *
     * @param  \App\Models\Sdm\Attendance|null  $attendance
     * @return string
     */
    protected function symbolFor(?Attendance $attendance): string
    {
        if (! $attendance) {
            return '';
        }

        return match ($attendance->status) {
            'hadir' => '✓',
            'lembur' => 'Lb',
            'izin' => 'I',
            'sakit' => 'S',
            'cuti' => 'C',
            default => '',
        };
    }

    /**
     * Generate the data collection for export.
     *
     * Iterates through all payrolls, memuat absensi harian per payroll, dan
     * menghitung total untuk section rekap.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $data = [];
        $no = 1;

        $totalKerja = 0;
        $totalOvertime = 0;
        $totalKasbon = 0;
        $totalNetSalary = 0;

        foreach ($this->payrolls as $payroll) {
            $start = $payroll->period_start_date ? Carbon::parse($payroll->period_start_date) : null;

            $attendances = collect();
            if ($start) {
                $attendances = Attendance::where('employee_id', $payroll->employee_id)
                    ->whereBetween('attendance_date', [
                        $start->copy()->format('Y-m-d'),
                        $start->copy()->addDays(6)->format('Y-m-d'),
                    ])
                    ->get()
                    ->keyBy(fn ($a) => $a->attendance_date?->format('Y-m-d'));
            }

            $dayCells = [];
            foreach (self::DAY_OFFSETS as $offset) {
                $date = $start ? $start->copy()->addDays($offset)->format('Y-m-d') : '';
                $att = $date && isset($attendances[$date]) ? $attendances[$date] : null;
                $dayCells[$date ?: 'empty'] = $this->symbolFor($att);
            }

            $data[] = array_merge([
                'no' => $no++,
                'name' => $payroll->employee->name ?? '-',
                'position' => $payroll->employee->position ?? '-',
                'base_salary' => $payroll->base_salary,
            ], $dayCells, [
                'present_days' => $payroll->present_days,
                'overtime_total' => $payroll->overtime_total,
                'kasbon_deduction' => $payroll->kasbon_deduction,
                'net_salary' => $payroll->net_salary,
            ]);

            $totalKerja += (int) $payroll->base_salary * (int) $payroll->present_days;
            $totalOvertime += (int) $payroll->overtime_total;
            $totalKasbon += (int) $payroll->kasbon_deduction;
            $totalNetSalary += (int) $payroll->net_salary;
        }

        $this->totals = [
            'total_kerja' => $totalKerja,
            'overtime_total' => $totalOvertime,
            'kasbon_deduction' => $totalKasbon,
            'net_salary' => $totalNetSalary,
            'grand_total' => $totalNetSalary,
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
                'NO', 'NAMA PEKERJA', 'JABATAN', 'UPAH/HARI',
                'MING', 'SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB',
                'JML HARI', 'LEMBUR', 'KASBON', 'DITERIMA',
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
        $sheet->mergeCells('A1:'.self::LAST_COLUMN.'1');

        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->mergeCells('A2:'.self::LAST_COLUMN.'2');

        $sheet->mergeCells('A4:B4');
        $sheet->mergeCells('C4:'.self::LAST_COLUMN.'4');
        $sheet->mergeCells('A5:B5');
        $sheet->mergeCells('C5:'.self::LAST_COLUMN.'5');
        $sheet->mergeCells('A6:B6');
        $sheet->mergeCells('C6:'.self::LAST_COLUMN.'6');

        $sheet->getStyle('A4:A6')->applyFromArray(['font' => ['bold' => true]]);
        $sheet->getStyle('A4:C6')->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]]);

        $headerRow = 8;
        $sheet->getStyle("A{$headerRow}:".self::LAST_COLUMN."{$headerRow}")->applyFromArray([
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
     * Handles data row formatting, summary section with fund recap,
     * grand total, and footer.
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
                $sheet->getStyle("A{$dataStartRow}:".self::LAST_COLUMN."{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                $sheet->getStyle("A{$dataStartRow}:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$dataStartRow}:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E{$dataStartRow}:L{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D{$dataStartRow}:D{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("M{$dataStartRow}:".self::LAST_COLUMN."{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

                $sheet->getStyle("B{$dataStartRow}:B{$lastRow}")->getFont()->setBold(true);

                $sheet->getStyle("N{$dataStartRow}:N{$lastRow}")->getFont()->setColor(
                    new Color(Color::COLOR_RED)
                );

                // === SUMMARY SECTION ===
                $startSummaryRow = $lastRow + 2;

                // Fund Recap
                $sheet->setCellValue("A{$startSummaryRow}", "REKAPITULASI DANA");
                $sheet->getStyle("A{$startSummaryRow}")->getFont()->setBold(true);

                $rekapRow = $startSummaryRow + 1;

                $sheet->setCellValue("A{$rekapRow}", "Total Kerja");
                $sheet->setCellValue("C{$rekapRow}", $this->totals['total_kerja']);
                $sheet->getStyle("C{$rekapRow}")->getNumberFormat()->setFormatCode('#,##0');
                $rekapRow++;

                $sheet->setCellValue("A{$rekapRow}", "Total Lembur");
                $sheet->setCellValue("C{$rekapRow}", $this->totals['overtime_total']);
                $sheet->getStyle("C{$rekapRow}")->getNumberFormat()->setFormatCode('+#,##0;-#,##0');
                $rekapRow++;

                $sheet->setCellValue("A{$rekapRow}", "Total Kasbon");
                $sheet->setCellValue("C{$rekapRow}", -$this->totals['kasbon_deduction']);
                $sheet->getStyle("A{$rekapRow}")->getFont()->setColor(
                    new Color(Color::COLOR_RED)
                );
                $sheet->getStyle("C{$rekapRow}")->getFont()->setColor(
                    new Color(Color::COLOR_RED)
                );
                $sheet->getStyle("C{$rekapRow}")->getNumberFormat()->setFormatCode('#,##0');
                $rekapRow++;

                foreach ($this->teamKasbon as $kasbonLabel => $amount) {
                    $sheet->setCellValue("A{$rekapRow}", $kasbonLabel);
                    $sheet->setCellValue("C{$rekapRow}", $amount);
                    $sheet->getStyle("A{$rekapRow}")->getFont()->setColor(
                        new Color(Color::COLOR_RED)
                    );
                    $sheet->getStyle("C{$rekapRow}")->getFont()->setColor(
                        new Color(Color::COLOR_RED)
                    );
                    $sheet->getStyle("C{$rekapRow}")->getNumberFormat()->setFormatCode('#,##0');
                    $rekapRow++;
                }

                $sheet->setCellValue("A{$rekapRow}", "Total Upah Pekerja");
                $sheet->setCellValue("C{$rekapRow}", $this->totals['net_salary']);
                $sheet->getStyle("A{$rekapRow}")->getFont()->setBold(true);
                $sheet->getStyle("C{$rekapRow}")->getFont()->setBold(true);
                $sheet->getStyle("C{$rekapRow}")->getNumberFormat()->setFormatCode('#,##0');
                $rekapRow++;

                // Grand Total
                $grandTotalRow = $rekapRow + 1;
                $sheet->mergeCells("A{$grandTotalRow}:".self::LAST_COLUMN."{$grandTotalRow}");
                $sheet->setCellValue("A{$grandTotalRow}", "TOTAL DIBAYARKAN: Rp " . number_format($this->totals['grand_total'], 0, ',', '.'));

                $sheet->getStyle("A{$grandTotalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
                ]);

                // Footer
                $footerRow = $grandTotalRow + 2;
                $sheet->mergeCells("A{$footerRow}:".self::LAST_COLUMN."{$footerRow}");
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
            'B' => 30,   // NAMA PEKERJA
            'C' => 14,   // JABATAN
            'D' => 14,   // UPAH/HARI
            'E' => 8,    // MING
            'F' => 8,    // SEN
            'G' => 8,    // SEL
            'H' => 8,    // RAB
            'I' => 8,    // KAM
            'J' => 8,    // JUM
            'K' => 8,    // SAB
            'L' => 12,   // JML HARI
            'M' => 14,   // LEMBUR
            'N' => 14,   // KASBON
            'O' => 22,   // DITERIMA
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