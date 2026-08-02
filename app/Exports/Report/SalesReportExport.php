<?php

namespace App\Exports\Report;

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

class SalesReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $projects;
    protected $periodTitle;
    protected $grandTotal;
    protected $mergeInfo = [];

    private const DATA_START_ROW = 5;

    public function __construct($projects, $periodTitle, $grandTotal)
    {
        $this->projects = $projects;
        $this->periodTitle = $periodTitle;
        $this->grandTotal = $grandTotal;
    }

    public function collection()
    {
        $data = [];
        $no = 1;
        $currentRow = self::DATA_START_ROW;

        foreach ($this->projects as $project) {
            $projectStartRow = $currentRow;
            $totalItems = 0;

            foreach ($project['sales_recaps'] as $sr) {
                $totalItems += count($sr['items']);
            }

            foreach ($project['sales_recaps'] as $sale) {
                foreach ($sale['items'] as $itemIndex => $item) {
                    $row = [
                        'no' => '',
                        'date' => '',
                        'faktur' => '',
                        'item' => $item['name_item'],
                        'qty' => $item['qty'],
                        'harga_modal' => $item['capital_price'],
                        'harga_jual' => $item['selling_price'],
                        'jumlah' => $item['jumlah'],
                        'total' => '',
                    ];

                    if ($currentRow === $projectStartRow) {
                        $row['no'] = $no;
                        $row['date'] = $sale['date'];
                        $row['faktur'] = $sale['no_faktur'] . "\n" . strtoupper($project['project_name']);
                    }

                    $data[] = $row;
                    $currentRow++;
                }
            }

            if ($totalItems > 1) {
                $this->mergeInfo[] = ['col' => 'A', 'start' => $projectStartRow, 'end' => $currentRow - 1];
                $this->mergeInfo[] = ['col' => 'B', 'start' => $projectStartRow, 'end' => $currentRow - 1];
                $this->mergeInfo[] = ['col' => 'C', 'start' => $projectStartRow, 'end' => $currentRow - 1];
            }

            $subtotalLabel = 'TOTAL';
            if (($project['sales_recaps'][0]['status'] ?? '') === 'Lunas') {
                $subtotalLabel .= ' (Sudah Lunas ' . ($project['lunas_date'] ?? '') . ')';
            }

            $data[] = [
                'no' => '',
                'date' => '',
                'faktur' => '',
                'item' => $subtotalLabel,
                'qty' => '',
                'harga_modal' => '',
                'harga_jual' => '',
                'jumlah' => '',
                'total' => $project['subtotal'],
            ];
            $currentRow++;

            $no++;
        }

        $data[] = [
            'no' => '',
            'date' => '',
            'faktur' => '',
            'item' => 'TOTAL PENJUALAN BELUM PROFIT',
            'qty' => '',
            'harga_modal' => '',
            'harga_jual' => '',
            'jumlah' => '',
            'total' => $this->grandTotal,
        ];

        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');
        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');

        $data[] = [
            'no' => '',
            'date' => '',
            'faktur' => 'DIBUAT/DIPERIKSA',
            'item' => '',
            'qty' => '',
            'harga_modal' => 'KAB. KEUANGAN',
            'harga_jual' => '',
            'jumlah' => '',
            'total' => 'MENGETAHUI, DIREKTUR PT. AGHITSNA KARYA INDAH',
        ];

        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');
        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');
        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');

        $data[] = [
            'no' => '',
            'date' => '',
            'faktur' => '( A. KHAIDIR )',
            'item' => '',
            'qty' => '',
            'harga_modal' => '( KAMILA )',
            'harga_jual' => '',
            'jumlah' => '',
            'total' => '( Zulkarnain,ST.,MT )',
        ];

        return collect($data);
    }

    public function headings(): array
    {
        return [
            ['LAPORAN PENJUALAN LIST ORDER DIVISI PRODUKSI'],
            [$this->periodTitle],
            [''],
            [
                'NO',
                'TANGGAL',
                'NO FAKTUR & PROYEK',
                'NAMA BARANG',
                'QTY',
                'HARGA MODAL',
                'HARGA JUAL',
                'JUMLAH',
                'TOTAL',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(3)->setRowHeight(5);

        $sheet->getStyle('A4:I4')->applyFromArray([
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

        $sheet->getRowDimension(4)->setRowHeight(30);

        $sheet->getStyle('A5:I' . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle('A5:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B5:B' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E5:E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F5:H' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('I5:I' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $this->applyMergeStyles($sheet);
                $this->applyRowStyles($sheet, $highestRow);
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 12,
            'C' => 25,
            'D' => 22,
            'E' => 6,
            'F' => 14,
            'G' => 14,
            'H' => 14,
            'I' => 16,
        ];
    }

    public function title(): string
    {
        return 'Laporan_Penjualan';
    }

    private function applyMergeStyles($sheet): void
    {
        foreach ($this->mergeInfo as $merge) {
            if ($merge['start'] < $merge['end']) {
                $range = $merge['col'] . $merge['start'] . ':' . $merge['col'] . $merge['end'];
                $sheet->mergeCells($range);
                $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle($range)->getAlignment()->setWrapText(true);

                for ($row = $merge['start']; $row <= $merge['end']; $row++) {
                    $cell = $merge['col'] . $row;
                    $borderStyle = [
                        'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                    ];
                    $borderStyle['top'] = ($row === $merge['start'])
                        ? ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
                        : ['borderStyle' => Border::BORDER_NONE];
                    $borderStyle['bottom'] = ($row === $merge['end'])
                        ? ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
                        : ['borderStyle' => Border::BORDER_NONE];
                    $sheet->getStyle($cell)->applyFromArray(['borders' => $borderStyle]);
                }
            }
        }
    }

    private function applyRowStyles($sheet, int $highestRow): void
    {
        for ($row = self::DATA_START_ROW; $row <= $highestRow; $row++) {
            $cellD = $sheet->getCell('D' . $row)->getValue();
            $cellI = $sheet->getCell('I' . $row)->getValue();

            if (str_starts_with($cellD, 'TOTAL') && !empty($cellI)) {
                $isSubtotal = str_starts_with($cellD, 'TOTAL') && $cellD !== 'TOTAL PENJUALAN BELUM PROFIT';

                if ($isSubtotal) {
                    $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2AD28']],
                        'font' => ['bold' => true],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                    ]);
                } elseif ($cellD === 'TOTAL PENJUALAN BELUM PROFIT') {
                    $sheet->mergeCells('A' . $row . ':H' . $row);
                    $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5C327']],
                        'font' => ['bold' => true],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                    ]);
                }
            }

            $cellC = $sheet->getCell('C' . $row)->getValue();
            if (in_array($cellC, ['DIBUAT/DIPERIKSA', '( A. KHAIDIR )'])) {
                $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }

            $cellF = $sheet->getCell('F' . $row)->getValue();
            if (in_array($cellF, ['KAB. KEUANGAN', '( KAMILA )'])) {
                $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }

            if (str_contains($cellI ?? '', 'DIREKTUR') || ($cellI ?? '') === '( Zulkarnain,ST.,MT )') {
                $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
        }
    }
}
