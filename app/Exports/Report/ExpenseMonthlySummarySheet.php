<?php

namespace App\Exports\Report;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Rekap Per Bulan" untuk export pengeluaran yang mencakup lebih dari
 * satu bulan: total uang masuk/keluar & saldo tiap bulan, saldo kumulatif
 * (saldo dibawa ke bulan berikutnya), dan total keseluruhan periode.
 *
 * Diletakkan sebagai sheet terakhir setelah sheet per bulan.
 */
class ExpenseMonthlySummarySheet implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    /** Baris header tabel. */
    private const HEADER_ROW = 4;

    /** @var int|null Nomor baris total */
    protected $totalRow = null;

    /**
     * @param  array   $sections     Hasil ExpenseMonthlySections::build()
     * @param  string  $periodTitle  Judul periode keseluruhan (mis. "TAHUN 2026")
     * @param  object  $totals       Total keseluruhan: total_income, total_expense, balance
     * @param  string  $headerFill   Warna header tabel (mengikuti varian laporan)
     * @param  string  $totalFill    Warna baris total (mengikuti varian laporan)
     */
    public function __construct(
        protected array $sections,
        protected string $periodTitle,
        protected $totals,
        protected string $headerFill = 'FFFF00',
        protected string $totalFill = 'FFCC00'
    ) {}

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $data = [
            ['PT. AGHITSNA KARYA INDAH'],
            ['REKAPITULASI LAPORAN PENGELUARAN DIVISI PRODUKSI PER BULAN'],
            [$this->periodTitle],
            ['NO', 'BULAN', 'UANG MASUK', 'UANG KELUAR', 'SALDO BULAN INI', 'SALDO KUMULATIF'],
        ];

        foreach ($this->sections as $index => $section) {
            $data[] = [
                $index + 1,
                strtoupper($section['label']),
                $this->rupiah($section['totals']->total_income),
                $this->rupiah($section['totals']->total_expense),
                $this->rupiah($section['totals']->balance),
                $this->rupiah($section['closing_balance']),
            ];
        }

        $data[] = [
            'JUMLAH', '',
            $this->rupiah($this->totals->total_income ?? 0),
            $this->rupiah($this->totals->total_expense ?? 0),
            $this->rupiah($this->totals->balance ?? 0),
            $this->rupiah($this->totals->balance ?? 0),
        ];
        $this->totalRow = count($data);

        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $h = self::HEADER_ROW;
                $end = $this->totalRow;

                foreach ([1 => 15, 2 => 13, 3 => 12] as $row => $size) {
                    $sheet->mergeCells("A{$row}:F{$row}");
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => $size],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                }
                $sheet->getRowDimension(1)->setRowHeight(22);
                $sheet->getRowDimension(2)->setRowHeight(19);
                $sheet->getRowDimension(3)->setRowHeight(18);

                $sheet->getStyle("A{$h}:F{$h}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->headerFill]],
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);
                $sheet->getRowDimension($h)->setRowHeight(24);

                $sheet->getStyle("A{$h}:F{$end}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);
                $sheet->getStyle('A' . ($h + 1) . ":A{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('C' . ($h + 1) . ":F{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->mergeCells("A{$end}:B{$end}");
                $sheet->getStyle("A{$end}:F{$end}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->totalFill]],
                    'font' => ['bold' => true],
                ]);

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 24,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 20,
        ];
    }

    public function title(): string
    {
        return 'Rekap Per Bulan';
    }

    /**
     * Format angka ke Rupiah (Rp 1.000.000).
     */
    private function rupiah($value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }
}
