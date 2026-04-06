<?php

namespace App\Exports\Inventory;

use App\Models\Inventory\ItemStockIn;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    WithColumnWidths
};
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockInExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths
{
    protected $month;
    protected $year;

    public function __construct($month = null, $year = null)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function collection()
    {
        return ItemStockIn::query()
            ->with('item')
            ->when($this->month, function ($query, $month) {
                $query->whereMonth('tanggal', $month);
            })
            ->when($this->year, function ($query, $year) {
                $query->whereYear('tanggal', $year);
            })
            ->when($this->month, function ($query, $month) {
                $query->whereMonth('tanggal', $month);
            })
            ->when($this->year, function ($query, $year) {
                $query->whereYear('tanggal', $year);
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('id_stock_in', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            ['BARANG MASUK', '', '', '', '', ''],
            ['ID Masuk', 'Barang', 'Jumlah', 'Harga Modal', 'Total', 'Tanggal']
        ];
    }

    public function map($record): array
    {
        return [
            $record->id_stock_in,
            $record->item->name_item ?? '-',
            $record->quantity,
            'Rp' . number_format($record->capital_price, 0, ',', '.'),
            'Rp' . number_format($record->total_capital, 0, ',', '.'),
            $record->tanggal->format('d-m-Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
        ]);

        $sheet->getStyle('A2:F2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C8E6C9']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->getStyle('A3:F1000')->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->mergeCells('A1:F1');

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 25,
            'C' => 10,
            'D' => 15,
            'E' => 15,
            'F' => 12,
        ];
    }

    public function title(): string
    {
        return 'Barang Masuk';
    }
}
