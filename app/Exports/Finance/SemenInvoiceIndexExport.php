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

/**
 * Export rekap Invoice Semen ke Excel.
 *
 * Merangkum seluruh invoice (sesuai filter bulan/tahun/pencarian) menjadi
 * tabel per baris: No Invoice, Tanggal, Nama Proyek, Jumlah Proyek, dan Total.
 */
class SemenInvoiceIndexExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
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
                'project_names' => $invoice->nama_proyek_list,
                'project_count' => $invoice->proyek_count,
                'total_amount' => 'Rp ' . number_format((int) ($invoice->total_amount ?? 0), 0, ',', '.'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            [
                'NO INVOICE',
                'TANGGAL',
                'NAMA PROYEK',
                'JML PROYEK',
                'TOTAL',
            ]
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->applyFromArray([
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
            'A' => 22,
            'B' => 14,
            'C' => 50,
            'D' => 12,
            'E' => 20,
        ];
    }

    public function title(): string
    {
        return 'Rekap_Invoice_Semen';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->getStyle('A1:E' . $highestRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A1:E' . $highestRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}