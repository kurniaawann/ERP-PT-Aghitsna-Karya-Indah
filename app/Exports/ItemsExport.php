<?php

namespace App\Exports;

use App\Models\Inventory\Items;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    WithColumnWidths
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};

class ItemsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths
{
    public function collection()
    {
        return Items::orderBy('id_item', 'asc')->get();
    }

    public function headings(): array
    {
        return [
            ['STOCK HOLLOW  DI GI', '', '', '', ''],
            ['Nama Barang', 'Quantity', 'Harga Modal', 'Total', 'Harga Jual']
        ];
    }

    public function map($item): array
    {
        $total = $item->quantity * $item->capital_price;

        $capitalPrice = 'Rp' . number_format($item->capital_price, 0, ',', '.');
        $sellingPrice = 'Rp' . number_format($item->selling_price, 0, ',', '.');
        $totalFormatted = 'Rp' . number_format($total, 0, ',', '.');

        return [
            $item->name_item,
            $item->quantity,
            $capitalPrice,
            $totalFormatted,
            $sellingPrice,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Header utama style (baris 1)
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'FFFF00',
                ],
            ],
        ]);

        $sheet->mergeCells('A1:E1');

        // Header kolom style (baris 2)
        $sheet->getStyle('A2:E2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E0E0E0',
                ],
            ],
        ]);

        // Border style
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:E' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Stock Hollow';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,  // Nama Barang
            'B' => 12,  // Quantity
            'C' => 18,  // Harga Modal
            'D' => 18,  // Total
            'E' => 18,  // Harga Jual
        ];
    }
}
