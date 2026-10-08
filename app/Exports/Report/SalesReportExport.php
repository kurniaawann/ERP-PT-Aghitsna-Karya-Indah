<?php

namespace App\Exports\Report;

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
 * Export class untuk Laporan Penjualan (List Order Divisi Produksi) ke Excel.
 *
 * Data berasal dari SalesReportService::buildExportData() (proyek & penjualan
 * sudah terurut dari transaksi paling awal). Struktur sheet:
 * - NO digabung per proyek; TANGGAL dan NO FAKTUR digabung per penjualan
 *   (setiap faktur tampil, nama proyek di bawah faktur pertama)
 * - Subtotal per proyek (+ keterangan Sudah Lunas) dan grand total
 * - Tanda tangan
 *
 * Posisi setiap jenis baris dicatat saat membangun data sehingga styling
 * tidak bergantung pada pencocokan isi sel.
 */
class SalesReportExport implements FromArray, WithColumnWidths, WithTitle, WithEvents, WithDefaultStyles
{
    protected $projects;
    protected $periodTitle;
    protected $grandTotal;

    /** @var array<int, array{col: string, start: int, end: int}> Merge vertikal */
    protected $mergeInfo = [];

    /** @var array<int, int> Nomor baris subtotal proyek */
    protected $subtotalRows = [];

    /** @var int|null Nomor baris grand total */
    protected $grandTotalRow = null;

    /** @var array<int, int> Nomor baris tanda tangan (judul & nama) */
    protected $signatureRows = [];

    /** Baris header tabel. */
    private const HEADER_ROW = 4;

    /** Baris awal data. */
    private const DATA_START_ROW = 5;

    /** Format angka rupiah (pemisah ribuan mengikuti locale Excel). */
    private const RUPIAH_FORMAT = '"Rp. "#,##0';

    public function __construct($projects, $periodTitle, $grandTotal)
    {
        $this->projects = $projects;
        $this->periodTitle = $periodTitle;
        $this->grandTotal = $grandTotal;
    }

    /**
     * Membangun seluruh baris sheet (judul, header, data, total, tanda tangan).
     *
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $this->mergeInfo = [];
        $this->subtotalRows = [];
        $this->signatureRows = [];

        $data = [
            ['LAPORAN PENJUALAN LIST ORDER DIVISI PRODUKSI'],
            [$this->periodTitle],
            [''],
            ['NO', 'TANGGAL', 'NO FAKTUR & PROYEK', 'NAMA BARANG', 'QTY', 'HARGA MODAL', 'HARGA JUAL', 'JUMLAH', 'TOTAL'],
        ];

        $no = 1;
        $currentRow = self::DATA_START_ROW;

        foreach ($this->projects as $project) {
            $projectStartRow = $currentRow;

            foreach ($project['sales_recaps'] as $saleIndex => $sale) {
                $saleStartRow = $currentRow;

                foreach ($sale['items'] as $itemIndex => $item) {
                    $isFirstInSale = $itemIndex === 0;
                    $isFirstInProject = $isFirstInSale && $saleIndex === 0;

                    $faktur = '';
                    if ($isFirstInSale) {
                        $faktur = $sale['no_faktur'];
                        if ($saleIndex === 0) {
                            $faktur .= "\n" . strtoupper($project['project_name']);
                        }
                    }

                    $data[] = [
                        $isFirstInProject ? $no : '',
                        $isFirstInSale ? $sale['date'] : '',
                        $faktur,
                        $item['name_item'],
                        $item['qty'],
                        $item['capital_price'],
                        $item['selling_price'],
                        $item['jumlah'],
                        '',
                    ];
                    $currentRow++;
                }

                // TANGGAL dan NO FAKTUR digabung per penjualan
                $this->mergeInfo[] = ['col' => 'B', 'start' => $saleStartRow, 'end' => $currentRow - 1];
                $this->mergeInfo[] = ['col' => 'C', 'start' => $saleStartRow, 'end' => $currentRow - 1];
            }

            // NO digabung per proyek
            $this->mergeInfo[] = ['col' => 'A', 'start' => $projectStartRow, 'end' => $currentRow - 1];

            $subtotalLabel = 'TOTAL';
            if (($project['sales_recaps'][0]['status'] ?? '') === 'Lunas') {
                $subtotalLabel .= ' (Sudah Lunas ' . ($project['lunas_date'] ?? '') . ')';
            }

            $data[] = ['', '', '', $subtotalLabel, '', '', '', '', $project['subtotal']];
            $this->subtotalRows[] = $currentRow;
            $currentRow++;

            $no++;
        }

        $data[] = ['TOTAL PENJUALAN BELUM PROFIT', '', '', '', '', '', '', '', $this->grandTotal];
        $this->grandTotalRow = $currentRow;
        $currentRow++;

        // Baris kosong sebelum tanda tangan
        $data[] = [''];
        $data[] = [''];
        $currentRow += 2;

        // Tanda tangan: 3 blok (A:C, D:F, G:I) digabung di applySignatureStyles
        $data[] = ['DIBUAT/DIPERIKSA', '', '', 'KAB. KEUANGAN', '', '', "MENGETAHUI,\nDIREKTUR PT. AGHITSNA KARYA INDAH"];
        $this->signatureRows[] = $currentRow;
        $currentRow++;

        // Ruang tanda tangan
        $data[] = [''];
        $data[] = [''];
        $data[] = [''];
        $currentRow += 3;

        $data[] = ['( A. KHAIDIR )', '', '', '( Kamila,AMK )', '', '', '( Zulkarnain,ST.,MT )'];
        $this->signatureRows[] = $currentRow;

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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->applyHeaderStyles($sheet);
                $this->applyDataStyles($sheet);
                $this->applyMerges($sheet);
                $this->applyTotalStyles($sheet);
                $this->applySignatureStyles($sheet);
                $this->applyPageSetup($sheet);
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 12,
            'C' => 28,
            'D' => 28,
            'E' => 7,
            'F' => 15,
            'G' => 15,
            'H' => 16,
            'I' => 17,
        ];
    }

    public function title(): string
    {
        return 'Laporan_Penjualan';
    }

    // ============================================================
    // PRIVATE HELPERS
    // ============================================================

    /**
     * Styling judul, periode, dan header tabel.
     */
    private function applyHeaderStyles(Worksheet $sheet): void
    {
        $h = self::HEADER_ROW;

        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1:A2')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(3)->setRowHeight(6);

        $sheet->getStyle("A{$h}:I{$h}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '9EA974']],
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);
        $sheet->getRowDimension($h)->setRowHeight(24);
    }

    /**
     * Border, alignment, dan format rupiah untuk baris data sampai grand total.
     */
    private function applyDataStyles(Worksheet $sheet): void
    {
        $start = self::DATA_START_ROW;
        $end = $this->grandTotalRow;

        $sheet->getStyle("A{$start}:I{$end}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle("A{$start}:C{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$start}:D{$end}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("E{$start}:E{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$start}:I{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("F{$start}:I{$end}")->getNumberFormat()->setFormatCode(self::RUPIAH_FORMAT);
    }

    /**
     * Merge vertikal NO (per proyek) serta TANGGAL & NO FAKTUR (per penjualan).
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
     * Styling subtotal proyek dan grand total.
     */
    private function applyTotalStyles(Worksheet $sheet): void
    {
        foreach ($this->subtotalRows as $row) {
            $sheet->mergeCells("D{$row}:H{$row}");
            $sheet->getStyle("D{$row}:I{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2AD28']],
                'font' => ['bold' => true],
            ]);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getRowDimension($row)->setRowHeight(18);
        }

        $row = $this->grandTotalRow;
        $sheet->mergeCells("A{$row}:H{$row}");
        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5C327']],
            'font' => ['bold' => true],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(18);
    }

    /**
     * Styling blok tanda tangan (tanpa border, rata tengah, tebal).
     */
    private function applySignatureStyles(Worksheet $sheet): void
    {
        foreach ($this->signatureRows as $row) {
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->mergeCells("D{$row}:F{$row}");
            $sheet->mergeCells("G{$row}:I{$row}");
            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true,
                ],
            ]);
        }

        // Judul tanda tangan direktur 2 baris (sel gabungan tidak auto-fit tinggi)
        $sheet->getRowDimension($this->signatureRows[0])->setRowHeight(30);
    }

    /**
     * Setup cetak: A4 landscape, muat 1 halaman lebar, header tabel berulang.
     */
    private function applyPageSetup(Worksheet $sheet): void
    {
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(self::HEADER_ROW, self::HEADER_ROW);
        $sheet->getPageMargins()->setLeft(0.4)->setRight(0.4)->setTop(0.5)->setBottom(0.5);
    }
}
