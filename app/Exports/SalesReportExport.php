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

                    $data[] = [
                        $firstInSale ? $no : '',
                        $firstInSale ? \Carbon\Carbon::parse($sale->date)->format('d/m/Y') : '',
                        $firstInProject ? strtoupper($projectName) : '',
                        $item['name_item'] ?? '',
                        $qty,
                        $item['unit'] ?? '',
                        'Rp ' . number_format($capital, 0, ',', '.'),
                        'Rp ' . number_format($selling, 0, ',', '.'),
                        'Rp ' . number_format($totalSelling, 0, ',', '.'),
                        'Rp ' . number_format($profit, 0, ',', '.'),
                        $firstInSale ? strtoupper($sale->status) : '',
                    ];

                    $firstInSale = false;
                    $firstInProject = false;
                }
                $no++;
            }

            $data[] = [
                '',
                '',
                '',
                '',
                '',
                '',
                'Rp ' . number_format($projectTotalCapital, 0, ',', '.'),
                'Rp ' . number_format($projectTotalSelling, 0, ',', '.'),
                '',
                'Rp ' . number_format($projectTotalProfit, 0, ',', '.'),
                '',
            ];

            $grandTotalCapital += $projectTotalCapital;
            $grandTotalSelling += $projectTotalSelling;
            $grandTotalProfit += $projectTotalProfit;
        }

        $data[] = [
            '',
            '',
            '',
            '',
            '',
            'TOTAL',
            'Rp ' . number_format($grandTotalCapital, 0, ',', '.'),
            'Rp ' . number_format($grandTotalSelling, 0, ',', '.'),
            '',
            'Rp ' . number_format($grandTotalProfit, 0, ',', '.'),
            '',
        ];

        $data[] = ['', '', '', '', '', '', '', '', '', '', ''];
        $data[] = ['', '', '', '', '', '', '', '', '', '', ''];

        $data[] = [
            '',
            '',
            '',
            '',
            'Modal Aghitsna',
            'Rp ' . number_format($grandTotalCapital, 0, ',', '.'),
            '',
            '',
            '',
            '',
            '',
        ];

        $data[] = [
            '',
            '',
            '',
            '',
            'Modal Divisi Holo',
            'Rp ' . number_format($grandTotalSelling, 0, ',', '.'),
            '',
            '',
            '',
            '',
            '',
        ];

        $data[] = [
            '',
            '',
            '',
            '',
            'PROFIT',
            'Rp ' . number_format($grandTotalProfit, 0, ',', '.'),
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
                'JUMLAH &',
                'PROFIT',
                'SUMBER UANG',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->mergeCells('A2:K2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getRowDimension(3)->setRowHeight(5);

        $sheet->getStyle('A4:K4')->applyFromArray([
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

        $dataEndRow = $highestRow - 3;
        $sheet->getStyle('A5:K' . $dataEndRow)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                for ($row = 5; $row <= $highestRow; $row++) {
                    $cellValue = $sheet->getCell('D' . $row)->getValue();
                    $satuan = $sheet->getCell('F' . $row)->getValue();

                    if (empty($cellValue) && empty($satuan) && !empty($sheet->getCell('G' . $row)->getValue())) {
                        $sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFFF00'],
                            ],
                            'font' => ['bold' => true],
                        ]);
                    }

                    if ($satuan === 'TOTAL') {
                        $sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFFF00'],
                            ],
                            'font' => ['bold' => true],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                    }

                    $modalValue = $sheet->getCell('E' . $row)->getValue();
                    if (in_array($modalValue, ['Modal Aghitsna', 'Modal Divisi Holo', 'PROFIT'])) {
                        $sheet->getStyle('E' . $row . ':F' . $row)->applyFromArray([
                            'font' => ['bold' => true],
                        ]);

                        $sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray([
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
            'A' => 5,
            'B' => 12,
            'C' => 20,
            'D' => 25,
            'E' => 6,
            'F' => 20,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 15,
        ];
    }

    public function title(): string
    {
        return 'Laporan Penjualan';
    }
}
