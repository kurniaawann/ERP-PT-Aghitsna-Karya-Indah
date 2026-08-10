<?php

namespace App\Exports\Report;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export class untuk Laporan Keuangan Proyek ke Excel.
 *
 * Format mirip Rekap Pengeluaran (expense recap):
 * - Header: PT. AGHITSNA KARYA INDAH / LAPORAN KEUANGAN / NAMA PROYEK + LOKASI
 * - Data: Grouped by kategori dengan subtotal per kategori
 * - Grand Total: Jumlah keseluruhan
 * - Rekapitulasi: Ringkasan uang masuk, uang keluar, saldo
 * - Tanda tangan: Dibuat/Diperiksa & Direktur
 *
 * Kolom: NO | TANGGAL | KETERANGAN | UANG MASUK | UANG KELUAR | KETERANGAN BON
 * Penomoran baris: "1 Bon 1", "1 Bon 2" (indeks kategori . indeks bon).
 *
 * @property \App\Models\Finance\ProjectRecap $recap
 * @property \Illuminate\Database\Eloquent\Collection $items
 * @property object $totals
 */
class ProjectFinancialReportExport implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithStyles, WithTitle
{
    /** @var \App\Models\Finance\ProjectRecap Rekap proyek pemilik laporan */
    protected $recap;

    /** @var \Illuminate\Database\Eloquent\Collection Item-item laporan keuangan */
    protected $items;

    /** @var object Total income, expense, dan balance */
    protected $totals;

    /**
     * @param  \App\Models\Finance\ProjectRecap  $recap  Rekap proyek
     * @param  \Illuminate\Database\Eloquent\Collection  $items  Item-item laporan
     * @param  object  $totals  Total income, expense, balance
     */
    public function __construct($recap, $items, $totals)
    {
        $this->recap = $recap;
        $this->items = $items;
        $this->totals = $totals;
    }

    public function collection()
    {
        $data = [];
        $currentRow = 5;
        $catNo = 1;

        $itemsByCategory = $this->items->groupBy('transaction_category_id');
        $categories = $this->items->pluck('category')->filter()->unique('id');

        foreach ($categories as $category) {
            $categoryItems = $itemsByCategory->get($category->id, collect());
            $categoryIncome = 0;
            $categoryExpense = 0;
            $bonNo = 1;

            // Category header row
            $data[] = [
                'no' => '',
                'date' => '',
                'description' => strtoupper($category->name ?? 'LAIN-LAIN'),
                'income' => '',
                'expense' => '',
                'keterangan_bon' => '',
            ];
            $currentRow++;

            // Items in category
            foreach ($categoryItems as $item) {
                $data[] = [
                    'no' => $catNo.' Bon '.$bonNo++,
                    'date' => $item->transaction_date ? Carbon::parse($item->transaction_date)->format('d/m/Y') : '',
                    'description' => $item->description ?? '',
                    'income' => $item->income_amount ? 'Rp '.number_format($item->income_amount, 0, ',', '.') : '',
                    'expense' => $item->expense_amount ? 'Rp '.number_format($item->expense_amount, 0, ',', '.') : '',
                    'keterangan_bon' => $item->keterangan_bon ?? '',
                ];

                $categoryIncome += $item->income_amount ?? 0;
                $categoryExpense += $item->expense_amount ?? 0;
                $currentRow++;
            }

            // Category subtotal (italic untuk pemasukan dan pengeluaran)
            $data[] = [
                'no' => '',
                'date' => '',
                'description' => '',
                'income' => 'Rp '.number_format($categoryIncome, 0, ',', '.'),
                'expense' => 'Rp '.number_format($categoryExpense, 0, ',', '.'),
                'keterangan_bon' => '',
            ];
            $currentRow++;

            $catNo++;
        }

        // Grand Total
        $data[] = [
            'no' => '',
            'date' => '',
            'description' => 'Jumlah',
            'income' => 'Rp '.number_format($this->totals->total_income ?? 0, 0, ',', '.'),
            'expense' => 'Rp '.number_format($this->totals->total_expense ?? 0, 0, ',', '.'),
            'keterangan_bon' => 'Rp '.number_format($this->totals->balance ?? 0, 0, ',', '.'),
        ];

        // Empty rows before rekapitulasi
        $data[] = ['no' => '', 'date' => '', 'description' => '', 'income' => '', 'expense' => '', 'keterangan_bon' => ''];
        $data[] = ['no' => '', 'date' => '', 'description' => '', 'income' => '', 'expense' => '', 'keterangan_bon' => ''];

        // Rekapitulasi header
        $data[] = [
            'no' => '',
            'date' => '',
            'description' => 'Rekapitulasi Laporan Keuangan '.($this->recap->project_name ?? ''),
            'income' => '',
            'expense' => '',
            'keterangan_bon' => '',
        ];

        // Rekapitulasi items
        $data[] = [
            'no' => '1.',
            'date' => '',
            'description' => 'UANG MASUK',
            'income' => 'Rp '.number_format($this->totals->total_income ?? 0, 0, ',', '.'),
            'expense' => '',
            'keterangan_bon' => '',
        ];

        $data[] = [
            'no' => '2.',
            'date' => '',
            'description' => 'UANG KELUAR',
            'income' => 'Rp '.number_format($this->totals->total_expense ?? 0, 0, ',', '.'),
            'expense' => '',
            'keterangan_bon' => '',
        ];

        $data[] = [
            'no' => '',
            'date' => '',
            'description' => 'SALDO',
            'income' => 'Rp '.number_format($this->totals->balance ?? 0, 0, ',', '.'),
            'expense' => '',
            'keterangan_bon' => '',
        ];

        // Empty rows before signatures
        $data[] = ['no' => '', 'date' => '', 'description' => '', 'income' => '', 'expense' => '', 'keterangan_bon' => ''];
        $data[] = ['no' => '', 'date' => '', 'description' => '', 'income' => '', 'expense' => '', 'keterangan_bon' => ''];

        // Signature headers
        $data[] = [
            'no' => '',
            'date' => 'Dibuat / Diperiksa',
            'description' => '',
            'income' => '',
            'expense' => '',
            'keterangan_bon' => 'Direktur PT. Aghitsna',
        ];

        // Empty rows for signature space
        $data[] = ['no' => '', 'date' => '', 'description' => '', 'income' => '', 'expense' => '', 'keterangan_bon' => ''];
        $data[] = ['no' => '', 'date' => '', 'description' => '', 'income' => '', 'expense' => '', 'keterangan_bon' => ''];
        $data[] = ['no' => '', 'date' => '', 'description' => '', 'income' => '', 'expense' => '', 'keterangan_bon' => ''];

        // Signature names
        $data[] = [
            'no' => '',
            'date' => '( AKHMAD KHAIDIR )',
            'description' => '',
            'income' => '',
            'expense' => '',
            'keterangan_bon' => '( Zulkarnain,ST.,MT )',
        ];

        return collect($data);
    }

    public function headings(): array
    {
        $projectLine = $this->recap->project_name ?? '';
        $locationLine = $this->recap->location ?? '';

        return [
            ['PT. AGHITSNA KARYA INDAH'],
            ['LAPORAN KEUANGAN'],
            [$projectLine.(! empty($locationLine) ? ' - '.$locationLine : '')],
            [
                'NO',
                'TANGGAL',
                'KETERANGAN',
                'UANG MASUK',
                'UANG KELUAR',
                'KETERANGAN BON',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // Merge and style title (Row 1)
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Merge and style subtitle (Row 2)
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Merge and style project info (Row 3)
        $sheet->mergeCells('A3:F3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(3)->setRowHeight(16);

        // Header row styling (Row 4)
        $sheet->getStyle('A4:F4')->applyFromArray([
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
            $cellC = $sheet->getCell('C'.$row)->getValue();
            if ($cellC === 'SALDO') {
                $lastDataRow = $row;
                break;
            }
        }

        // Data rows border (only up to rekapitulasi section, excluding signatures)
        $sheet->getStyle('A5:F'.$lastDataRow)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Center align columns
        $sheet->getStyle('A5:A'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B5:B'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D5:E'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                for ($row = 5; $row <= $highestRow; $row++) {
                    $cellA = $sheet->getCell('A'.$row)->getValue();
                    $cellB = $sheet->getCell('B'.$row)->getValue();
                    $cellC = $sheet->getCell('C'.$row)->getValue();
                    $cellD = $sheet->getCell('D'.$row)->getValue();
                    $cellE = $sheet->getCell('E'.$row)->getValue();
                    $cellF = $sheet->getCell('F'.$row)->getValue();

                    // Category header rows (background hijau #A9D08E)
                    if (
                        empty($cellA) && ! empty($cellC) && $cellC === strtoupper($cellC) &&
                        ! in_array($cellC, ['Jumlah', 'SALDO']) &&
                        ! str_contains($cellC, 'Rekapitulasi')
                    ) {

                        // Simpan value sebelum merge (merge menghapus value non-first cell)
                        $categoryName = $sheet->getCell('C'.$row)->getValue();

                        // Merge A to C for category header
                        $sheet->mergeCells('A'.$row.':C'.$row);

                        // Set value kembali setelah merge
                        $sheet->setCellValue('A'.$row, $categoryName);

                        // Background hijau hanya untuk kolom A sampai C (sampai KETERANGAN)
                        $sheet->getStyle('A'.$row.':C'.$row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'A9D08E'], // Hijau muda
                            ],
                            'font' => ['bold' => true, 'size' => 10],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);

                        // Border untuk semua kolom (A sampai F)
                        $sheet->getStyle('A'.$row.':F'.$row)->applyFromArray([
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                            ],
                        ]);

                        // Kolom D, E, F tetap putih (tanpa background hijau)
                        $sheet->getStyle('D'.$row.':F'.$row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFFFFF'], // Putih
                            ],
                        ]);
                    }

                    // Category subtotal rows (background kuning #FFCC00, italic)
                    if (empty($cellA) && empty($cellC) && (! empty($cellD) || ! empty($cellE))) {
                        $sheet->getStyle('A'.$row.':F'.$row)->applyFromArray([
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
                        $sheet->getStyle('D'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle('E'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }

                    // Jumlah (Grand Total) row
                    if ($cellC === 'Jumlah') {
                        // Simpan value sebelum merge
                        $jumlahText = $sheet->getCell('C'.$row)->getValue();

                        // Merge A to C for "Jumlah" text
                        $sheet->mergeCells('A'.$row.':C'.$row);

                        // Set value kembali setelah merge
                        $sheet->setCellValue('A'.$row, $jumlahText);

                        $sheet->getStyle('A'.$row.':F'.$row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFCC00'], // Kuning/Orange
                            ],
                            'font' => ['bold' => true],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                            ],
                        ]);

                        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('D'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle('E'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle('F'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }

                    // Rekapitulasi header (di sebelah kiri)
                    if (! empty($cellC) && str_contains($cellC, 'Rekapitulasi')) {
                        $title = $cellC;
                        $sheet->mergeCells('A'.$row.':F'.$row);
                        $sheet->setCellValue('A'.$row, $title);
                        $sheet->getStyle('A'.$row)->applyFromArray([
                            'font' => ['bold' => true, 'size' => 10],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                        ]);
                        // Remove borders for rekapitulasi section
                        $sheet->getStyle('A'.$row.':F'.$row)->applyFromArray([
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                            ],
                        ]);
                    }

                    // Rekapitulasi detail rows (1., 2., SALDO) — sejajar di kiri
                    if (in_array($cellA, ['1.', '2.', '']) && in_array($cellC, ['UANG MASUK', 'UANG KELUAR', 'SALDO'])) {
                        $label = $cellC;
                        $number = $cellA;
                        $sheet->mergeCells('A'.$row.':C'.$row);
                        $sheet->setCellValue('A'.$row, ($number !== '' ? $number.' ' : '').$label);
                        $sheet->setCellValue('C'.$row, $label);
                        $sheet->getStyle('A'.$row.':F'.$row)->applyFromArray([
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                            ],
                        ]);
                        $sheet->getStyle('A'.$row)->applyFromArray([
                            'font' => ['bold' => $label === 'SALDO'],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                        ]);
                        $sheet->getStyle('D'.$row)->applyFromArray([
                            'font' => ['bold' => true],
                        ]);
                        $sheet->getStyle('F'.$row)->applyFromArray([
                            'font' => ['bold' => true],
                        ]);
                    }

                    // Signature headers (Dibuat/Diperiksa, Direktur)
                    $cellB = $sheet->getCell('B'.$row)->getValue();
                    $cellF = $sheet->getCell('F'.$row)->getValue();

                    if ($cellB === 'Dibuat / Diperiksa' || $cellF === 'Direktur PT. Aghitsna') {
                        $sheet->getStyle('A'.$row.':F'.$row)->applyFromArray([
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                            ],
                            'font' => ['bold' => true],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                    }

                    // Signature names (AKHMAD KHAIDIR, Zulkarnain)
                    if ($cellB === '( AKHMAD KHAIDIR )' || $cellF === '( Zulkarnain,ST.,MT )') {
                        $sheet->getStyle('A'.$row.':F'.$row)->applyFromArray([
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                            ],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                    }

                    // Empty rows before and between signature (remove borders)
                    if (empty($cellA) && empty($cellB) && empty($cellC) && empty($cellD) && empty($cellE) && empty($cellF)) {
                        // Check if this is after rekapitulasi section
                        if ($row > 5) {
                            $prevRow = $row - 1;
                            $prevCellC = $sheet->getCell('C'.$prevRow)->getValue();
                            $prevCellB = $sheet->getCell('B'.$prevRow)->getValue();

                            // If previous row has Saldo or is signature-related, remove border
                            if ($prevCellC === 'SALDO' || $prevCellB === 'Dibuat / Diperiksa' || (! empty($prevCellB) && strpos($prevCellB, 'KHAIDIR') !== false)) {
                                $sheet->getStyle('A'.$row.':F'.$row)->applyFromArray([
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
            'A' => 8,     // NO
            'B' => 12,    // TANGGAL
            'C' => 40,    // KETERANGAN
            'D' => 15,    // UANG MASUK
            'E' => 15,    // UANG KELUAR
            'F' => 25,    // KETERANGAN BON
        ];
    }

    public function title(): string
    {
        return 'Laporan_Keuangan';
    }
}
