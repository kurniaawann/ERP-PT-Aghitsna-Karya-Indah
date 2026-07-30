<?php

namespace App\Exports\Inventory;

use App\Models\Inventory\Items;
use Maatwebsite\Excel\Concerns\{
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    WithColumnWidths
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};

/**
 * Export Data Barang ke format Excel (.xlsx).
 *
 * Menghasilkan file Excel dengan header "STOCK HOLLOW DI GI"
 * berisi daftar barang beserta quantity, harga modal, total, dan harga jual.
 */
class ItemsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths
{
    /**
     * Query data yang akan di-export.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return Items::orderBy('id_item', 'asc');
    }

    /**
     * Header kolom Excel.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            ['STOCK HOLLOW  DI GI', '', '', '', ''],
            ['Nama Barang', 'Quantity', 'Harga Modal', 'Total', 'Harga Jual']
        ];
    }

    /**
     * Mapping data setiap baris barang.
     *
     * @param  Items  $item
     * @return array
     */
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

    /**
     * Style header dan border untuk seluruh sheet.
     *
     * @param  Worksheet  $sheet
     * @return array
     */
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

        // Border style untuk seluruh data
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

    /**
     * Judul sheet Excel.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Stock_Hollow';
    }

    /**
     * Lebar kolom Excel.
     *
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 12,
            'C' => 18,
            'D' => 18,
            'E' => 18,
        ];
    }
}
