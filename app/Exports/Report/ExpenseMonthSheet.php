<?php

namespace App\Exports\Report;

use App\Models\Report\TransactionCategory;
use Carbon\Carbon;
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
 * Satu sheet laporan pengeluaran (satu bulan, atau seluruh periode bila
 * export hanya satu bulan).
 *
 * Dipakai oleh ExpenseRecapExport (Rekap Pengeluaran, varian "rekap") dan
 * ExpenseReportExport (Laporan Pengeluaran, varian "laporan"). Kedua varian
 * punya struktur sama — dikelompokkan per kategori dengan subtotal, total
 * (Jumlah), rekapitulasi, dan tanda tangan — hanya berbeda warna & label.
 *
 * Posisi setiap jenis baris dicatat saat membangun data sehingga styling
 * tidak bergantung pada pencocokan isi sel (mis. keterangan huruf kapital
 * tidak lagi salah dianggap judul kategori).
 */
class ExpenseMonthSheet implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    public const VARIANT_REKAP = 'rekap';
    public const VARIANT_LAPORAN = 'laporan';

    /**
     * Perbedaan tampilan antar varian (mengikuti format export sebelumnya).
     */
    private const VARIANTS = [
        self::VARIANT_REKAP => [
            'period_prefix' => 'PERIODE ',
            'header_fill' => 'FFFF00',
            'subtotal_fill' => 'FFCC00',
            'subtotal_label' => '',
            'subtotal_bold' => false,
            'total_fill' => 'FFCC00',
            'total_label' => 'Jumlah',
            'signature_titles' => ['Dibuat / Diperiksa', 'Direktur PT. Aghitsna'],
            'signature_names' => ['( AKHMAD KHAIDIR )', '( Zulkarnain,ST.,MT )'],
            'widths' => ['A' => 5, 'B' => 25, 'C' => 12, 'D' => 40, 'E' => 17, 'F' => 17, 'G' => 20],
        ],
        self::VARIANT_LAPORAN => [
            'period_prefix' => '',
            'header_fill' => '9EA974',
            'subtotal_fill' => 'E2AD28',
            'subtotal_label' => 'SUB TOTAL',
            'subtotal_bold' => true,
            'total_fill' => 'E5C327',
            'total_label' => 'JUMLAH',
            'signature_titles' => ['DIBUAT/DIPERIKSA', 'MENGETAHUI, DIREKTUR PT. AGHITSNA KARYA INDAH'],
            'signature_names' => ['( A. KHAIDIR )', '( Zulkarnain,ST.,MT )'],
            'widths' => ['A' => 5, 'B' => 25, 'C' => 12, 'D' => 40, 'E' => 17, 'F' => 17, 'G' => 22],
        ],
    ];

    /** Baris header tabel. */
    private const HEADER_ROW = 4;

    /** Baris awal data. */
    private const DATA_START_ROW = 5;

    /** @var array Konfigurasi varian aktif */
    protected $config;

    /** @var array<string, array<int, int>> Nomor baris per jenis (category, item, subtotal, rekap, signature) */
    protected $rows = [];

    /** @var int|null Nomor baris total (Jumlah) */
    protected $totalRow = null;

    /** @var int|null Nomor baris judul rekapitulasi */
    protected $rekapTitleRow = null;

    /**
     * @param  \Illuminate\Support\Collection $expenseRecaps  Data rekap pengeluaran untuk sheet ini
     * @param  string                         $periodTitle    Judul periode (tanpa prefix "PERIODE")
     * @param  object                         $totals         total_income, total_expense, balance
     * @param  string                         $variant        self::VARIANT_REKAP | self::VARIANT_LAPORAN
     * @param  string                         $sheetTitle     Nama sheet (≤ 31 karakter)
     * @param  array|null                     $carry          ['opening_balance' => int, 'closing_balance' => int]
     *                                                        untuk bulan ke-2 dst. pada export multi-bulan
     */
    public function __construct(
        protected $expenseRecaps,
        protected string $periodTitle,
        protected $totals,
        protected string $variant,
        protected string $sheetTitle,
        protected ?array $carry = null
    ) {
        $this->config = self::VARIANTS[$variant];
    }

    /**
     * Membangun seluruh baris sheet.
     *
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $this->rows = ['category' => [], 'item' => [], 'empty' => [], 'subtotal' => [], 'rekap' => [], 'signature' => []];

        $data = [
            ['PT. AGHITSNA KARYA INDAH'],
            ['LAPORAN PENGELUARAN DIVISI PRODUKSI'],
            [$this->config['period_prefix'] . $this->periodTitle],
            ['NO', 'FAKTUR', 'TANGGAL', 'KETERANGAN', 'PEMASUKAN', 'PENGELUARAN', 'SUMBER UANG'],
        ];
        $currentRow = self::DATA_START_ROW;

        // Semua kategori aktif milik modul Rekap Pengeluaran, urut sort_order (sama seperti PDF)
        $allCategories = TransactionCategory::where('created_by', auth()->id())
            ->module(TransactionCategory::MODULE_EXPENSE_RECAP)
            ->active()->orderBy('sort_order')->get();
        $expenseRecapsById = $this->expenseRecaps->groupBy('transaction_category_id');

        foreach ($allCategories as $category) {
            $expenses = $expenseRecapsById->get($category->id, collect());

            $data[] = [strtoupper($category->name ?? 'LAIN-LAIN')];
            $this->rows['category'][] = $currentRow++;

            $categoryIncome = 0;
            $categoryExpense = 0;
            $itemNo = 1;

            foreach ($expenses as $expense) {
                $data[] = [
                    $itemNo++,
                    $expense->invoice_number ?? '',
                    $expense->transaction_date ? Carbon::parse($expense->transaction_date)->format('d/m/Y') : '',
                    $expense->description ?? '',
                    $expense->income_amount ? $this->rupiah($expense->income_amount) : '',
                    $expense->expense_amount ? $this->rupiah($expense->expense_amount) : '',
                    $expense->money_source ?? '',
                ];
                $this->rows['item'][] = $currentRow++;

                $categoryIncome += $expense->income_amount ?? 0;
                $categoryExpense += $expense->expense_amount ?? 0;
            }

            // Baris kosong putih jika tidak ada data
            if ($expenses->isEmpty()) {
                $data[] = [''];
                $this->rows['empty'][] = $currentRow++;
            }

            $data[] = ['', '', '', $this->config['subtotal_label'], $this->rupiah($categoryIncome), $this->rupiah($categoryExpense), ''];
            $this->rows['subtotal'][] = $currentRow++;
        }

        // Total (Jumlah)
        $data[] = [
            $this->config['total_label'], '', '', '',
            $this->rupiah($this->totals->total_income ?? 0),
            $this->rupiah($this->totals->total_expense ?? 0),
            $this->rupiah($this->totals->balance ?? 0),
        ];
        $this->totalRow = $currentRow++;

        // Baris kosong sebelum rekapitulasi
        $data[] = [''];
        $data[] = [''];
        $currentRow += 2;

        $data[] = ['Rekapitulasi Pengeluaran Divisi Produksi ' . $this->periodTitle];
        $this->rekapTitleRow = $currentRow++;

        $rekapLines = [
            ['1.  UANG MASUK', $this->totals->total_income ?? 0, false],
            ['2.  UANG KELUAR', $this->totals->total_expense ?? 0, false],
            ['SALDO', $this->totals->balance ?? 0, true],
        ];
        if ($this->carry) {
            // Saldo dibawa dari bulan sebelumnya (dalam rentang export)
            $rekapLines[] = ['SALDO BULAN SEBELUMNYA', $this->carry['opening_balance'], false];
            $rekapLines[] = ['SALDO AKHIR (KUMULATIF)', $this->carry['closing_balance'], true];
        }
        foreach ($rekapLines as [$label, $value, $bold]) {
            $data[] = [$label, '', '', '', $this->rupiah($value)];
            $this->rows['rekap'][] = ['row' => $currentRow++, 'bold' => $bold];
        }

        // Baris kosong sebelum tanda tangan
        $data[] = [''];
        $data[] = [''];
        $currentRow += 2;

        [$leftTitle, $rightTitle] = $this->config['signature_titles'];
        [$leftName, $rightName] = $this->config['signature_names'];

        // Tanda tangan kiri di kolom B, kanan di E:G (digabung agar judul panjang tidak terpotong)
        $data[] = ['', $leftTitle, '', '', $rightTitle];
        $this->rows['signature'][] = $currentRow++;

        // Ruang tanda tangan
        $data[] = [''];
        $data[] = [''];
        $data[] = [''];
        $currentRow += 3;

        $data[] = ['', $leftName, '', '', $rightName];
        $this->rows['signature'][] = $currentRow;

        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->applyHeaderStyles($sheet);
                $this->applyTableStyles($sheet);
                $this->applyRekapStyles($sheet);
                $this->applySignatureStyles($sheet);
                $this->applyPageSetup($sheet);
            },
        ];
    }

    public function columnWidths(): array
    {
        return $this->config['widths'];
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    // ============================================================
    // PRIVATE HELPERS
    // ============================================================

    /**
     * Format angka ke Rupiah (Rp 1.000.000) — sama seperti export sebelumnya.
     */
    private function rupiah($value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }

    /**
     * Styling judul (3 baris) dan header tabel.
     */
    private function applyHeaderStyles(Worksheet $sheet): void
    {
        $h = self::HEADER_ROW;

        foreach ([1 => 15, 2 => 13, 3 => 12] as $row => $size) {
            $sheet->mergeCells("A{$row}:G{$row}");
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true, 'size' => $size],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(19);
        $sheet->getRowDimension(3)->setRowHeight(18);

        $sheet->getStyle("A{$h}:G{$h}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->config['header_fill']]],
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
     * Border, alignment, kategori, subtotal, dan total.
     */
    private function applyTableStyles(Worksheet $sheet): void
    {
        $start = self::DATA_START_ROW;
        $end = $this->totalRow;

        $sheet->getStyle("A{$start}:G{$end}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Kolom NO & TANGGAL rata tengah, nominal rata kanan, faktur/keterangan/sumber uang wrap
        $sheet->getStyle("A{$start}:A{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$start}:C{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$start}:F{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("B{$start}:B{$end}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("D{$start}:D{$end}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("G{$start}:G{$end}")->getAlignment()->setWrapText(true);

        // Judul kategori: A:D digabung, hijau; E:G tetap putih
        foreach ($this->rows['category'] as $row) {
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A9D08E']],
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // Subtotal per kategori (italic)
        foreach ($this->rows['subtotal'] as $row) {
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->config['subtotal_fill']]],
                'font' => ['italic' => true, 'bold' => $this->config['subtotal_bold']],
            ]);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // Total (Jumlah): A:D digabung
        $row = $this->totalRow;
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->config['total_fill']]],
            'font' => ['bold' => true],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($row)->setRowHeight(18);
    }

    /**
     * Styling rekapitulasi (tanpa border, rata kiri, nominal tebal).
     */
    private function applyRekapStyles(Worksheet $sheet): void
    {
        $row = $this->rekapTitleRow;
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);

        foreach ($this->rows['rekap'] as $line) {
            $row = $line['row'];
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setBold($line['bold']);
            $sheet->getStyle("E{$row}")->getFont()->setBold(true);
            $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
    }

    /**
     * Styling blok tanda tangan (tebal & rata tengah untuk judul).
     */
    private function applySignatureStyles(Worksheet $sheet): void
    {
        [$titleRow, $nameRow] = $this->rows['signature'];

        $sheet->mergeCells("E{$titleRow}:G{$titleRow}");
        $sheet->mergeCells("E{$nameRow}:G{$nameRow}");

        $sheet->getStyle("A{$titleRow}:G{$titleRow}")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle("A{$nameRow}:G{$nameRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
