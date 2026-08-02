<?php

namespace App\Exports\Inventory;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Events\AfterSheet;

class StockReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $reportData;
    protected $summary;
    protected $periodTitle;
    protected $startDate;
    protected $endDate;

    private const DATA_START_ROW = 6;

    public function __construct($reportData, $summary, $periodTitle, $startDate, $endDate)
    {
        $this->reportData = $reportData;
        $this->summary = $summary;
        $this->periodTitle = $periodTitle;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $data = [];
        $no = 1;

        foreach ($this->reportData as $item) {
            $data[] = [
                'no' => $no++,
                'id_item' => $item['id_item'],
                'name_item' => $item['name_item'],
                'beginning_stock' => $item['beginning_stock'],
                'stock_in' => $item['stock_in'],
                'stock_out' => $item['stock_out'],
                'returns' => $item['returns'],
                'ending_stock' => $item['ending_stock'],
                'capital_price' => $item['capital_price'],
                'stock_value' => $item['stock_value'],
            ];
        }

        // Grand Total row
        $data[] = [
            'no' => '',
            'id_item' => '',
            'name_item' => 'TOTAL',
            'beginning_stock' => $this->summary['total_beginning_stock'],
            'stock_in' => $this->summary['total_stock_in'],
            'stock_out' => $this->summary['total_stock_out'],
            'returns' => $this->summary['total_returns'],
            'ending_stock' => $this->summary['total_ending_stock'],
            'capital_price' => '',
            'stock_value' => $this->summary['total_stock_value'],
        ];

        // Empty rows before summary
        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');

        // Summary items
        $summaryItems = [
            ['no' => '', 'id_item' => '', 'name_item' => 'Ringkasan Pergerakan Stok', 'beginning_stock' => '', 'stock_in' => '', 'stock_out' => '', 'returns' => '', 'ending_stock' => '', 'capital_price' => '', 'stock_value' => ''],
            ['no' => '1.', 'id_item' => '', 'name_item' => 'Stok Awal', 'beginning_stock' => '', 'stock_in' => '', 'stock_out' => '', 'returns' => '', 'ending_stock' => '', 'capital_price' => '', 'stock_value' => $this->summary['total_beginning_stock'] . ' unit'],
            ['no' => '2.', 'id_item' => '', 'name_item' => 'Stok Masuk', 'beginning_stock' => '', 'stock_in' => '', 'stock_out' => '', 'returns' => '', 'ending_stock' => '', 'capital_price' => '', 'stock_value' => $this->summary['total_stock_in'] . ' unit'],
            ['no' => '3.', 'id_item' => '', 'name_item' => 'Stok Keluar', 'beginning_stock' => '', 'stock_in' => '', 'stock_out' => '', 'returns' => '', 'ending_stock' => '', 'capital_price' => '', 'stock_value' => $this->summary['total_stock_out'] . ' unit'],
            ['no' => '4.', 'id_item' => '', 'name_item' => 'Retur Barang', 'beginning_stock' => '', 'stock_in' => '', 'stock_out' => '', 'returns' => '', 'ending_stock' => '', 'capital_price' => '', 'stock_value' => $this->summary['total_returns'] . ' unit'],
            ['no' => '', 'id_item' => '', 'name_item' => 'Stok Akhir', 'beginning_stock' => '', 'stock_in' => '', 'stock_out' => '', 'returns' => '', 'ending_stock' => '', 'capital_price' => '', 'stock_value' => $this->summary['total_ending_stock'] . ' unit'],
            ['no' => '', 'id_item' => '', 'name_item' => 'Nilai Stok Total', 'beginning_stock' => '', 'stock_in' => '', 'stock_out' => '', 'returns' => '', 'ending_stock' => '', 'capital_price' => '', 'stock_value' => 'Rp ' . number_format($this->summary['total_stock_value'], 0, ',', '.')],
        ];

        foreach ($summaryItems as $item) {
            $data[] = $item;
        }

        // Empty rows before signatures
        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');
        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');

        // Signature headers
        $data[] = [
            'no' => '',
            'id_item' => '',
            'name_item' => 'DIBUAT/DIPERIKSA',
            'beginning_stock' => '',
            'stock_in' => '',
            'stock_out' => '',
            'returns' => '',
            'ending_stock' => '',
            'capital_price' => 'KAB. KEUANGAN',
            'stock_value' => 'MENGETAHUI, DIREKTUR PT. AGHITSNA KARYA INDAH',
        ];

        // Empty rows for signature space
        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');
        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');
        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');

        // Signature names
        $data[] = [
            'no' => '',
            'id_item' => '',
            'name_item' => '( A. KHAIDIR )',
            'beginning_stock' => '',
            'stock_in' => '',
            'stock_out' => '',
            'returns' => '',
            'ending_stock' => '',
            'capital_price' => '( KAMILA )',
            'stock_value' => '( Zulkarnain,ST.,MT )',
        ];

        return collect($data);
    }

    public function headings(): array
    {
        return [
            ['PT. AGHITSNA KARYA INDAH'],
            ['LAPORAN STOK BARANG'],
            ['PERIODE ' . $this->periodTitle],
            ['Tanggal Cetak: ' . date('d M Y H:i')],
            [''],
            [
                'NO',
                'ID BARANG',
                'NAMA BARANG',
                'STOK AWAL',
                'MASUK',
                'KELUAR',
                'RETUR',
                'STOK AKHIR',
                'HARGA SATUAN',
                'NILAI STOK',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        // Merge and style company name (Row 1)
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Merge and style title (Row 2)
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Merge and style period (Row 3)
        $sheet->mergeCells('A3:J3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Print date (Row 4)
        $sheet->mergeCells('A4:J4');
        $sheet->getStyle('A4')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(3)->setRowHeight(16);
        $sheet->getRowDimension(4)->setRowHeight(14);

        // Header row styling (Row 6) - green background matching sales report
        $sheet->getStyle('A6:J6')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '9EA974'],
            ],
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);

        $sheet->getRowDimension(6)->setRowHeight(30);

        // Data rows border (up to total row)
        $totalRow = self::DATA_START_ROW + $this->reportData->count();
        $sheet->getStyle('A' . self::DATA_START_ROW . ':J' . $totalRow)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Center align columns
        $sheet->getStyle('A' . self::DATA_START_ROW . ':A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B' . self::DATA_START_ROW . ':B' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D' . self::DATA_START_ROW . ':I' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('J' . self::DATA_START_ROW . ':J' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $totalRow = self::DATA_START_ROW + $this->reportData->count();

                // Grand total row styling
                $sheet->getStyle('A' . $totalRow . ':J' . $totalRow)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5C327']],
                    'font' => ['bold' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);

                // Style summary section (no borders)
                $summaryStartRow = $totalRow + 2;
                $summaryEndRow = $summaryStartRow + 6;

                $sheet->getStyle('A' . $summaryStartRow . ':J' . $summaryEndRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                ]);

                // Summary title
                $sheet->mergeCells('C' . $summaryStartRow . ':J' . $summaryStartRow);
                $sheet->getStyle('C' . $summaryStartRow)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                ]);

                // Bold summary items
                $sheet->getStyle('C' . ($summaryStartRow + 5) . ':C' . ($summaryStartRow + 6))->applyFromArray([
                    'font' => ['bold' => true],
                ]);
                $sheet->getStyle('J' . ($summaryStartRow + 5) . ':J' . ($summaryStartRow + 6))->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                // Signature section
                $sigRow = $summaryEndRow + 2;
                $sheet->getStyle('A' . $sigRow . ':J' . ($sigRow + 4))->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 14,
            'C' => 22,
            'D' => 12,
            'E' => 10,
            'F' => 10,
            'G' => 10,
            'H' => 12,
            'I' => 15,
            'J' => 15,
        ];
    }

    public function title(): string
    {
        return 'Laporan_Stok';
    }
}
