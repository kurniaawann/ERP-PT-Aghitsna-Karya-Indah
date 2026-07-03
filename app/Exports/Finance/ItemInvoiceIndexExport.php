<?php

namespace App\Exports\Finance;

use Carbon\Carbon;
use Illuminate\Support\Collection;
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

class ItemInvoiceIndexExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    public function __construct(
        protected Collection $invoices,
        protected ?string $month = null,
        protected ?string $year = null,
    ) {
    }

    public function collection()
    {
        return $this->invoices->map(function ($invoice) {
            return [
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => Carbon::parse($invoice->invoice_date)->format('d/m/Y'),
                'recipient' => $invoice->recipient,
                'project_description' => $invoice->project_description,
                'total_selling' => 'Rp ' . number_format((int) ($invoice->total_selling ?? 0), 0, ',', '.'),
                'total_capital' => 'Rp ' . number_format((int) ($invoice->total_capital ?? 0), 0, ',', '.'),
                'total_profit' => 'Rp ' . number_format((int) ($invoice->total_profit ?? 0), 0, ',', '.'),
                'status' => $invoice->status_label,
            ];
        });
    }

    public function headings(): array
    {
        return [
            [
                'NO INVOICE',
                'TANGGAL',
                'KEPADA',
                'KETERANGAN',
                'TOTAL PENJUALAN',
                'TOTAL MODAL',
                'PROFIT',
                'STATUS',
            ]
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F0F0'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 14,
            'C' => 24,
            'D' => 40,
            'E' => 18,
            'F' => 18,
            'G' => 18,
            'H' => 14,
        ];
    }

    public function title(): string
    {
        return 'Rekap_Invoice_Barang';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->getStyle('A1:H' . $highestRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A1:H' . $highestRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}