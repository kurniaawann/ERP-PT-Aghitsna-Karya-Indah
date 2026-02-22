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
use App\Models\Report\ExpenseRecap;
use Carbon\Carbon;

class ExpenseRecapExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $expenseRecaps;
    protected $periodTitle;
    protected $totals;
    protected $mergeInfo = [];

    public function __construct($expenseRecaps, $month = null, $year = null, $categoryName = null, $totals = null)
    {
        $this->expenseRecaps = $expenseRecaps;
        $this->totals = $totals;

        // Build period title
        $periodParts = [];

        if ($month && $year) {
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');
            $periodParts[] = strtoupper($monthName) . ' ' . $year;
        } elseif ($month) {
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');
            $periodParts[] = 'BULAN ' . strtoupper($monthName);
        } elseif ($year) {
            $periodParts[] = 'TAHUN ' . $year;
        }

        $this->periodTitle = !empty($periodParts) ? implode(' - ', $periodParts) : 'SEMUA PERIODE';
    }

    public function collection()
    {
        $data = [];
        $globalNo = 1; // Nomor urut global
        $currentRow = 5; // Start from row 5 (after header)

        // Group by category
        $categoryGroups = $this->expenseRecaps->groupBy('transaction_category_id');

        foreach ($categoryGroups as $categoryId => $expenses) {
            $category = $expenses->first()->category;
            $categoryStartRow = $currentRow;

            // Category header row
            $data[] = [
                'no' => '',
                'invoice' => '',
                'date' => '',
                'description' => strtoupper($category->name ?? 'LAIN-LAIN'),
                'income' => '',
                'expense' => '',
                'money_source' => '',
            ];
            $currentRow++;

            $categoryIncome = 0;
            $categoryExpense = 0;
            $itemNo = 1; // Nomor urut per kategori

            // Items in category
            foreach ($expenses as $expense) {
                $data[] = [
                    'no' => $itemNo++,
                    'invoice' => $expense->invoice_number ?? '',
                    'date' => Carbon::parse($expense->transaction_date)->format('d/m/Y'),
                    'description' => $expense->description ?? '',
                    'income' => $expense->income_amount ? 'Rp ' . number_format($expense->income_amount, 0, ',', '.') : '',
                    'expense' => $expense->expense_amount ? 'Rp ' . number_format($expense->expense_amount, 0, ',', '.') : '',
                    'money_source' => $expense->money_source ?? '',
                ];

                $categoryIncome += $expense->income_amount ?? 0;
                $categoryExpense += $expense->expense_amount ?? 0;
                $currentRow++;
            }

            // Category subtotal (italic untuk pemasukan dan pengeluaran)
            $data[] = [
                'no' => '',
                'invoice' => '',
                'date' => '',
                'description' => '',
                'income' => $categoryIncome > 0 ? number_format($categoryIncome, 0, ',', '.') : '0',
                'expense' => $categoryExpense > 0 ? number_format($categoryExpense, 0, ',', '.') : '0',
                'money_source' => '',
            ];
            $currentRow++;
        }

        // Grand Total
        $data[] = [
            'no' => '',
            'invoice' => '',
            'date' => '',
            'description' => 'Jumlah',
            'income' => 'Rp ' . number_format($this->totals->total_income ?? 0, 0, ',', '.'),
            'expense' => 'Rp ' . number_format($this->totals->total_expense ?? 0, 0, ',', '.'),
            'money_source' => 'Rp ' . number_format($this->totals->balance ?? 0, 0, ',', '.'),
        ];

        // Empty rows before rekapitulasi
        $data[] = ['no' => '', 'invoice' => '', 'date' => '', 'description' => '', 'income' => '', 'expense' => '', 'money_source' => ''];
        $data[] = ['no' => '', 'invoice' => '', 'date' => '', 'description' => '', 'income' => '', 'expense' => '', 'money_source' => ''];

        // Rekapitulasi header
        $data[] = [
            'no' => '',
            'invoice' => '',
            'date' => '',
            'description' => 'Rekapitulasi Pengeluaran Divisi Produksi ' . $this->periodTitle,
            'income' => '',
            'expense' => '',
            'money_source' => '',
        ];

        // Rekapitulasi items
        $data[] = [
            'no' => '1.',
            'invoice' => '',
            'date' => '',
            'description' => 'Uang Masuk',
            'income' => 'Rp ' . number_format($this->totals->total_income ?? 0, 0, ',', '.'),
            'expense' => '',
            'money_source' => 'UANG MASUK',
        ];

        $data[] = [
            'no' => '2.',
            'invoice' => '',
            'date' => '',
            'description' => 'Uang Keluar',
            'income' => 'Rp ' . number_format($this->totals->total_expense ?? 0, 0, ',', '.'),
            'expense' => '',
            'money_source' => 'UANG KELUAR',
        ];

        $data[] = [
            'no' => '',
            'invoice' => '',
            'date' => '',
            'description' => 'Saldo',
            'income' => 'Rp ' . number_format($this->totals->balance ?? 0, 0, ',', '.'),
            'expense' => '',
            'money_source' => 'SALDO',
        ];

        // Empty rows before signatures
        $data[] = ['no' => '', 'invoice' => '', 'date' => '', 'description' => '', 'income' => '', 'expense' => '', 'money_source' => ''];
        $data[] = ['no' => '', 'invoice' => '', 'date' => '', 'description' => '', 'income' => '', 'expense' => '', 'money_source' => ''];

        // Signature headers
        $data[] = [
            'no' => '',
            'invoice' => 'Dibuat / Diperiksa',
            'date' => '',
            'description' => '',
            'income' => '',
            'expense' => '',
            'money_source' => 'Direktur PT. Aghitsna',
        ];

        // Empty rows for signature space
        $data[] = ['no' => '', 'invoice' => '', 'date' => '', 'description' => '', 'income' => '', 'expense' => '', 'money_source' => ''];
        $data[] = ['no' => '', 'invoice' => '', 'date' => '', 'description' => '', 'income' => '', 'expense' => '', 'money_source' => ''];
        $data[] = ['no' => '', 'invoice' => '', 'date' => '', 'description' => '', 'income' => '', 'expense' => '', 'money_source' => ''];

        // Signature names
        $data[] = [
            'no' => '',
            'invoice' => '( A.Khuluqi )',
            'date' => '',
            'description' => '',
            'income' => '',
            'expense' => '',
            'money_source' => '( Zulkarnaen, ST )',
        ];

        return collect($data);
    }

    public function headings(): array
    {
        return [
            ['PT. AGHITSNA KARYA INDAH'],
            ['LAPORAN PENGELUARAN DIVISI PRODUKSI'],
            ['PERIODE ' . $this->periodTitle],
            [
                'NO',
                'FAKTUR',
                'TANGGAL',
                'KETERANGAN',
                'PEMASUKAN',
                'PENGELUARAN',
                'SUMBER UANG',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // Merge and style title (Row 1)
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Merge and style subtitle (Row 2)
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Merge and style period (Row 3)
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(3)->setRowHeight(16);

        // Header row styling (Row 4)
        $sheet->getStyle('A4:G4')->applyFromArray([
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

        $sheet->getRowDimension(4)->setRowHeight(30);

        // Find the last row with "Saldo" to stop borders before signature section
        $lastDataRow = $highestRow;
        for ($row = 5; $row <= $highestRow; $row++) {
            $cellD = $sheet->getCell('D' . $row)->getValue();
            if ($cellD === 'Saldo') {
                $lastDataRow = $row;
                break;
            }
        }

        // Data rows border (only up to rekapitulasi section, excluding signatures)
        $sheet->getStyle('A5:G' . $lastDataRow)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Center align columns
        $sheet->getStyle('A5:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C5:C' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E5:F' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Apply styling to category headers and rows
                for ($row = 5; $row <= $highestRow; $row++) {
                    $cellD = $sheet->getCell('D' . $row)->getValue();
                    $cellA = $sheet->getCell('A' . $row)->getValue();
                    $cellE = $sheet->getCell('E' . $row)->getValue();
                    $cellF = $sheet->getCell('F' . $row)->getValue();

                    // Category header rows (background hijau #A9D08E)
                    if (
                        empty($cellA) && !empty($cellD) && $cellD === strtoupper($cellD) &&
                        !in_array($cellD, ['Jumlah']) &&
                        !str_contains($cellD, 'Rekapitulasi')
                    ) {

                        // Merge A to D for category header (hanya sampai kolom KETERANGAN)
                        $sheet->mergeCells('A' . $row . ':D' . $row);

                        // Background hijau hanya untuk kolom A sampai D (sampai KETERANGAN)
                        $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'A9D08E'], // Hijau muda
                            ],
                            'font' => ['bold' => true, 'size' => 10],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);

                        // Border untuk semua kolom (A sampai G)
                        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                            ],
                        ]);

                        // Kolom E, F, G tetap putih (tanpa background hijau)
                        $sheet->getStyle('E' . $row . ':G' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFFFFF'], // Putih
                            ],
                        ]);
                    }

                    // Category subtotal rows (background kuning #FFCC00, italic)
                    if (empty($cellA) && empty($cellD) && (!empty($cellE) || !empty($cellF))) {
                        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFCC00'], // Kuning/Orange
                            ],
                            'font' => ['italic' => true],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                            ],
                        ]);
                        // Right align untuk subtotal
                        $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }

                    // Jumlah (Grand Total) row
                    if ($cellD === 'Jumlah') {
                        // Merge A to D for "Jumlah" text
                        $sheet->mergeCells('A' . $row . ':D' . $row);

                        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFCC00'], // Kuning/Orange
                            ],
                            'font' => ['bold' => true],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                            ],
                        ]);

                        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }

                    // Rekapitulasi header
                    if (str_contains($cellD, 'Rekapitulasi')) {
                        $sheet->mergeCells('D' . $row . ':G' . $row);
                        $sheet->getStyle('D' . $row)->applyFromArray([
                            'font' => ['bold' => true, 'size' => 10],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                        ]);
                        // Remove borders for rekapitulasi section
                        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                            ],
                        ]);
                    }

                    // Rekapitulasi detail rows (1., 2., Saldo)
                    if (in_array($cellA, ['1.', '2.', '']) && in_array($cellD, ['Uang Masuk', 'Uang Keluar', 'Saldo'])) {
                        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                            ],
                        ]);
                        $sheet->getStyle('D' . $row)->applyFromArray([
                            'font' => ['bold' => $cellD === 'Saldo'],
                        ]);
                        $sheet->getStyle('E' . $row)->applyFromArray([
                            'font' => ['bold' => true],
                        ]);
                        $sheet->getStyle('G' . $row)->applyFromArray([
                            'font' => ['bold' => true],
                        ]);
                    }

                    // Signature headers (Dibuat/Diperiksa, Direktur)
                    $cellB = $sheet->getCell('B' . $row)->getValue();
                    $cellG = $sheet->getCell('G' . $row)->getValue();

                    if ($cellB === 'Dibuat / Diperiksa' || $cellG === 'Direktur PT. Aghitsna') {
                        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                            ],
                            'font' => ['bold' => true],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                    }

                    // Signature names (A.Khuluqi, Zulkarnaen)
                    if ($cellB === '( A.Khuluqi )' || $cellG === '( Zulkarnaen, ST )') {
                        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                            ],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                    }

                    // Empty rows before and between signature (remove borders)
                    if (empty($cellA) && empty($cellB) && empty($cellD) && empty($cellE) && empty($cellF) && empty($cellG)) {
                        // Check if this is after rekapitulasi section
                        if ($row > 5) {
                            $prevRow = $row - 1;
                            $prevCellD = $sheet->getCell('D' . $prevRow)->getValue();
                            $prevCellB = $sheet->getCell('B' . $prevRow)->getValue();

                            // If previous row has Saldo or is signature-related, remove border
                            if ($prevCellD === 'Saldo' || $prevCellB === 'Dibuat / Diperiksa' || strpos($prevCellB, 'Khuluqi') !== false) {
                                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                                    'borders' => [
                                        'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                                    ],
                                ]);
                            }
                        }
                    }
                }
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,     // NO
            'B' => 25,    // FAKTUR
            'C' => 12,    // TANGGAL
            'D' => 35,    // KETERANGAN
            'E' => 15,    // PEMASUKAN
            'F' => 15,    // PENGELUARAN
            'G' => 20,    // SUMBER UANG
        ];
    }

    public function title(): string
    {
        return 'Laporan Pengeluaran';
    }
}
