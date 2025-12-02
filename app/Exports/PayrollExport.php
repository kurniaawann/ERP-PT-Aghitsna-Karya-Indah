<?php

namespace App\Exports;

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

    public function __construct($payrolls, $month = null, $year = null)
    {
        $this->payrolls = $payrolls;

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
        $totalDeduction = 0;
        $totalOvertime = 0;
        $totalNetSalary = 0;

        foreach ($this->payrolls as $payroll) {
            $data[] = [
                'no' => $no++,
                'employee_code' => $payroll->employee->employee_code ?? '-',
                'employee_name' => $payroll->employee->name ?? '-',
                'position' => $payroll->employee->jabatan ?? '-',
                'period' => $payroll->formatted_period,
                'base_salary' => 'Rp ' . number_format($payroll->base_salary, 0, ',', '.'),
                'present_days' => $payroll->present_days . ' hari',
                'permission_days' => $payroll->permission_days . ' hari',
                'sick_days' => $payroll->sick_days . ' hari',
                'leave_days' => $payroll->leave_days . ' hari',
                'overtime_days' => $payroll->overtime_days . ' hari',
                'deduction' => 'Rp ' . number_format($payroll->deduction_amount, 0, ',', '.'),
                'overtime_total' => 'Rp ' . number_format($payroll->overtime_total, 0, ',', '.'),
                'net_salary' => 'Rp ' . number_format($payroll->net_salary, 0, ',', '.'),
                'status' => strtoupper($payroll->status),
                'payment_date' => $payroll->payment_date ? Carbon::parse($payroll->payment_date)->format('d/m/Y') : '-',
            ];

            $totalBaseSalary += $payroll->base_salary;
            $totalDeduction += $payroll->deduction_amount;
            $totalOvertime += $payroll->overtime_total;
            $totalNetSalary += $payroll->net_salary;
        }

        // Baris total
        $data[] = [
            'no' => '',
            'employee_code' => '',
            'employee_name' => '',
            'position' => '',
            'period' => 'TOTAL',
            'base_salary' => 'Rp ' . number_format($totalBaseSalary, 0, ',', '.'),
            'present_days' => '',
            'permission_days' => '',
            'sick_days' => '',
            'leave_days' => '',
            'overtime_days' => '',
            'deduction' => 'Rp ' . number_format($totalDeduction, 0, ',', '.'),
            'overtime_total' => 'Rp ' . number_format($totalOvertime, 0, ',', '.'),
            'net_salary' => 'Rp ' . number_format($totalNetSalary, 0, ',', '.'),
            'status' => '',
            'payment_date' => '',
        ];

        return collect($data);
    }

    public function headings(): array
    {
        return [
            ['LAPORAN PAYROLL (PENGGAJIAN KARYAWAN)'],
            ['PERIODE: ' . strtoupper($this->periodText)],
            [''],
            [
                'NO',
                'KODE KARYAWAN',
                'NAMA KARYAWAN',
                'JABATAN',
                'PERIODE',
                'GAJI POKOK',
                'HADIR',
                'IZIN',
                'SAKIT',
                'CUTI',
                'LEMBUR',
                'POTONGAN',
                'TOTAL LEMBUR',
                'GAJI BERSIH',
                'STATUS',
                'TGL BAYAR',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // Merge title
        $sheet->mergeCells('A1:P1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Merge subtitle
        $sheet->mergeCells('A2:P2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Empty row
        $sheet->getRowDimension(3)->setRowHeight(5);

        // Header row styling
        $sheet->getStyle('A4:P4')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(4)->setRowHeight(40);

        // Data rows border
        $dataEndRow = $highestRow;
        $sheet->getStyle('A5:P' . $dataEndRow)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Align columns
        $sheet->getStyle('A5:A' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B5:B' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E5:E' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F5:F' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G5:K' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('L5:N' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('O5:O' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('P5:P' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Style untuk baris total (baris terakhir)
                $sheet->getStyle('A' . $highestRow . ':P' . $highestRow)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFD966'],
                    ],
                    'font' => ['bold' => true, 'size' => 11],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                    ],
                ]);

                // Merge cells untuk "TOTAL"
                $sheet->mergeCells('A' . $highestRow . ':E' . $highestRow);
                $sheet->getStyle('E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Styling untuk status berdasarkan nilai
                for ($row = 5; $row < $highestRow; $row++) {
                    $status = $sheet->getCell('O' . $row)->getValue();

                    if ($status === 'PAID') {
                        $sheet->getStyle('O' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'C6EFCE'],
                            ],
                            'font' => ['color' => ['rgb' => '006100']],
                        ]);
                    } elseif ($status === 'DRAFT') {
                        $sheet->getStyle('O' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFC7CE'],
                            ],
                            'font' => ['color' => ['rgb' => '9C0006']],
                        ]);
                    }
                }
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,     // NO
            'B' => 15,    // KODE KARYAWAN
            'C' => 25,    // NAMA KARYAWAN
            'D' => 20,    // JABATAN
            'E' => 15,    // PERIODE
            'F' => 18,    // GAJI POKOK
            'G' => 10,    // HADIR
            'H' => 10,    // IZIN
            'I' => 10,    // SAKIT
            'J' => 10,    // CUTI
            'K' => 10,    // LEMBUR
            'L' => 18,    // POTONGAN
            'M' => 18,    // TOTAL LEMBUR
            'N' => 18,    // GAJI BERSIH
            'O' => 12,    // STATUS
            'P' => 14,    // TGL BAYAR
        ];
    }

    public function title(): string
    {
        return 'Laporan Payroll';
    }
}
