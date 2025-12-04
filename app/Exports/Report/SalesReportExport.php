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
use App\Models\Report\SalesReport;
use Carbon\Carbon;

class SalesReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $salesReports;
    protected $monthYear;

    public function __construct($salesReports, $month = null, $year = null)
    {
        $this->salesReports = $salesReports;

        // Jika tidak ada filter atau filter kosong, ambil dari data terbaru
        if (empty($month) && empty($year)) {
            // Ambil tanggal terbaru dari data
            $latestDate = $salesReports->sortByDesc('date')->first()?->date;
            if ($latestDate) {
                $this->monthYear = Carbon::parse($latestDate)->locale('id')->translatedFormat('F Y');
            } else {
                $this->monthYear = Carbon::now()->locale('id')->translatedFormat('F Y');
            }
        } else {
            // Gunakan filter yang dipilih
            $monthName = $month ? Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F') : '';
            $yearValue = $year ?: Carbon::now()->year;

            if ($month && $year) {
                $this->monthYear = $monthName . ' ' . $yearValue;
            } elseif ($month) {
                // Hanya bulan, gunakan tahun terbaru dari data
                $latestYear = $salesReports->sortByDesc('date')->first()?->date->year ?? Carbon::now()->year;
                $this->monthYear = $monthName . ' ' . $latestYear;
            } else {
                // Hanya tahun
                $this->monthYear = 'TAHUN ' . $yearValue;
            }
        }
    }

    public function collection()
    {
        $data = [];
        $no = 1;

        $grandTotalCapital = 0;
        $grandTotalSelling = 0;
        $grandTotalProfit = 0;

        $projectGroups = $this->salesReports->groupBy('name_proyek');

        // Store merge info for later use
        $mergeInfo = [];
        $currentRow = 5; // Start from row 5 (after header)

        foreach ($projectGroups as $projectName => $projectSales) {
            $projectTotalCapital = 0;
            $projectTotalSelling = 0;
            $projectTotalProfit = 0;

            // Hitung total items dalam project
            $totalItemsInProject = 0;
            foreach ($projectSales as $saleTemp) {
                $itemsTemp = is_string($saleTemp->items) ? json_decode($saleTemp->items, true) : $saleTemp->items;
                $totalItemsInProject += count($itemsTemp);
            }

            // Track project start row for merging
            $projectStartRow = $currentRow;

            foreach ($projectSales as $sale) {
                $items = is_string($sale->items) ? json_decode($sale->items, true) : $sale->items;
                $itemCount = count($items);

                // Track sale start row for NO and TANGGAL merging
                $saleStartRow = $currentRow;

                foreach ($items as $index => $item) {
                    $qty = $item['quantity'] ?? 0;
                    $capital = $item['capital_price'] ?? 0;
                    $selling = $item['selling_price'] ?? 0;
                    $totalCapital = $capital * $qty;
                    $totalSelling = $selling * $qty;
                    $profit = $totalSelling - $totalCapital;

                    $projectTotalCapital += $totalCapital;
                    $projectTotalSelling += $totalSelling;
                    $projectTotalProfit += $profit;

                    // Only first row of each sale has NO and TANGGAL
                    $data[] = [
                        'no' => $index === 0 ? $no : '',
                        'date' => $index === 0 ? Carbon::parse($sale->date)->format('d/m/Y') : '',
                        'project' => '', // Will be filled by first row only
                        'item' => $item['name_item'] ?? '',
                        'qty' => $qty,
                        'hpp' => 'Rp ' . number_format($capital, 0, ',', '.') . ' | Rp ' . number_format($totalCapital, 0, ',', '.'),
                        'selling' => 'Rp ' . number_format($selling, 0, ',', '.') . ' | Rp ' . number_format($totalSelling, 0, ',', '.'),
                        'profit' => 'Rp ' . number_format($profit, 0, ',', '.'),
                        'status' => '', // Will be filled by first row only
                    ];

                    $currentRow++;
                }

                // Store merge info for NO and TANGGAL
                if ($itemCount > 1) {
                    $mergeInfo[] = ['col' => 'A', 'start' => $saleStartRow, 'end' => $currentRow - 1]; // NO
                    $mergeInfo[] = ['col' => 'B', 'start' => $saleStartRow, 'end' => $currentRow - 1]; // TANGGAL
                }

                $no++;
            }

            // Store merge info for PROJECT and STATUS
            if ($totalItemsInProject > 1) {
                $mergeInfo[] = ['col' => 'C', 'start' => $projectStartRow, 'end' => $currentRow - 1]; // PROYEK
                $mergeInfo[] = ['col' => 'I', 'start' => $projectStartRow, 'end' => $currentRow - 1]; // SUMBER UANG
            }

            // Set PROJECT name and STATUS in first row
            $data[$projectStartRow - 5]['project'] = strtoupper($projectName);
            $data[$projectStartRow - 5]['status'] = strtoupper($projectSales->first()->status);

            // Project Subtotal
            $data[] = [
                'no' => '',
                'date' => '',
                'project' => '',
                'item' => '',
                'qty' => '',
                'hpp' => 'Rp ' . number_format($projectTotalCapital, 0, ',', '.'),
                'selling' => 'Rp ' . number_format($projectTotalSelling, 0, ',', '.'),
                'profit' => 'Rp ' . number_format($projectTotalProfit, 0, ',', '.'),
                'status' => '',
            ];
            $currentRow++;

            $grandTotalCapital += $projectTotalCapital;
            $grandTotalSelling += $projectTotalSelling;
            $grandTotalProfit += $projectTotalProfit;
        }

        // Store merge info in a property for use in events
        $this->mergeInfo = $mergeInfo;

        // Grand Total
        $data[] = [
            'no' => 'TOTAL PENJUALAN PROFIT',
            'date' => '',
            'project' => '',
            'item' => '',
            'qty' => '',
            'hpp' => 'Rp ' . number_format($grandTotalCapital, 0, ',', '.'),
            'selling' => 'Rp ' . number_format($grandTotalSelling, 0, ',', '.'),
            'profit' => 'Rp ' . number_format($grandTotalProfit, 0, ',', '.'),
            'status' => '',
        ];

        // Empty rows
        $data[] = ['no' => '', 'date' => '', 'project' => '', 'item' => '', 'qty' => '', 'hpp' => '', 'selling' => '', 'profit' => '', 'status' => ''];
        $data[] = ['no' => '', 'date' => '', 'project' => '', 'item' => '', 'qty' => '', 'hpp' => '', 'selling' => '', 'profit' => '', 'status' => ''];

        // Footer info - menggunakan kolom C-D untuk label dan E-F untuk value agar center
        $data[] = [
            'no' => '',
            'date' => '',
            'project' => 'Modal Aghitsna',
            'item' => '',
            'qty' => 'Rp ' . number_format($grandTotalCapital, 0, ',', '.'),
            'hpp' => '',
            'selling' => '',
            'profit' => '',
            'status' => '',
        ];

        $data[] = [
            'no' => '',
            'date' => '',
            'project' => 'Modal Divisi Holo',
            'item' => '',
            'qty' => 'Rp ' . number_format($grandTotalSelling, 0, ',', '.'),
            'hpp' => '',
            'selling' => '',
            'profit' => '',
            'status' => '',
        ];

        $data[] = [
            'no' => '',
            'date' => '',
            'project' => 'PROFIT',
            'item' => '',
            'qty' => 'Rp ' . number_format($grandTotalProfit, 0, ',', '.'),
            'hpp' => '',
            'selling' => '',
            'profit' => '',
            'status' => '',
        ];

        return collect($data);
    }

    protected $mergeInfo = [];

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

                // Apply merges for NO, TANGGAL, PROYEK, and SUMBER UANG
                foreach ($this->mergeInfo as $merge) {
                    if ($merge['start'] < $merge['end']) {
                        $range = $merge['col'] . $merge['start'] . ':' . $merge['col'] . $merge['end'];
                        $sheet->mergeCells($range);

                        // Center align vertically for merged cells
                        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                        // Remove internal borders for merged cells
                        for ($row = $merge['start']; $row <= $merge['end']; $row++) {
                            $cell = $merge['col'] . $row;

                            // Keep outer borders, remove internal
                            $borderStyle = [
                                'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                                'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                            ];

                            // Top border only for first row
                            if ($row === $merge['start']) {
                                $borderStyle['top'] = ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']];
                            } else {
                                $borderStyle['top'] = ['borderStyle' => Border::BORDER_NONE];
                            }

                            // Bottom border only for last row
                            if ($row === $merge['end']) {
                                $borderStyle['bottom'] = ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']];
                            } else {
                                $borderStyle['bottom'] = ['borderStyle' => Border::BORDER_NONE];
                            }

                            $sheet->getStyle($cell)->applyFromArray(['borders' => $borderStyle]);
                        }
                    }
                }

                // Process each row for styling
                for ($row = 5; $row <= $highestRow; $row++) {
                    $cellA = $sheet->getCell('A' . $row)->getValue();
                    $cellC = $sheet->getCell('C' . $row)->getValue();
                    $cellD = $sheet->getCell('D' . $row)->getValue();
                    $cellF = $sheet->getCell('F' . $row)->getValue();

                    // Subtotal rows (empty NO, empty NAMA BARANG but has HPP value)
                    if (empty($cellA) && empty($cellD) && !empty($cellF) && strpos($cellF, 'Rp') !== false) {
                        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFC000'],
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
                    if (in_array($cellC, ['Modal Aghitsna', 'Modal Divisi Holo', 'PROFIT'])) {
                        // Set row height untuk footer info
                        $sheet->getRowDimension($row)->setRowHeight(20);

                        // Merge cells C-D untuk label
                        $sheet->mergeCells('C' . $row . ':D' . $row);

                        // Merge cells E-F untuk value
                        $sheet->mergeCells('E' . $row . ':F' . $row);

                        // Style untuk label (kolom C-D)
                        $sheet->getStyle('C' . $row)->applyFromArray([
                            'font' => ['bold' => true, 'size' => 10],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                                'wrapText' => false
                            ],
                        ]);

                        // Style untuk value (kolom E-F)
                        $sheet->getStyle('E' . $row)->applyFromArray([
                            'font' => ['bold' => true, 'size' => 10],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                                'wrapText' => false
                            ],
                        ]);

                        // Remove all borders
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
            'A' => 22,    // NO - diperlebar untuk footer info
            'B' => 18,    // TANGGAL - diperlebar untuk nilai
            'C' => 20,    // PROYEK
            'D' => 25,    // NAMA BARANG
            'E' => 8,     // QTY
            'F' => 25,    // HPP
            'G' => 25,    // HARGA JUAL
            'H' => 18,    // PROFIT
            'I' => 20,    // SUMBER UANG
        ];
    }

    public function title(): string
    {
        return 'Laporan Penjualan';
    }
}
