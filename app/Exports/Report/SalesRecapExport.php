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
use App\Models\Report\SalesRecap;
use Carbon\Carbon;

/**
 * Export class untuk Laporan Profit Penjualan ke Excel.
 *
 * Fitur:
 * - Grouping by proyek dengan merged cells
 * - Subtotal per proyek
 * - Grand total
 * - Footer info (Modal Aghitsna, Modal Divisi Holo, PROFIT)
 */
class SalesRecapExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    /**
     * Data rekap penjualan yang akan di-export.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $salesRecaps;

    /**
     * Label bulan/tahun untuk header.
     *
     * @var string
     */
    protected $monthYear;

    /**
     * Info merge cells untuk registerEvents.
     *
     * @var array<int, array{col: string, start: int, end: int}>
     */
    protected $mergeInfo = [];

    /**
     * Baris awal data (setelah header).
     */
    private const DATA_START_ROW = 5;

    /**
     * @param  \Illuminate\Support\Collection $salesRecaps  Data rekap penjualan
     * @param  int|null                       $month        Filter bulan
     * @param  int|null                       $year         Filter tahun
     */
    public function __construct($salesRecaps, $month = null, $year = null)
    {
        $this->salesRecaps = $salesRecaps;
        $this->monthYear = $this->buildMonthYearLabel($month, $year);
    }

    /**
     * Membangun data collection untuk export.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $data = [];
        $no = 1;

        $grandTotalCapital = 0;
        $grandTotalSelling = 0;
        $grandTotalProfit = 0;

        $projectGroups = $this->salesRecaps->groupBy('name_proyek');
        $currentRow = self::DATA_START_ROW;

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

            $projectStartRow = $currentRow;

            foreach ($projectSales as $sale) {
                $items = is_string($sale->items) ? json_decode($sale->items, true) : $sale->items;
                $itemCount = count($items);

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

                    $data[] = [
                        'no' => $index === 0 ? $no : '',
                        'date' => $index === 0 ? Carbon::parse($sale->date)->format('d/m/Y') : '',
                        'project' => '',
                        'item' => $item['name_item'] ?? '',
                        'qty' => $qty,
                        'hpp' => 'Rp ' . number_format($capital, 0, ',', '.') . ' | Rp ' . number_format($totalCapital, 0, ',', '.'),
                        'selling' => 'Rp ' . number_format($selling, 0, ',', '.') . ' | Rp ' . number_format($totalSelling, 0, ',', '.'),
                        'profit' => 'Rp ' . number_format($profit, 0, ',', '.'),
                        'status' => '',
                    ];

                    $currentRow++;
                }

                // Merge info untuk NO dan TANGGAL
                if ($itemCount > 1) {
                    $this->mergeInfo[] = ['col' => 'A', 'start' => $saleStartRow, 'end' => $currentRow - 1];
                    $this->mergeInfo[] = ['col' => 'B', 'start' => $saleStartRow, 'end' => $currentRow - 1];
                }

                $no++;
            }

            // Merge info untuk PROYEK dan SUMBER UANG
            if ($totalItemsInProject > 1) {
                $this->mergeInfo[] = ['col' => 'C', 'start' => $projectStartRow, 'end' => $currentRow - 1];
                $this->mergeInfo[] = ['col' => 'I', 'start' => $projectStartRow, 'end' => $currentRow - 1];
            }

            // Set PROJECT name dan STATUS di baris pertama
            $data[$projectStartRow - self::DATA_START_ROW]['project'] = strtoupper($projectName ?: '-');
            $data[$projectStartRow - self::DATA_START_ROW]['status'] = strtoupper($projectSales->first()->status);

            // Subtotal per proyek
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
        $data[] = $this->emptyRow();
        $data[] = $this->emptyRow();

        // Footer info
        $data[] = $this->footerRow('Modal Aghitsna', 'Rp ' . number_format($grandTotalCapital, 0, ',', '.'));
        $data[] = $this->footerRow('Modal Divisi Holo', 'Rp ' . number_format($grandTotalSelling, 0, ',', '.'));
        $data[] = $this->footerRow('PROFIT', 'Rp ' . number_format($grandTotalProfit, 0, ',', '.'));

        return collect($data);
    }

    /**
     * Header untuk Excel.
     *
     * @return array<int, array<int, string>>
     */
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

    /**
     * Style untuk worksheet.
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet): array
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

    /**
     * Event handler untuk merge cells dan styling tambahan.
     *
     * @return array<string, callable>
     */
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

    /**
     * Lebar kolom untuk Excel.
     *
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 22,
            'B' => 18,
            'C' => 20,
            'D' => 25,
            'E' => 8,
            'F' => 25,
            'G' => 25,
            'H' => 18,
            'I' => 20,
        ];
    }

    /**
     * Nama sheet Excel.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Laporan_Penjualan';
    }

    // ============================================================
    // PRIVATE HELPERS
    // ============================================================

    /**
     * Membangun label bulan/tahun untuk header.
     *
     * @param  int|null $month
     * @param  int|null $year
     * @return string
     */
    private function buildMonthYearLabel(?int $month, ?int $year): string
    {
        if (empty($month) && empty($year)) {
            $latestDate = $this->salesRecaps->sortByDesc('date')->first()?->date;
            return $latestDate
                ? Carbon::parse($latestDate)->locale('id')->translatedFormat('F Y')
                : Carbon::now()->locale('id')->translatedFormat('F Y');
        }

        $monthName = $month ? Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F') : '';
        $yearValue = $year ?: Carbon::now()->year;

        if ($month && $year) {
            return $monthName . ' ' . $yearValue;
        }

        if ($month) {
            $latestYear = $this->salesRecaps->sortByDesc('date')->first()?->date->year ?? Carbon::now()->year;
            return $monthName . ' ' . $latestYear;
        }

        return 'TAHUN ' . $yearValue;
    }

    /**
     * Membuat baris kosong untuk footer spacing.
     *
     * @return array<string, string>
     */
    private function emptyRow(): array
    {
        return array_fill_keys(['no', 'date', 'project', 'item', 'qty', 'hpp', 'selling', 'profit', 'status'], '');
    }

    /**
     * Membuat baris footer info.
     *
     * @param  string $label  Label (Modal Aghitsna, Modal Divisi Holo, PROFIT)
     * @param  string $value  Nilai formatted
     * @return array<string, string>
     */
    private function footerRow(string $label, string $value): array
    {
        return [
            'no' => '',
            'date' => '',
            'project' => $label,
            'item' => '',
            'qty' => $value,
            'hpp' => '',
            'selling' => '',
            'profit' => '',
            'status' => '',
        ];
    }

    /**
     * Apply merge styles untuk NO, TANGGAL, PROYEK, dan SUMBER UANG.
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return void
     */
    private function applyMergeStyles($sheet): void
    {
        foreach ($this->mergeInfo as $merge) {
            if ($merge['start'] < $merge['end']) {
                $range = $merge['col'] . $merge['start'] . ':' . $merge['col'] . $merge['end'];
                $sheet->mergeCells($range);
                $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

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

    /**
     * Apply row-level styles (subtotal, grand total, footer).
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param  int                                            $highestRow
     * @return void
     */
    private function applyRowStyles($sheet, int $highestRow): void
    {
        for ($row = self::DATA_START_ROW; $row <= $highestRow; $row++) {
            $cellA = $sheet->getCell('A' . $row)->getValue();
            $cellC = $sheet->getCell('C' . $row)->getValue();
            $cellD = $sheet->getCell('D' . $row)->getValue();
            $cellF = $sheet->getCell('F' . $row)->getValue();

            // Subtotal rows
            if (empty($cellA) && empty($cellD) && !empty($cellF) && strpos($cellF, 'Rp') !== false) {
                $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC000']],
                    'font' => ['bold' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);
            }

            // Grand Total row
            if ($cellA === 'TOTAL PENJUALAN PROFIT') {
                $sheet->mergeCells('A' . $row . ':E' . $row);
                $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);
                $sheet->getStyle('I' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                ]);
            }

            // Footer info rows
            if (in_array($cellC, ['Modal Aghitsna', 'Modal Divisi Holo', 'PROFIT'])) {
                $sheet->getRowDimension($row)->setRowHeight(20);
                $sheet->mergeCells('C' . $row . ':D' . $row);
                $sheet->mergeCells('E' . $row . ':F' . $row);

                $sheet->getStyle('C' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => false,
                    ],
                ]);

                $sheet->getStyle('E' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => false,
                    ],
                ]);

                $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                ]);
            }
        }
    }
}
