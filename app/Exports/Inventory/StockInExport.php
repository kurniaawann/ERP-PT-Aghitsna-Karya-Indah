<?php

namespace App\Exports\Inventory;

use App\Models\Inventory\ItemStockIn;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockInExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected $search;
    protected $month;
    protected $year;
    protected $totalOverall = 0;
    protected $totalQuantity = 0;

    public function __construct($search = null, $month = null, $year = null)
    {
        $this->search = $search;
        $this->month = $month;
        $this->year = $year;
    }

    public function collection()
    {
        $stockIns = ItemStockIn::query()
            ->with('item')
            ->when($this->search, function ($query, $search) {
                $query->where('id_stock_in', 'like', "%{$search}%")
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
            ->orderBy('tanggal', 'desc')
            ->orderBy('id_stock_in', 'desc')
            ->get();

        $this->totalQuantity = $stockIns->sum('quantity');
        $this->totalOverall = $stockIns->sum('total_capital');

        return $stockIns;
    }

    public function headings(): array
    {
        $monthName = $this->month ? \DateTime::createFromFormat('!m', $this->month)->format('F') : '';
        $yearName = $this->year ?? '';
        $period = $monthName || $yearName ? "Periode: {$monthName} {$yearName}" : 'Semua Data';

        return [
            ['PT AGHITSNA KARYA INDAH'],
            ['LAPORAN BARANG MASUK'],
            [$period],
            [],
            ['No', 'ID Masuk', 'Nama Barang', 'Jumlah', 'Harga Modal', 'Total', 'Tanggal']
        ];
    }

    public function map($record): array
    {
        static $number = 0;
        $number++;

        return [
            $number,
            $record->id_stock_in,
            $record->item->name_item ?? '-',
            $record->quantity,
            $record->capital_price,
            $record->total_capital,
            $record->tanggal->format('d-m-Y'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 20,
            'C' => 30,
            'D' => 10,
            'E' => 15,
            'F' => 15,
            'G' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(12);

        $sheet->getStyle('A1:G1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2:G2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:G3')->getFont()->setItalic(true)->setSize(12);

        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('A1:G3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A5:G5')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAEAEA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A6:G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('D:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('E6:F' . $lastRow)->getNumberFormat()->setFormatCode('_("Rp"* #,##0_);_("Rp"* \(#,##0\);_("Rp"* "-"??_);_(@_)');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $summaryStartRow = $lastRow + 2;

                $sheet->setCellValue("E{$summaryStartRow}", 'Total Kuantitas:');
                $sheet->setCellValue("F{$summaryStartRow}", $this->totalQuantity);

                $sheet->setCellValue("E" . ($summaryStartRow + 1), 'Total Keseluruhan:');
                $sheet->setCellValue("F" . ($summaryStartRow + 1), $this->totalOverall);

                $sheet->getStyle("E{$summaryStartRow}:F" . ($summaryStartRow + 1))->getFont()->setBold(true);
                $sheet->getStyle("F" . ($summaryStartRow + 1))->getNumberFormat()->setFormatCode('_("Rp"* #,##0_);_("Rp"* \(#,##0\);_("Rp"* "-"??_);_(@_)');
                $sheet->getStyle("E{$summaryStartRow}:E" . ($summaryStartRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }

    public function title(): string
    {
        return 'Barang Masuk';
    }
}
