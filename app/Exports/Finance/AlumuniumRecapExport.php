<?php

namespace App\Exports\Finance;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AlumuniumRecapExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $invoices;
    protected $totals;
    protected $periodTitle;

    public function __construct($invoices, $totals, $periodTitle)
    {
        $this->invoices = $invoices;
        $this->totals = $totals;
        $this->periodTitle = $periodTitle;
    }

    public function collection()
    {
        $data = [];
        $no = 1;

        foreach ($this->invoices as $invoice) {
            $data[] = [
                'no' => $no++,
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date?->format('d/m/Y') ?? '-',
                'recipient' => $invoice->recipient ?? '-',
                'project_description' => $invoice->project_description ?? '-',
                'total_amount' => 'Rp ' . number_format((int) $invoice->getNetAmount(), 0, ',', '.'),
                'paid_amount' => 'Rp ' . number_format((int) $invoice->getTotalPaidAmount(), 0, ',', '.'),
                'remaining_amount' => 'Rp ' . number_format((int) $invoice->getRemainingAmount(), 0, ',', '.'),
                'status' => strtoupper($invoice->payment_status_label),
            ];
        }

        $data[] = [
            'no' => '',
            'invoice_number' => '',
            'invoice_date' => '',
            'recipient' => 'TOTAL',
            'project_description' => 'JUMLAH REKAP INVOICE ALUMUNIUM',
            'total_amount' => 'Rp ' . number_format((int) $this->totals->total_invoice, 0, ',', '.'),
            'paid_amount' => 'Rp ' . number_format((int) $this->totals->total_paid, 0, ',', '.'),
            'remaining_amount' => 'Rp ' . number_format((int) $this->totals->total_remaining, 0, ',', '.'),
            'status' => '',
        ];

        return collect($data);
    }

    public function headings(): array
    {
        return [
            ['PT. AGHITSNA KARYA INDAH'],
            ['LAPORAN REKAP INVOICE ALUMUNIUM'],
            ['PERIODE ' . $this->periodTitle],
            [
                'NO',
                'NO INVOICE',
                'TANGGAL',
                'KEPADA',
                'PROYEK',
                'TOTAL INVOICE',
                'SUDAH DIBAYAR',
                'SISA',
                'STATUS',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->mergeCells('A3:I3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getRowDimension(3)->setRowHeight(18);

        $sheet->getStyle('A4:I4')->applyFromArray([
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

        $sheet->getRowDimension(4)->setRowHeight(30);

        $sheet->getStyle('A5:I' . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle('A5:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B5:B' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C5:C' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D5:D' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('E5:E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('F5:H' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('I5:I' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A' . $highestRow . ':I' . $highestRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2F0D9'],
            ],
        ]);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 16,
            'C' => 12,
            'D' => 24,
            'E' => 34,
            'F' => 18,
            'G' => 18,
            'H' => 18,
            'I' => 14,
        ];
    }

    public function title(): string
    {
        return 'Rekap Alumunium';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $highestRow = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getDelegate()->getStyle('E5:E' . $highestRow)->getAlignment()->setWrapText(true);
            },
        ];
    }
}