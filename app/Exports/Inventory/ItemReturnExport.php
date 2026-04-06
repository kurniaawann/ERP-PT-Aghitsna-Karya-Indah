<?php

namespace App\Exports\Inventory;

use App\Models\Inventory\ItemReturn;
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

class ItemReturnExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths
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
        return ItemReturn::query()
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
            ->orderBy('id_return', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            ['RETURN BARANG', '', '', '', '', ''],
            ['ID Return', 'Barang', 'Jumlah', 'Alasan', 'Tanggal', '']
        ];
    }

    public function map($record): array
    {
        return [
            $record->id_return,
            $record->item->name_item ?? '-',
            $record->quantity,
            $record->alasan ?? '-',
            $record->tanggal->format('d-m-Y'),
            '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3E5F5']],
        ]);
        $sheet->mergeCells('A1:F1');

        $sheet->getStyle('A2:F2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E1BEE7']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->getStyle('A3:F1000')->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

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
        return 'Return Barang';
    }
}
