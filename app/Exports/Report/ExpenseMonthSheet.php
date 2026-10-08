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
 * Aturan per role (revisi klien):
 * - Admin (menu "Kas Kantor"): tanpa kolom FAKTUR, kategori tanpa transaksi
 *   tidak ditampilkan, dan tanpa blok Rekapitulasi (uang masuk/keluar/saldo).
 * - Super admin / role lain: kolom FAKTUR, semua kategori aktif, Rekapitulasi.
 *
 * Posisi setiap jenis baris dicatat saat membangun data sehingga styling
 * tidak bergantung pada pencocokan isi sel (mis. keterangan huruf kapital
 * tidak lagi salah dianggap judul kategori). Huruf kolom dihitung dinamis
 * karena kolom FAKTUR bisa tidak ada.
 */
class ExpenseMonthSheet implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    public const VARIANT_REKAP = 'rekap';
    public const VARIANT_LAPORAN = 'laporan';

    /**
     * Perbedaan tampilan antar varian (mengikuti format export sebelumnya).
     * Baris "Jumlah" varian rekap berlatar kuning (revisi klien).
     */
    private const VARIANTS = [
        self::VARIANT_REKAP => [
            'period_prefix' => 'PERIODE ',
            'header_fill' => 'FFFF00',
            'subtotal_fill' => 'FFCC00',
            'subtotal_label' => '',
            'subtotal_bold' => false,
            'total_fill' => 'FFFF00',
            'total_label' => 'Jumlah',
            'signature_titles' => ['Dibuat / Diperiksa', 'Direktur PT. Aghitsna'],
            'signature_names' => ['( AKHMAD KHAIDIR )', '( Zulkarnain,ST.,MT )'],
            'widths' => ['no' => 5, 'faktur' => 25, 'tanggal' => 12, 'keterangan' => 40, 'pemasukan' => 17, 'pengeluaran' => 17, 'sumber' => 20],
            'orientation' => PageSetup::ORIENTATION_PORTRAIT,
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
            'widths' => ['no' => 5, 'faktur' => 25, 'tanggal' => 12, 'keterangan' => 40, 'pemasukan' => 17, 'pengeluaran' => 17, 'sumber' => 22],
            'orientation' => PageSetup::ORIENTATION_LANDSCAPE,
        ],
    ];

    /** Header kolom tabel per kunci kolom. */
    private const COLUMN_HEADINGS = [
        'no' => 'NO',
        'faktur' => 'FAKTUR',
        'tanggal' => 'TANGGAL',
        'keterangan' => 'KETERANGAN',
        'pemasukan' => 'PEMASUKAN',
        'pengeluaran' => 'PENGELUARAN',
        'sumber' => 'SUMBER UANG',
    ];

    /** Baris header tabel. */
    private const HEADER_ROW = 4;

    /** Baris awal data. */
    private const DATA_START_ROW = 5;

    /** @var array Konfigurasi varian aktif */
    protected $config;

    /** @var bool Role admin (Kas Kantor): tanpa faktur, tanpa kategori kosong, tanpa rekapitulasi */
    protected $isAdmin;

    /** @var array<string, string> Huruf kolom per kunci kolom (no, faktur, tanggal, ...) */
    protected $columns = [];

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
        $this->isAdmin = auth()->user()?->isAdmin() ?? false;

        // Susun huruf kolom: kolom FAKTUR hanya untuk non-admin
        $keys = $this->isAdmin
            ? ['no', 'tanggal', 'keterangan', 'pemasukan', 'pengeluaran', 'sumber']
            : ['no', 'faktur', 'tanggal', 'keterangan', 'pemasukan', 'pengeluaran', 'sumber'];

        foreach ($keys as $index => $key) {
            $this->columns[$key] = chr(ord('A') + $index);
        }
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
            [$this->isAdmin ? 'LAPORAN PENGELUARAN' : 'LAPORAN PENGELUARAN DIVISI PRODUKSI'],
            [$this->config['period_prefix'] . $this->periodTitle],
            array_values(array_intersect_key(self::COLUMN_HEADINGS, $this->columns)),
        ];
        $currentRow = self::DATA_START_ROW;

        // Semua kategori aktif milik modul Rekap Pengeluaran, urut sort_order (sama seperti PDF)
        $allCategories = TransactionCategory::where('created_by', auth()->id())
            ->module(TransactionCategory::MODULE_EXPENSE_RECAP)
            ->active()->orderBy('sort_order')->get();
        $expenseRecapsById = $this->expenseRecaps->groupBy('transaction_category_id');

        foreach ($allCategories as $category) {
            $expenses = $expenseRecapsById->get($category->id, collect());

            // Admin: kategori tanpa transaksi tidak ditampilkan sama sekali
            if ($expenses->isEmpty() && $this->isAdmin) {
                continue;
            }

            $data[] = [strtoupper($category->name ?? 'LAIN-LAIN')];
            $this->rows['category'][] = $currentRow++;

            $categoryIncome = 0;
            $categoryExpense = 0;
            $itemNo = 1;

            foreach ($expenses as $expense) {
                // Baris pemasukan: kolom pengeluaran kosong, dan sebaliknya (bukan "Rp. 0")
                $data[] = $this->buildRow([
                    'no' => $itemNo++,
                    'faktur' => $expense->invoice_number ?? '',
                    'tanggal' => $expense->transaction_date ? Carbon::parse($expense->transaction_date)->format('d/m/Y') : '',
                    'keterangan' => $expense->description ?? '',
                    'pemasukan' => (int) $expense->income_amount > 0 ? format_rupiah($expense->income_amount) : '',
                    'pengeluaran' => (int) $expense->expense_amount > 0 ? format_rupiah($expense->expense_amount) : '',
                    'sumber' => $expense->money_source ?? '',
                ]);
                $this->rows['item'][] = $currentRow++;

                $categoryIncome += $expense->income_amount ?? 0;
                $categoryExpense += $expense->expense_amount ?? 0;
            }

            // Baris kosong putih jika tidak ada data (hanya non-admin)
            if ($expenses->isEmpty()) {
                $data[] = [''];
                $this->rows['empty'][] = $currentRow++;
            }

            $data[] = $this->buildRow([
                'keterangan' => $this->config['subtotal_label'],
                'pemasukan' => $this->subtotalRupiah($categoryIncome, $categoryExpense),
                'pengeluaran' => $this->subtotalRupiah($categoryExpense, $categoryIncome),
            ]);
            $this->rows['subtotal'][] = $currentRow++;
        }

        // Total (Jumlah)
        $data[] = $this->buildRow([
            'no' => $this->config['total_label'],
            'pemasukan' => format_rupiah($this->totals->total_income ?? 0),
            'pengeluaran' => format_rupiah($this->totals->total_expense ?? 0),
            'sumber' => format_rupiah($this->totals->balance ?? 0),
        ]);
        $this->totalRow = $currentRow++;

        // Rekapitulasi — tidak ditampilkan untuk admin (Kas Kantor)
        if (!$this->isAdmin) {
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
                $data[] = $this->buildRow(['no' => $label, 'pemasukan' => format_rupiah($value)]);
                $this->rows['rekap'][] = ['row' => $currentRow++, 'bold' => $bold];
            }
        }

        // Baris kosong sebelum tanda tangan
        $data[] = [''];
        $data[] = [''];
        $currentRow += 2;

        [$leftTitle, $rightTitle] = $this->config['signature_titles'];
        [$leftName, $rightName] = $this->config['signature_names'];

        [$leftStart] = $this->signatureRanges()['left'];
        [$rightStart] = $this->signatureRanges()['right'];

        $data[] = $this->buildSignatureRow($leftStart, $leftTitle, $rightStart, $rightTitle);
        $this->rows['signature'][] = $currentRow++;

        // Ruang tanda tangan
        $data[] = [''];
        $data[] = [''];
        $data[] = [''];
        $currentRow += 3;

        $data[] = $this->buildSignatureRow($leftStart, $leftName, $rightStart, $rightName);
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
        $widths = [];
        foreach ($this->columns as $key => $letter) {
            $widths[$letter] = $this->config['widths'][$key];
        }

        return $widths;
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    // ============================================================
    // PRIVATE HELPERS
    // ============================================================

    /**
     * Susun satu baris berurutan sesuai kolom aktif dari array [kunci => nilai].
     *
     * @param  array<string, mixed> $values
     * @return array<int, mixed>
     */
    private function buildRow(array $values): array
    {
        $row = [];
        foreach (array_keys($this->columns) as $key) {
            $row[] = $values[$key] ?? '';
        }

        return $row;
    }

    /**
     * Baris tanda tangan: teks kiri & kanan pada kolom awal blok masing-masing.
     */
    private function buildSignatureRow(string $leftColumn, string $leftText, string $rightColumn, string $rightText): array
    {
        $row = array_fill(0, count($this->columns), '');
        $row[ord($leftColumn) - ord('A')] = $leftText;
        $row[ord($rightColumn) - ord('A')] = $rightText;

        return $row;
    }

    /**
     * Rentang kolom blok tanda tangan kiri & kanan.
     *
     * - Dengan faktur (7 kolom): kiri di B (seperti sebelumnya), kanan E:G.
     * - Tanpa faktur (6 kolom): kiri A:C digabung, kanan D:F digabung.
     *
     * @return array{left: array{0: string, 1: string}, right: array{0: string, 1: string}}
     */
    private function signatureRanges(): array
    {
        if ($this->isAdmin) {
            return [
                'left' => [$this->columns['no'], $this->columns['keterangan']],
                'right' => [$this->columns['pemasukan'], $this->columns['sumber']],
            ];
        }

        return [
            'left' => [$this->columns['faktur'], $this->columns['faktur']],
            'right' => [$this->columns['pemasukan'], $this->columns['sumber']],
        ];
    }

    /**
     * Nominal subtotal: sisi yang nol dikosongkan bila sisi lainnya terisi
     * (mis. kategori pemasukan tidak menampilkan "Rp. 0" di kolom pengeluaran).
     */
    private function subtotalRupiah($value, $other): string
    {
        return ((int) $value === 0 && (int) $other !== 0) ? '' : format_rupiah($value);
    }

    /**
     * Huruf kolom terakhir tabel.
     */
    private function lastColumn(): string
    {
        return end($this->columns);
    }

    /**
     * Styling judul (3 baris) dan header tabel.
     */
    private function applyHeaderStyles(Worksheet $sheet): void
    {
        $h = self::HEADER_ROW;
        $last = $this->lastColumn();

        foreach ([1 => 15, 2 => 13, 3 => 12] as $row => $size) {
            $sheet->mergeCells("A{$row}:{$last}{$row}");
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true, 'size' => $size],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(19);
        $sheet->getRowDimension(3)->setRowHeight(18);

        $sheet->getStyle("A{$h}:{$last}{$h}")->applyFromArray([
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
        $last = $this->lastColumn();
        $c = $this->columns;

        $sheet->getStyle("A{$start}:{$last}{$end}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Kolom NO & TANGGAL rata tengah, nominal rata kanan, faktur/keterangan/sumber uang wrap
        $sheet->getStyle("{$c['no']}{$start}:{$c['no']}{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$c['tanggal']}{$start}:{$c['tanggal']}{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$c['pemasukan']}{$start}:{$c['pengeluaran']}{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        if (isset($c['faktur'])) {
            $sheet->getStyle("{$c['faktur']}{$start}:{$c['faktur']}{$end}")->getAlignment()->setWrapText(true);
        }
        $sheet->getStyle("{$c['keterangan']}{$start}:{$c['keterangan']}{$end}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("{$c['sumber']}{$start}:{$c['sumber']}{$end}")->getAlignment()->setWrapText(true);

        // Judul kategori: NO s/d KETERANGAN digabung, hijau; kolom nominal tetap putih
        foreach ($this->rows['category'] as $row) {
            $sheet->mergeCells("A{$row}:{$c['keterangan']}{$row}");
            $sheet->getStyle("A{$row}:{$c['keterangan']}{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A9D08E']],
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // Subtotal per kategori (italic)
        foreach ($this->rows['subtotal'] as $row) {
            $sheet->getStyle("A{$row}:{$last}{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->config['subtotal_fill']]],
                'font' => ['italic' => true, 'bold' => $this->config['subtotal_bold']],
            ]);
            $sheet->getStyle("{$c['keterangan']}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // Total (Jumlah): NO s/d KETERANGAN digabung
        $row = $this->totalRow;
        $sheet->mergeCells("A{$row}:{$c['keterangan']}{$row}");
        $sheet->getStyle("A{$row}:{$last}{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->config['total_fill']]],
            'font' => ['bold' => true],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$c['sumber']}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($row)->setRowHeight(18);
    }

    /**
     * Styling rekapitulasi (tanpa border, rata kiri, nominal tebal).
     * Tidak ada untuk admin.
     */
    private function applyRekapStyles(Worksheet $sheet): void
    {
        if ($this->rekapTitleRow === null) {
            return;
        }

        $c = $this->columns;
        $row = $this->rekapTitleRow;
        $sheet->mergeCells("A{$row}:{$this->lastColumn()}{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);

        foreach ($this->rows['rekap'] as $line) {
            $row = $line['row'];
            $sheet->mergeCells("A{$row}:{$c['keterangan']}{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setBold($line['bold']);
            $sheet->getStyle("{$c['pemasukan']}{$row}")->getFont()->setBold(true);
            $sheet->getStyle("{$c['pemasukan']}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
    }

    /**
     * Styling blok tanda tangan (tebal & rata tengah untuk judul).
     */
    private function applySignatureStyles(Worksheet $sheet): void
    {
        [$titleRow, $nameRow] = $this->rows['signature'];
        $ranges = $this->signatureRanges();
        $last = $this->lastColumn();

        foreach ([$titleRow, $nameRow] as $row) {
            foreach ($ranges as [$from, $to]) {
                if ($from !== $to) {
                    $sheet->mergeCells("{$from}{$row}:{$to}{$row}");
                }
            }
        }

        $sheet->getStyle("A{$titleRow}:{$last}{$titleRow}")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle("A{$nameRow}:{$last}{$nameRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /**
     * Setup cetak: A4 (rekap: portrait, laporan: landscape), muat 1 halaman
     * lebar, header tabel berulang di setiap halaman.
     */
    private function applyPageSetup(Worksheet $sheet): void
    {
        $sheet->getPageSetup()
            ->setOrientation($this->config['orientation'])
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(self::HEADER_ROW, self::HEADER_ROW);
        $sheet->getPageMargins()->setLeft(0.4)->setRight(0.4)->setTop(0.5)->setBottom(0.5);
    }
}
