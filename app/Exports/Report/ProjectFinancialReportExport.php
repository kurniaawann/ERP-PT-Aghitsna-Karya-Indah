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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export class untuk Laporan Keuangan Proyek ke Excel.
 *
 * Kolom (8 Kolom):
 * A: NO | B: BON | C: TANGGAL | D: KETERANGAN | E: UANG MASUK | F: UANG KELUAR | G: SALDO | H: KETERANGAN BON
 */
class ProjectFinancialReportExport implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $recap;
    protected $items;
    protected $totals;

    /** @var array Menyimpan metadata tipe baris untuk styling dinamis di AfterSheet */
    protected $rowMetadata = [];

    public function __construct($recap, $items, $totals)
    {
        $this->recap = $recap;
        $this->items = $items;
        $this->totals = $totals;
    }

    public function collection()
    {
        $data = [];
        $currentRow = 5; // Data dimulai dari baris ke-5 (Row 1-3 Header, Row 4 Table Header)
        $catNo = 1;
        $runningBalance = 0;

        $itemsByCategory = $this->items->groupBy('transaction_category_id');
        $categories = $this->items->pluck('category')->filter()->unique('id');

        foreach ($categories as $category) {
            $categoryItems = $itemsByCategory->get($category->id, collect());
            $categoryIncome = 0;
            $categoryExpense = 0;
            $bonNo = 1;
            $isFirstItem = true;

            // Baris Header Kategori (Hijau)
            $data[] = [
                'no' => '',
                'bon' => '',
                'date' => '',
                'description' => $category->name ?? 'Lain - lain',
                'income' => '',
                'expense' => '',
                'balance' => '',
                'keterangan_bon' => '',
            ];
            $this->rowMetadata[$currentRow] = ['type' => 'CATEGORY_HEADER'];
            $currentRow++;

            // Item Transaksi dalam Kategori
            foreach ($categoryItems as $item) {
                // Baris informasi (kasbon personal) hanya keterangan,
                // tidak memengaruhi subtotal kategori maupun saldo berjalan.
                $inc = $item->is_informational ? 0 : ($item->income_amount ?? 0);
                $exp = $item->is_informational ? 0 : ($item->expense_amount ?? 0);
                $categoryIncome += $inc;
                $categoryExpense += $exp;
                $runningBalance += ($inc - $exp);

                $data[] = [
                    'no' => $isFirstItem ? $catNo : '',
                    'bon' => $bonNo++,
                    'date' => $item->transaction_date ? Carbon::parse($item->transaction_date)->format('d/m/Y') : '',
                    'description' => ($item->description ?? '').($item->is_informational ? ' (informasi)' : ''),
                    'income' => $inc ?: null,
                    'expense' => $exp ?: null,
                    'balance' => $item->is_informational ? null : $runningBalance,
                    'keterangan_bon' => $item->keterangan_bon ?? '',
                ];

                $this->rowMetadata[$currentRow] = ['type' => 'ITEM'];
                $currentRow++;
                $isFirstItem = false;
            }

            // Subtotal Kategori (Kuning)
            $data[] = [
                'no' => '',
                'bon' => '',
                'date' => '',
                'description' => '',
                'income' => $categoryIncome ?: null,
                'expense' => $categoryExpense ?: null,
                'balance' => $runningBalance,
                'keterangan_bon' => '',
            ];
            $this->rowMetadata[$currentRow] = ['type' => 'SUBTOTAL'];
            $currentRow++;

            $catNo++;
        }

        // Grand Total Row (Abu-abu)
        $data[] = [
            'no' => 'Jumlah',
            'bon' => '',
            'date' => '',
            'description' => '',
            'income' => $this->totals->total_income ?? 0,
            'expense' => $this->totals->total_expense ?? 0,
            'balance' => $this->totals->balance ?? $runningBalance,
            'keterangan_bon' => '',
        ];
        $this->rowMetadata[$currentRow] = ['type' => 'GRAND_TOTAL'];
        $currentRow++;

        // Sub-label Keterangan Total di bawah kolom (Uang Masuk, Uang Keluar, Sisa Saldo)
        $data[] = [
            'no' => '',
            'bon' => '',
            'date' => '',
            'description' => '',
            'income' => 'Uang Masuk',
            'expense' => 'Uang Keluar',
            'balance' => 'Sisa Saldo',
            'keterangan_bon' => '',
        ];
        $this->rowMetadata[$currentRow] = ['type' => 'SUMMARY_LABEL'];
        $currentRow++;

        // Space Kosong Sebelum Tanda Tangan
        $data[] = array_fill(0, 8, ''); $currentRow++;
        $data[] = array_fill(0, 8, ''); $currentRow++;

        // Header Tanda Tangan
        $data[] = [
            'no' => 'MANDOR',
            'bon' => '',
            'date' => '',
            'description' => 'KABAG KEUANGAN',
            'income' => '',
            'expense' => '',
            'balance' => 'DIREKTUR PT. AGHITSNA KARYA INDAH',
            'keterangan_bon' => '',
        ];
        $this->rowMetadata[$currentRow] = ['type' => 'SIGNATURE_TITLE'];
        $currentRow++;

        // Space untuk Tanda Tangan
        $data[] = array_fill(0, 8, ''); $currentRow++;
        $data[] = array_fill(0, 8, ''); $currentRow++;
        $data[] = array_fill(0, 8, ''); $currentRow++;

        // Nama Penandatangan
        $data[] = [
            'no' => 'Siswoyo',
            'bon' => '',
            'date' => '',
            'description' => 'Kamila',
            'income' => '',
            'expense' => '',
            'balance' => 'Zulkarnain, S.T., M.T.',
            'keterangan_bon' => '',
        ];
        $this->rowMetadata[$currentRow] = ['type' => 'SIGNATURE_NAME'];

        return collect($data);
    }

    public function headings(): array
    {
        $projectLine = $this->recap->project_name ?? 'Proyek Rumah Kos 4 Lantai';
        $locationLine = $this->recap->location ?? 'Jl. XYZ - Jakarta Selatan';

        return [
            ['', '', '', 'LAPORAN KEUANGAN', '', '', '', 'Tgl Edit Terakhir : ' . Carbon::now()->format('d F Y')],
            ['', '', '', $projectLine, '', '', '', ''],
            ['', '', '', $locationLine, '', '', '', ''],
            [
                'No',
                'Bon',
                'Tanggal',
                'Keterangan',
                'Uang Masuk',
                'Uang Keluar',
                'Saldo',
                'Keterangan Bon',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Judul Utama (Row 1)
        $sheet->mergeCells('D1:G1');
        $sheet->getStyle('D1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Tanggal Edit Terakhir (Top Right)
        $sheet->getStyle('H1')->applyFromArray([
            'font' => ['size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        // Proyek Name & Location (Row 2 & 3)
        $sheet->mergeCells('D2:G2');
        $sheet->mergeCells('D3:G3');
        $sheet->getStyle('D2:D3')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Table Headings Styling (Row 4)
        $sheet->getStyle('A4:H4')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFC000'], // Kuning Amber
            ],
            'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial'],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '7F7F7F']],
            ],
        ]);

        $sheet->getRowDimension(4)->setRowHeight(22);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->rowMetadata as $row => $meta) {
                    $type = $meta['type'];

                    if ($type === 'CATEGORY_HEADER') {
                        // Merge A sampai D untuk judul kategori
                        $sheet->mergeCells("A{$row}:D{$row}");
                        
                        // Background Hijau untuk A:D
                        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => '92D050'], // Hijau Kategori
                            ],
                            'font' => ['bold' => true, 'italic' => true, 'size' => 10, 'name' => 'Arial'],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);

                        // Border seluruh baris A:H
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '7F7F7F']],
                            ],
                        ]);
                    } elseif ($type === 'ITEM') {
                        // Border & Alignment untuk Item Transaksi
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '7F7F7F']],
                            ],
                            'font' => ['size' => 9.5, 'name' => 'Arial'],
                        ]);

                        // Alignment
                        $sheet->getStyle("A{$row}:C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("E{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        // Warna Teks
                        $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB('002060'); // Biru Bon
                        $sheet->getStyle("B{$row}")->getFont()->setBold(true);

                        $sheet->getStyle("E{$row}")->getFont()->getColor()->setRGB('548235'); // Hijau Uang Masuk
                        $sheet->getStyle("F{$row}")->getFont()->getColor()->setRGB('C65911'); // Cokelat Uang Keluar

                        // Number Formatting
                        $sheet->getStyle("E{$row}:G{$row}")->getNumberFormat()->setFormatCode('#,##0');
                    } elseif ($type === 'SUBTOTAL') {
                        // Background Kuning Subtotal
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFC000'],
                            ],
                            'font' => ['bold' => true, 'italic' => true, 'underline' => true, 'size' => 9.5, 'name' => 'Arial'],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '7F7F7F']],
                            ],
                        ]);

                        $sheet->getStyle("E{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("E{$row}")->getFont()->getColor()->setRGB('548235');
                        $sheet->getStyle("F{$row}")->getFont()->getColor()->setRGB('C65911');
                        $sheet->getStyle("E{$row}:G{$row}")->getNumberFormat()->setFormatCode('#,##0');
                    } elseif ($type === 'GRAND_TOTAL') {
                        // Merge A sampai D untuk teks "Jumlah"
                        $sheet->mergeCells("A{$row}:D{$row}");

                        // Background Abu-abu Total
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'BFBFBF'],
                            ],
                            'font' => ['bold' => true, 'italic' => true, 'underline' => true, 'size' => 10, 'name' => 'Arial'],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '7F7F7F']],
                            ],
                        ]);

                        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("E{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        
                        // Format Currency "Rp. #,##0"
                        $sheet->getStyle("E{$row}:G{$row}")->getNumberFormat()->setFormatCode('"Rp. "#,##0');
                    } elseif ($type === 'SUMMARY_LABEL') {
                        $sheet->getStyle("E{$row}:G{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'italic' => true, 'size' => 9.5, 'name' => 'Arial'],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                    } elseif ($type === 'SIGNATURE_TITLE') {
                        $sheet->mergeCells("A{$row}:B{$row}");
                        $sheet->mergeCells("G{$row}:H{$row}");

                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 9.5, 'name' => 'Arial'],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                    } elseif ($type === 'SIGNATURE_NAME') {
                        $sheet->mergeCells("A{$row}:B{$row}");
                        $sheet->mergeCells("G{$row}:H{$row}");

                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'underline' => true, 'size' => 9.5, 'name' => 'Arial'],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                    }
                }
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // NO
            'B' => 6,   // BON
            'C' => 13,  // TANGGAL
            'D' => 42,  // KETERANGAN
            'E' => 16,  // UANG MASUK
            'F' => 16,  // UANG KELUAR
            'G' => 16,  // SALDO
            'H' => 24,  // KETERANGAN BON
        ];
    }

    public function title(): string
    {
        return 'Laporan_Keuangan';
    }
}