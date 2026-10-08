<?php

namespace App\Exports\Report;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export class untuk Laporan Profit Penjualan (Rekap Penjualan) ke Excel.
 *
 * Fitur:
 * - Grouping by proyek dengan merged cells (NO/TANGGAL per penjualan,
 *   PROYEK/SUMBER UANG per proyek)
 * - Kolom HPP dan HARGA JUAL dipecah menjadi SATUAN | JUMLAH
 * - Subtotal per proyek dan grand total
 * - Footer info (Modal Aghitsna, Modal Divisi Holo, PROFIT)
 *
 * Urutan data mengikuti collection yang dikirim controller (tanggal menaik).
 * Posisi setiap jenis baris dicatat saat membangun data sehingga styling
 * tidak bergantung pada pencocokan isi sel.
 */
class SalesRecapExport implements FromArray, WithColumnWidths, WithTitle, WithEvents, WithDefaultStyles
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
     * Info merge cells vertikal (NO/TANGGAL per penjualan, PROYEK/SUMBER UANG per proyek).
     *
     * @var array<int, array{col: string, start: int, end: int}>
     */
    protected $mergeInfo = [];

    /** @var array<int, int> Nomor baris subtotal proyek */
    protected $subtotalRows = [];

    /** @var int|null Nomor baris grand total */
    protected $grandTotalRow = null;

    /** @var array<int, int> Nomor baris footer info */
    protected $footerRows = [];

    /** Kolom terakhir tabel. */
    private const LAST_COL = 'K';

    /** Baris header tabel (2 baris: grup + sub kolom). */
    private const HEADER_ROW = 4;

    /** Baris awal data (setelah header 2 baris). */
    private const DATA_START_ROW = 6;

    /**
     * @param  \Illuminate\Support\Collection $salesRecaps  Data rekap penjualan
     * @param  int|null                       $month        Filter bulan
     * @param  int|null                       $year         Filter tahun
     */
    public function __construct($salesRecaps, $month = null, $year = null)
    {
        $this->salesRecaps = $salesRecaps;
        $this->monthYear = $this->buildMonthYearLabel($month ? (int) $month : null, $year ? (int) $year : null);
    }

    /**
     * Membangun seluruh baris sheet (judul, header, data, total, footer).
     *
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $this->mergeInfo = [];
        $this->subtotalRows = [];
        $this->footerRows = [];

        $periodLabel = strtoupper($this->monthYear);
        $periodLabel = str_starts_with($periodLabel, 'TAHUN') ? $periodLabel : 'BULAN ' . $periodLabel;

        $data = [
            ['LAPORAN PROFIT PENJUALAN DIVISI PRODUKSI'],
            [$periodLabel],
            [''],
            ['NO', 'TANGGAL', 'PROYEK', 'NAMA BARANG', 'QTY', 'HPP (HARGA MODAL)', '', 'HARGA JUAL', '', 'PROFIT', 'SUMBER UANG'],
            ['', '', '', '', '', 'SATUAN', 'JUMLAH', 'SATUAN', 'JUMLAH', '', ''],
        ];

        $no = 1;
        $grandTotalCapital = 0;
        $grandTotalSelling = 0;
        $grandTotalProfit = 0;
        $currentRow = self::DATA_START_ROW;

        // groupBy mempertahankan urutan kemunculan pertama → proyek terurut dari transaksi paling awal
        foreach ($this->salesRecaps->groupBy('name_proyek') as $projectName => $projectSales) {
            $projectTotalCapital = 0;
            $projectTotalSelling = 0;
            $projectTotalProfit = 0;
            $projectStartRow = $currentRow;

            foreach ($projectSales as $sale) {
                $items = is_string($sale->items) ? json_decode($sale->items, true) : $sale->items;
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

                    $isFirstInProject = $currentRow === $projectStartRow;

                    $data[] = [
                        $index === 0 ? $no : '',
                        $index === 0 ? Carbon::parse($sale->date)->format('d/m/Y') : '',
                        $isFirstInProject ? strtoupper($projectName ?: '-') : '',
                        $item['name_item'] ?? '',
                        $qty,
                        $this->rupiah($capital),
                        $this->rupiah($totalCapital),
                        $this->rupiah($selling),
                        $this->rupiah($totalSelling),
                        $this->rupiah($profit),
                        $isFirstInProject ? strtoupper($projectSales->first()->status) : '',
                    ];

                    $currentRow++;
                }

                // Merge NO dan TANGGAL per penjualan
                $this->mergeInfo[] = ['col' => 'A', 'start' => $saleStartRow, 'end' => $currentRow - 1];
                $this->mergeInfo[] = ['col' => 'B', 'start' => $saleStartRow, 'end' => $currentRow - 1];

                $no++;
            }

            // Merge PROYEK dan SUMBER UANG per proyek
            $this->mergeInfo[] = ['col' => 'C', 'start' => $projectStartRow, 'end' => $currentRow - 1];
            $this->mergeInfo[] = ['col' => 'K', 'start' => $projectStartRow, 'end' => $currentRow - 1];

            // Subtotal per proyek
            $data[] = [
                'SUB TOTAL', '', '', '', '',
                $this->rupiah($projectTotalCapital), '',
                $this->rupiah($projectTotalSelling), '',
                $this->rupiah($projectTotalProfit),
                '',
            ];
            $this->subtotalRows[] = $currentRow;
            $currentRow++;

            $grandTotalCapital += $projectTotalCapital;
            $grandTotalSelling += $projectTotalSelling;
            $grandTotalProfit += $projectTotalProfit;
        }

        // Grand Total
        $data[] = [
            'TOTAL PENJUALAN PROFIT', '', '', '', '',
            $this->rupiah($grandTotalCapital), '',
            $this->rupiah($grandTotalSelling), '',
            $this->rupiah($grandTotalProfit),
            '',
        ];
        $this->grandTotalRow = $currentRow;
        $currentRow++;

        // Baris kosong pemisah
        $data[] = [''];
        $currentRow++;

        // Footer info
        foreach ([
            'Modal Aghitsna' => $grandTotalCapital,
            'Modal Divisi Holo' => $grandTotalSelling,
            'PROFIT' => $grandTotalProfit,
        ] as $label => $value) {
            $data[] = ['', '', $label, '', $this->rupiah($value)];
            $this->footerRows[] = $currentRow;
            $currentRow++;
        }

        return $data;
    }

    /**
     * Font default workbook: Times New Roman 11pt (seragam dengan PDF).
     *
     * @param  \PhpOffice\PhpSpreadsheet\Style\Style $defaultStyle
     * @return array
     */
    public function defaultStyles(Style $defaultStyle)
    {
        return ['font' => ['name' => 'Times New Roman', 'size' => 11]];
    }

    /**
     * Event handler untuk merge cells dan styling.
     *
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->applyHeaderStyles($sheet);
                $this->applyDataStyles($sheet);
                $this->applyMerges($sheet);
                $this->applyTotalStyles($sheet);
                $this->applyFooterStyles($sheet);
                $this->applyPageSetup($sheet);
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
            'A' => 5,
            'B' => 12,
            'C' => 26,
            'D' => 28,
            'E' => 7,
            'F' => 15,
            'G' => 16,
            'H' => 15,
            'I' => 16,
            'J' => 16,
            'K' => 17,
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
     * Format angka ke Rupiah ("Rp. 1.000.000") via helper format_rupiah().
     *
     * @param  int|float $value
     * @return string
     */
    private function rupiah($value): string
    {
        return format_rupiah($value);
    }

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
     * Styling judul dan header tabel 2 baris.
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return void
     */
    private function applyHeaderStyles(Worksheet $sheet): void
    {
        $last = self::LAST_COL;
        $h1 = self::HEADER_ROW;
        $h2 = self::HEADER_ROW + 1;

        $sheet->mergeCells("A1:{$last}1");
        $sheet->mergeCells("A2:{$last}2");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("A1:A2")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(3)->setRowHeight(6);

        // Kolom tunggal: merge vertikal 2 baris header
        foreach (['A', 'B', 'C', 'D', 'E', 'J', 'K'] as $col) {
            $sheet->mergeCells("{$col}{$h1}:{$col}{$h2}");
        }
        // Grup kolom HPP dan HARGA JUAL
        $sheet->mergeCells("F{$h1}:G{$h1}");
        $sheet->mergeCells("H{$h1}:I{$h1}");

        $sheet->getStyle("A{$h1}:{$last}{$h2}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);
        $sheet->getRowDimension($h1)->setRowHeight(20);
        $sheet->getRowDimension($h2)->setRowHeight(18);
    }

    /**
     * Border dan alignment baris data (sampai grand total).
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return void
     */
    private function applyDataStyles(Worksheet $sheet): void
    {
        $start = self::DATA_START_ROW;
        $end = $this->grandTotalRow;
        $last = self::LAST_COL;

        $sheet->getStyle("A{$start}:{$last}{$end}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle("A{$start}:B{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$start}:D{$end}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("E{$start}:E{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$start}:J{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("K{$start}:K{$end}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setWrapText(true);
    }

    /**
     * Merge vertikal NO/TANGGAL per penjualan dan PROYEK/SUMBER UANG per proyek.
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return void
     */
    private function applyMerges(Worksheet $sheet): void
    {
        foreach ($this->mergeInfo as $merge) {
            // Lewati kelompok tanpa item (tidak ada baris data)
            if ($merge['end'] < $merge['start']) {
                continue;
            }

            $range = $merge['col'] . $merge['start'] . ':' . $merge['col'] . $merge['end'];

            if ($merge['start'] < $merge['end']) {
                $sheet->mergeCells($range);
            }

            $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        }
    }

    /**
     * Styling baris subtotal proyek dan grand total.
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return void
     */
    private function applyTotalStyles(Worksheet $sheet): void
    {
        $rows = array_merge($this->subtotalRows, [$this->grandTotalRow]);

        foreach ($rows as $row) {
            $isGrandTotal = $row === $this->grandTotalRow;

            $sheet->mergeCells("A{$row}:E{$row}");
            $sheet->mergeCells("F{$row}:G{$row}");
            $sheet->mergeCells("H{$row}:I{$row}");

            $sheet->getStyle("A{$row}:" . self::LAST_COL . $row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isGrandTotal ? 'FFFF00' : 'FFC000']],
                'font' => ['bold' => true],
            ]);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(
                $isGrandTotal ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_RIGHT
            );
            $sheet->getRowDimension($row)->setRowHeight(18);
        }
    }

    /**
     * Styling footer info (Modal Aghitsna, Modal Divisi Holo, PROFIT).
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return void
     */
    private function applyFooterStyles(Worksheet $sheet): void
    {
        foreach ($this->footerRows as $row) {
            $sheet->mergeCells("C{$row}:D{$row}");
            $sheet->mergeCells("E{$row}:G{$row}");
            $sheet->getStyle("C{$row}:G{$row}")->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getRowDimension($row)->setRowHeight(18);
        }
    }

    /**
     * Setup cetak: A4 landscape, muat 1 halaman lebar, header tabel berulang.
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return void
     */
    private function applyPageSetup(Worksheet $sheet): void
    {
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(self::HEADER_ROW, self::HEADER_ROW + 1);
        $sheet->getPageMargins()->setLeft(0.4)->setRight(0.4)->setTop(0.5)->setBottom(0.5);
    }
}
