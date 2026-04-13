<?php

namespace App\Exports\Finance;

use App\Models\Finance\PurchaseInvoice;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PurchaseInvoiceExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected $request;

    public function __construct($request = null)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = PurchaseInvoice::query();

        // Filter pencarian
        if ($this->request && $this->request->has('search') && $this->request->search != '') {
            $search = $this->request->search;
            $query->where('material_name', 'like', "%{$search}%")
                ->orWhere('item_name', 'like', "%{$search}%")
                ->orWhere('npwp', 'like', "%{$search}%");
        }

        return $query->orderBy('date', 'desc')
            ->get()
            ->map(function ($invoice, $index) {
                return [
                    $index + 1,
                    $invoice->date->format('d/m/Y'),
                    $invoice->material_name,
                    $invoice->npwp,
                    $invoice->tax_number_code,
                    $invoice->item_name,
                    'Rp ' . number_format($invoice->selling_price, 0, ',', '.'),
                    'Rp ' . number_format($invoice->ppn_tax, 0, ',', '.'),
                    'Rp ' . number_format($invoice->selling_price + $invoice->ppn_tax, 0, ',', '.'),
                    $invoice->notes ?? '-',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'NO',
            'TANGGAL',
            'NAMA MATERIAL',
            'NPWP',
            'KODE NOMOR SERI PAJAK',
            'NAMA BARANG',
            'HARGA JUAL',
            'PPN PENGENAAN PAJAK',
            'TOTAL',
            'KETERANGAN',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 12,
            'C' => 25,
            'D' => 18,
            'E' => 20,
            'F' => 20,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Apply borders to all data rows
                $highestRow = $sheet->getHighestRow();
                for ($row = 2; $row <= $highestRow; $row++) {
                    for ($col = 'A'; $col <= 'J'; $col++) {
                        $sheet->getStyle($col . $row)
                            ->getBorders()
                            ->getAllBorders()
                            ->setBorderStyle(Border::BORDER_THIN);
                    }
                }

                // Center align NO and TANGGAL columns
                $sheet->getStyle('A2:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B2:B' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
