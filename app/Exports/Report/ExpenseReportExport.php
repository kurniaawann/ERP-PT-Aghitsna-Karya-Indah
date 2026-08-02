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
use App\Models\Report\TransactionCategory;
use Carbon\Carbon;

class ExpenseReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $expenseRecaps;
    protected $periodTitle;
    protected $totals;
    protected $mergeInfo = [];

    private const DATA_START_ROW = 5;

    public function __construct($expenseRecaps, $periodTitle, $totals)
    {
        $this->expenseRecaps = $expenseRecaps;
        $this->periodTitle = $periodTitle;
        $this->totals = $totals;
    }

    public function collection()
    {
        $data = [];
        $no = 1;
        $currentRow = self::DATA_START_ROW;

        $allCategories = TransactionCategory::where('created_by', auth()->id())->active()->orderBy('sort_order')->get();
        $expenseRecapsById = $this->expenseRecaps->groupBy('transaction_category_id');

        foreach ($allCategories as $category) {
            $expenses = $expenseRecapsById->get($category->id, collect());
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
            $itemNo = 1;
            $totalItems = $expenses->count();

            // Items in category
            foreach ($expenses as $expense) {
                $row = [
                    'no' => '',
                    'invoice' => '',
                    'date' => '',
                    'description' => $expense->description ?? '',
                    'income' => $expense->income_amount ? 'Rp ' . number_format($expense->income_amount, 0, ',', '.') : '',
                    'expense' => $expense->expense_amount ? 'Rp ' . number_format($expense->expense_amount, 0, ',', '.') : '',
                    'money_source' => $expense->money_source ?? '',
                ];

                // Only set no, invoice, date on first row of category
                if ($itemNo === 1) {
                    $row['no'] = $no;
                    $row['invoice'] = $expense->invoice_number ?? '';
                    $row['date'] = Carbon::parse($expense->transaction_date)->format('d/m/Y');
                }

                $data[] = $row;
                $categoryIncome += $expense->income_amount ?? 0;
                $categoryExpense += $expense->expense_amount ?? 0;
                $currentRow++;
                $itemNo++;
            }

            // Baris kosong putih jika tidak ada data
            if ($expenses->isEmpty()) {
                $data[] = [
                    'no' => '',
                    'invoice' => '',
                    'date' => '',
                    'description' => '',
                    'income' => '',
                    'expense' => '',
                    'money_source' => '',
                ];
                $currentRow++;
            }

            // Merge NO, INVOICE, DATE columns if more than 1 item
            if ($totalItems > 1) {
                $this->mergeInfo[] = ['col' => 'A', 'start' => $categoryStartRow + 1, 'end' => $currentRow - 1];
                $this->mergeInfo[] = ['col' => 'B', 'start' => $categoryStartRow + 1, 'end' => $currentRow - 1];
                $this->mergeInfo[] = ['col' => 'C', 'start' => $categoryStartRow + 1, 'end' => $currentRow - 1];
            }

            // Category subtotal
            $data[] = [
                'no' => '',
                'invoice' => '',
                'date' => '',
                'description' => 'SUB TOTAL',
                'income' => $categoryIncome > 0 ? number_format($categoryIncome, 0, ',', '.') : '0',
                'expense' => $categoryExpense > 0 ? number_format($categoryExpense, 0, ',', '.') : '0',
                'money_source' => '',
            ];
            $currentRow++;

            $no++;
        }

        // Grand Total
        $data[] = [
            'no' => '',
            'invoice' => '',
            'date' => '',
            'description' => 'JUMLAH',
            'income' => 'Rp ' . number_format($this->totals->total_income, 0, ',', '.'),
            'expense' => 'Rp ' . number_format($this->totals->total_expense, 0, ',', '.'),
            'money_source' => 'Rp ' . number_format($this->totals->balance, 0, ',', '.'),
        ];

        // Empty rows before rekapitulasi
        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');
        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');

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
            'description' => 'UANG MASUK',
            'income' => 'Rp ' . number_format($this->totals->total_income, 0, ',', '.'),
            'expense' => '',
            'money_source' => '',
        ];

        $data[] = [
            'no' => '2.',
            'invoice' => '',
            'date' => '',
            'description' => 'UANG KELUAR',
            'income' => 'Rp ' . number_format($this->totals->total_expense, 0, ',', '.'),
            'expense' => '',
            'money_source' => '',
        ];

        $data[] = [
            'no' => '',
            'invoice' => '',
            'date' => '',
            'description' => 'SALDO',
            'income' => 'Rp ' . number_format($this->totals->balance, 0, ',', '.'),
            'expense' => '',
            'money_source' => '',
        ];

        // Empty rows before signatures
        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');
        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');

        // Signature headers
        $data[] = [
            'no' => '',
            'invoice' => 'DIBUAT/DIPERIKSA',
            'date' => '',
            'description' => '',
            'income' => '',
            'expense' => '',
            'money_source' => 'MENGETAHUI, DIREKTUR PT. AGHITSNA KARYA INDAH',
        ];

        // Empty rows for signature space
        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');
        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');
        $data[] = array_fill_keys(array_keys($data[0] ?? []), '');

        // Signature names
        $data[] = [
            'no' => '',
            'invoice' => '( A. KHAIDIR )',
            'date' => '',
            'description' => '',
            'income' => '',
            'expense' => '',
            'money_source' => '( Zulkarnain,ST.,MT )',
        ];

        return collect($data);
    }

    public function headings(): array
    {
        return [
            ['PT. AGHITSNA KARYA INDAH'],
            ['LAPORAN PENGELUARAN DIVISI PRODUKSI'],
            [$this->periodTitle],
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

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        // Merge and style company name (Row 1)
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Merge and style title (Row 2)
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

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(3)->setRowHeight(16);

        // Header row styling (Row 4) - green background matching sales report
        $sheet->getStyle('A4:G4')->applyFromArray([
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

        $sheet->getStyle('A5:G' . $highestRow)->applyFromArray([
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

                $this->applyMergeStyles($sheet);
                $this->applyRowStyles($sheet, $highestRow);
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 20,
            'C' => 12,
            'D' => 30,
            'E' => 18,
            'F' => 18,
            'G' => 20,
        ];
    }

    public function title(): string
    {
        return 'Laporan_Pengeluaran';
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
            $cellA = $sheet->getCell('A' . $row)->getValue();
            $cellB = $sheet->getCell('B' . $row)->getValue();
            $cellD = $sheet->getCell('D' . $row)->getValue();
            $cellE = $sheet->getCell('E' . $row)->getValue();
            $cellF = $sheet->getCell('F' . $row)->getValue();
            $cellG = $sheet->getCell('G' . $row)->getValue();

            // Category header rows (all uppercase, non-empty D, empty A)
            if (
                empty($cellA) && !empty($cellD) && $cellD === strtoupper($cellD) &&
                !in_array($cellD, ['JUMLAH']) &&
                !str_contains($cellD, 'Rekapitulasi')
            ) {
                $categoryName = $cellD;
                $sheet->mergeCells('A' . $row . ':D' . $row);
                $sheet->setCellValue('A' . $row, $categoryName);

                $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A9D08E']],
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);

                $sheet->getStyle('E' . $row . ':G' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                ]);
            }

            // Subtotal rows (SUB TOTAL)
            if ($cellD === 'SUB TOTAL') {
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2AD28']],
                    'font' => ['bold' => true, 'italic' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);
                $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            // Grand total row (JUMLAH)
            if ($cellD === 'JUMLAH') {
                $jumlahText = $cellD;
                $sheet->mergeCells('A' . $row . ':D' . $row);
                $sheet->setCellValue('A' . $row, $jumlahText);

                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5C327']],
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);
                $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            // Rekapitulasi header
            if (str_contains($cellD ?? '', 'Rekapitulasi')) {
                $sheet->mergeCells('D' . $row . ':G' . $row);
                $sheet->getStyle('D' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                ]);
            }

            // Rekapitulasi detail rows
            if (in_array($cellD, ['UANG MASUK', 'UANG KELUAR', 'SALDO'])) {
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                ]);
                $sheet->getStyle('D' . $row)->applyFromArray([
                    'font' => ['bold' => $cellD === 'SALDO'],
                ]);
                $sheet->getStyle('E' . $row)->applyFromArray([
                    'font' => ['bold' => true],
                ]);
                $sheet->getStyle('G' . $row)->applyFromArray([
                    'font' => ['bold' => true],
                ]);
            }

            // Signature headers
            if ($cellB === 'DIBUAT/DIPERIKSA' || ($cellG ?? '') === 'MENGETAHUI, DIREKTUR PT. AGHITSNA KARYA INDAH') {
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }

            // Signature names
            if ($cellB === '( A. KHAIDIR )' || ($cellG ?? '') === '( Zulkarnain,ST.,MT )') {
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }

            // Empty rows between sections (remove borders)
            if (empty($cellA) && empty($cellB) && empty($cellD) && empty($cellE) && empty($cellF) && empty($cellG)) {
                if ($row > self::DATA_START_ROW) {
                    $prevRow = $row - 1;
                    $prevCellD = $sheet->getCell('D' . $prevRow)->getValue();
                    $prevCellB = $sheet->getCell('B' . $prevRow)->getValue();

                    if ($prevCellD === 'SALDO' || $prevCellB === 'DIBUAT/DIPERIKSA' || strpos($prevCellB ?? '', 'KHAIDIR') !== false) {
                        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                        ]);
                    }
                }
            }
        }
    }
}
