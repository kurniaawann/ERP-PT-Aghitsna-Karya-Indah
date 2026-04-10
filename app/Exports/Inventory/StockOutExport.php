<?php

namespace App\Exports\Inventory;

use App\Models\Inventory\ItemStockOut;
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

class StockOutExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths
{
    protected $search;
    protected $month;
    protected $year;

    public function __construct($search = null, $month = null, $year = null)
    {
        $this->search = $search;
        $this->month = $month;
        $this->year = $year;
    }

    public function collection()
    {
        return ItemStockOut::query()
            ->with('item')
            ->when($this->search, function ($query, $search) {
                $query->where('id_stock_out', 'like', "%{$search}%")
                    ->orWhere('id_item', 'like', "%{$search}%")
                    ->orWhereHas('item', function ($q) use ($search) {
                        $q->where('name_item', 'like', "%{$search}%");
                    });
            })
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
            ->orderBy('id_stock_out', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            ['BARANG KELUAR', '', '', '', '', '', ''],
            ['ID Keluar', 'Barang', 'Jumlah', 'Sisa Barang', 'Tanggal', 'Proyek', '']
        ];
    }

    public function map($record): array
    {
        return [
            $record->id_stock_out,
            $record->item->name_item ?? '-',
            $record->quantity,
            $record->remaining_quantity ?? '-',
            $record->tanggal->format('d-m-Y'),
            $record->project_name ?? '-',
            '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFEBEE']],
        ]);
        $sheet->mergeCells('A1:G1');

        $sheet->getStyle('A2:G2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EF9A9A']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->getStyle('A3:G1000')->applyFromArray([
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
            'F' => 20,
            'G' => 12,
        ];
    }

    public function title(): string
    {
        return 'Barang Keluar';
    }
}
