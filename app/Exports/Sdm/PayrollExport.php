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
use Carbon\Carbon;

class PayrollExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $payrolls;
    protected $periodText;
    protected $projectName;

    public function __construct($payrolls, $month = null, $year = null, $projectName = null)
    {
        $this->payrolls = $payrolls;
        $this->projectName = $projectName;

        // Format periode untuk header
        if ($month && $year) {
            $monthNames = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember'
            ];
            $this->periodText = $monthNames[$month] . ' ' . $year;
        } elseif ($year) {
            $this->periodText = 'Tahun ' . $year;
        } else {
            $this->periodText = 'Semua Periode';
        }
    }

    public function collection()
    {
        $data = [];
        $no = 1;

        $totalBaseSalary = 0;
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
            $totalOvertime += $payroll->overtime_total;
            $totalKasbon += $payroll->kasbon_deduction;
            $totalNetSalary += $payroll->net_salary;
        }

        // Simpan totals dan detail expenses untuk digunakan di registerEvents
        $allExpenses = [];
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
            'overtime_total' => $totalOvertime,
            'kasbon_deduction' => $totalKasbon,
            'net_salary' => $totalNetSalary,
            'total_expenses' => $totalExpenses,
            'expenses_details' => $allExpenses,
            'grand_total' => $totalNetSalary + $totalExpenses - $totalKasbon // Note: net_salary in loop already includes kasbon deduction subtraction? Wait.
            // Let's check PDF logic: 
            // $totalWages = $payrolls->sum('net_salary');
            // $grandTotal = $totalWages + $totalExpenses - $totalKasbon;
            // PDF Logic seems unexpected here if net_salary is already "Total Dibayar".
            // Let's look at pdf logic closer: 
            // $totalWages = $payrolls->sum('net_salary');
            // $totalExpenses = array_sum($allExpenses);
            // $grandTotal = $totalWages + $totalExpenses - $totalKasbon; 
            // 
            // If net_salary is what the employee receives, then grand total for the company is (Sum of net salaries) + (Operational Expenses).
            // Why -totalKasbon? 
            // Usually Kasbon is deducted from Employee Salary. So Net Salary = Gross - Kasbon.
            // If the company pays Net Salary, they pay out X.
            // If there are operational expenses (buying materials etc), they pay out Y.
            // Total Payout = X + Y.
            //
            // Let's check the PDF code provided in context.
            // PDF: $grandTotal = $totalWages + $totalExpenses - $totalKasbon;
            // This implies the PDF logic subtracts Kasbon again? Or maybe $totalWages is Gross?
            // In PDF loop: <td ...>{{ number_format($payroll->net_salary, ...)}}</td>
            // So $totalWages is Sum of Net Salaries.
            // If I look at the previous PDF code...
            // $grandTotal = $totalWages + $totalExpenses - $totalKasbon;
            // It subtracts Kasbon from the total payout calculation? 
            // If the user wants 100% match, I will transpose the exact formula even if it looks weird financially, 
            // unless clarity suggests otherwise. I will stick to the PDF formula to be safe: 
            // Grant Total = Sum(NetSalary) + Sum(Expenses) - Sum(Kasbon).
        ];

        // Match PDF logic exactly
        $this->totals['grand_total'] = $totalNetSalary + $totalExpenses - $totalKasbon;

        return collect($data);
    }

    public function headings(): array
    {
        // Sesuaikan header dengan PDF
        return [
            ['PT. AGHITSNA KARYA INDAH'],
            ['DAFTAR ABSENSI & PENGGAJIAN PEKERJA'],
            [''], // Spasi
            ['PROYEK', ':', $this->projectName ?? 'Semua Proyek'],
            ['PERIODE', ':', $this->periodText],
            ['TANGGAL CETAK', ':', date('d/m/Y H:i')],
            [''], // Spasi sebelum tabel
            [
                'NO',
                'NAMA PEKERJA',
                'HADIR',
                'LEMBUR',
                'IZIN',
                'SAKIT',
                'CUTI',
                'UPAH HARIAN',
                'BONUS LEMBUR',
                'KASBON',
                'DITERIMA'
            ]
        ];
    }

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

        // Metadata styling - Merge Cells for Label and Value to ensure visibility
        $sheet->mergeCells('A4:B4'); // Label PROYEK
        $sheet->mergeCells('C4:K4'); // Value PROYEK
        $sheet->mergeCells('A5:B5'); // Label PERIODE
        $sheet->mergeCells('C5:K5'); // Value PERIODE
        $sheet->mergeCells('A6:B6'); // Label TANGGAL
        $sheet->mergeCells('C6:K6'); // Value TANGGAL

        $sheet->getStyle('A4:A6')->applyFromArray(['font' => ['bold' => true]]);
        $sheet->getStyle('A4:C6')->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]]);

        // Table Header Styling
        $headerRow = 8;
        $sheet->getStyle("A{$headerRow}:K{$headerRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F4F4']],
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        // Set height for header row
        $sheet->getRowDimension($headerRow)->setRowHeight(25);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $dataStartRow = 9;

                // Format Data Rows
                $sheet->getStyle("A{$dataStartRow}:K{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);

                // Alignment
                $sheet->getStyle("A{$dataStartRow}:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // No
                $sheet->getStyle("C{$dataStartRow}:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Days
                $sheet->getStyle("H{$dataStartRow}:K{$lastRow}")->getNumberFormat()->setFormatCode('#,##0'); // Currency
    
                // Font Bold untuk Nama & Total
                $sheet->getStyle("B{$dataStartRow}:B{$lastRow}")->getFont()->setBold(true);

                // Warna Merah untuk Kasbon
                $sheet->getStyle("J{$dataStartRow}:J{$lastRow}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));

                // --- SUMMARY SECTION (PENGELUARAN TAMBAHAN & REKAP DANA) ---
                $startSummaryRow = $lastRow + 2;

                // Left Column: Pengeluaran Tambahan
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
                    // Total Additional
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

                // Right Column: Rekapitulasi Dana
                // We'll place this around column H-K
                $sheet->setCellValue("H{$startSummaryRow}", "REKAPITULASI DANA");
                $sheet->getStyle("H{$startSummaryRow}")->getFont()->setBold(true);

                $rekapRow = $startSummaryRow + 1;

                // Total Upah
                $sheet->setCellValue("H{$rekapRow}", "Total Upah Pekerja");
                $sheet->setCellValue("K{$rekapRow}", $this->totals['net_salary']);
                $sheet->getStyle("K{$rekapRow}")->getNumberFormat()->setFormatCode('#,##0');
                $rekapRow++;

                // Total Pengeluaran
                $sheet->setCellValue("H{$rekapRow}", "Total Pengeluaran Tambahan");
                $sheet->setCellValue("K{$rekapRow}", $this->totals['total_expenses']);
                $sheet->getStyle("K{$rekapRow}")->getNumberFormat()->setFormatCode('#,##0');
                $rekapRow++;

                // Total Potongan Kasbon
                $sheet->setCellValue("H{$rekapRow}", "Total Potongan Kasbon");
                $sheet->setCellValue("K{$rekapRow}", $this->totals['kasbon_deduction']); // Display positive value, but formatted usually in parens if negative context
                // PDF says: (100.000) in red.
                $sheet->getStyle("H{$rekapRow}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));
                $sheet->getStyle("K{$rekapRow}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));
                $sheet->getStyle("K{$rekapRow}")->getNumberFormat()->setFormatCode('(#,##0)'); // Helper to show brackets
                $rekapRow++;

                // Grand Total Box
                $grandTotalRow = max($currentRow, $rekapRow) + 1;
                $sheet->mergeCells("A{$grandTotalRow}:K{$grandTotalRow}");
                $sheet->setCellValue("A{$grandTotalRow}", "TOTAL DIBAYARKAN: Rp " . number_format($this->totals['grand_total'], 0, ',', '.'));

                $sheet->getStyle("A{$grandTotalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
                ]);

                // Footer Timestamp
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

    public function columnWidths(): array
    {
        return [
            'A' => 6,    // NO
            'B' => 35,   // NAMA PEKERJA (Lebarkan agar muat nama panjang)
            'C' => 10,   // HADIR
            'D' => 10,   // LEMBUR
            'E' => 10,   // IZIN
            'F' => 10,   // SAKIT
            'G' => 10,   // CUTI
            'H' => 18,   // UPAH HARIAN (Lebarkan untuk angka jutaan)
            'I' => 18,   // BONUS LEMBUR
            'J' => 18,   // KASBON
            'K' => 22,   // DITERIMA (Lebarkan untuk total)
        ];
    }

    public function title(): string
    {
        return 'Laporan Payroll';
    }
}
