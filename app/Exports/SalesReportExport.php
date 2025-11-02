<?php

namespace App\Exports;

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
use App\Models\SalesReport;
use Carbon\Carbon;

class SalesReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $salesReports;
    protected $monthYear;

    public function __construct($salesReports, $monthYear = null)
    {
        $this->salesReports = $salesReports;
        $this->monthYear = $monthYear ?? Carbon::now()->locale('id')->translatedFormat('F Y');
    }

    public function collection()
    {
        $data = [];
        $no = 1;

        $grandTotalCapital = 0;
        $grandTotalSelling = 0;
        $grandTotalProfit = 0;

        $projectGroups = $this->salesReports->groupBy('name_proyek');

        foreach ($projectGroups as $projectName => $projectSales) {
            $projectTotalCapital = 0;
            $projectTotalSelling = 0;
            $projectTotalProfit = 0;
            $firstInProject = true;

            // Hitung total items dalam project
            $totalItemsInProject = 0;
            foreach ($projectSales as $saleTemp) {
                $itemsTemp = is_string($saleTemp->items) ? json_decode($saleTemp->items, true) : $saleTemp->items;
                $totalItemsInProject += count($itemsTemp);
            }

            $projectItemCounter = 0;
            $statusAdded = false;

            foreach ($projectSales as $sale) {
                $items = is_string($sale->items) ? json_decode($sale->items, true) : $sale->items;
                $firstInSale = true;

                foreach ($items as $item) {
                    $qty = $item['quantity'] ?? 0;
                    $capital = $item['capital_price'] ?? 0;
                    $selling = $item['selling_price'] ?? 0;
                    $totalCapital = $capital * $qty;
                    $totalSelling = $selling * $qty;
                    $profit = $totalSelling - $totalCapital;

                    $projectTotalCapital += $totalCapital;
                    $projectTotalSelling += $totalSelling;
                    $projectTotalProfit += $profit;

                    $projectItemCounter++;

                    $data[] = [
                        $firstInSale ? $no : '',
                        $firstInSale ? \Carbon\Carbon::parse($sale->date)->format('d/m/Y') : '',
                        $firstInProject ? strtoupper($projectName) : '',
                        $item['name_item'] ?? '',
                        $qty,
                        'Rp ' . number_format($capital, 0, ',', '.'),
                        'Rp ' . number_format($selling, 0, ',', '.'),
                        'Rp ' . number_format($profit, 0, ',', '.'),
                        (!$statusAdded && $firstInSale) ? strtoupper($sale->status) : '',
                    ];

                    $firstInSale = false;
                    $firstInProject = false;
                    if (!$statusAdded) {
                        $statusAdded = true;
                    }
                }
                $no++;
            }

            // Project Subtotal
            $data[] = [
                '',
                '',
                '',
                '',
                '',
                'Rp ' . number_format($projectTotalCapital, 0, ',', '.'),
                'Rp ' . number_format($projectTotalSelling, 0, ',', '.'),
                'Rp ' . number_format($projectTotalProfit, 0, ',', '.'),
                '',
            ];

            $grandTotalCapital += $projectTotalCapital;
            $grandTotalSelling += $projectTotalSelling;
            $grandTotalProfit += $projectTotalProfit;
        }

        // Grand Total
        $data[] = [
            'TOTAL PENJUALAN PROFIT',
            '',
            '',
            '',
            '',
            'Rp ' . number_format($grandTotalCapital, 0, ',', '.'),
            'Rp ' . number_format($grandTotalSelling, 0, ',', '.'),
            'Rp ' . number_format($grandTotalProfit, 0, ',', '.'),
            '',
        ];

        // Empty rows
        $data[] = ['', '', '', '', '', '', '', '', ''];
        $data[] = ['', '', '', '', '', '', '', '', ''];

        // Footer info
        $data[] = [
            'Modal Aghitsna',
            'Rp ' . number_format($grandTotalCapital, 0, ',', '.'),
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ];

        $data[] = [
            'Modal Divisi Holo',
            'Rp ' . number_format($grandTotalSelling, 0, ',', '.'),
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ];

        $data[] = [
            'PROFIT',
            'Rp ' . number_format($grandTotalProfit, 0, ',', '.'),
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ];

        return collect($data);
    }

    public function headings(): array
    {
        return [
            ['LAPORAN PROFIT PENJUALAN DIVISI PRODUKSI'],
            ['BULAN ' . strtoupper($this->monthYear)],
            [''],
            [
                'NO',
                'TANGGAL',
                'PROYEK',
                'NAMA BARANG',
                'QTY',
                'HPP (HARGA MODAL )',
                'HARGA JUAL',
                'PROFIT',
                'SUMBER UANG',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // Merge title
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Merge subtitle
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Empty row
        $sheet->getRowDimension(3)->setRowHeight(5);

        // Header row styling
        $sheet->getStyle('A4:I4')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'],
            ],
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(4)->setRowHeight(30);

        // Data rows border
        $dataEndRow = $highestRow - 5;
        $sheet->getStyle('A5:I' . $dataEndRow)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Align columns
        $sheet->getStyle('A5:A' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B5:B' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E5:E' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F5:H' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('I5:I' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                for ($row = 5; $row <= $highestRow; $row++) {
                    $cellA = $sheet->getCell('A' . $row)->getValue();
                    $cellD = $sheet->getCell('D' . $row)->getValue();
                    $cellF = $sheet->getCell('F' . $row)->getValue();

                    // Subtotal rows (empty NO, empty NAMA BARANG but has HPP value)
                    if (empty($cellA) && empty($cellD) && !empty($cellF) && strpos($cellF, 'Rp') !== false) {
                        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFFF00'],
                            ],
                            'font' => ['bold' => true],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                            ],
                        ]);
                    }

                    // Grand Total row (starts with "TOTAL PENJUALAN PROFIT")
                    if ($cellA === 'TOTAL PENJUALAN PROFIT') {
                        // Merge first 5 columns
                        $sheet->mergeCells('A' . $row . ':E' . $row);

                        $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFFF00'],
                            ],
                            'font' => ['bold' => true],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                            ],
                        ]);

                        // White background for SUMBER UANG column in grand total
                        $sheet->getStyle('I' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFFFFF'],
                            ],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                            ],
                        ]);
                    }

                    // Footer info rows (Modal Aghitsna, Modal Divisi Holo, PROFIT)
                    if (in_array($cellA, ['Modal Aghitsna', 'Modal Divisi Holo', 'PROFIT'])) {
                        $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
                            'font' => ['bold' => true],
                        ]);

                        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                            ],
                        ]);
                    }
                }
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,     // NO
            'B' => 12,    // TANGGAL
            'C' => 20,    // PROYEK
            'D' => 25,    // NAMA BARANG
            'E' => 8,     // QTY
            'F' => 20,    // HPP
            'G' => 18,    // HARGA JUAL
            'H' => 18,    // PROFIT
            'I' => 20,    // SUMBER UANG
        ];
    }

    public function title(): string
    {
        return 'Laporan Penjualan';
    }
}
