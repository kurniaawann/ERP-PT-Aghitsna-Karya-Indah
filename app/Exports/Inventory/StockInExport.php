<?php

namespace App\Exports\Inventory;

use App\Models\Inventory\ItemStockIn;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Barang Masuk ke format Excel (.xlsx).
 *
 * Menghasilkan file Excel dengan header "LAPORAN BARANG MASUK"
 * berisi daftar barang masuk beserta jumlah, harga modal, total, dan tanggal.
 * Mendukung filter pencarian, bulan, dan tahun.
 */
class StockInExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    /**
     * @var string|null Kata kunci pencarian
     */
    protected $search;

    /**
     * @var string|int|null Filter bulan
     */
    protected $month;

    /**
     * @var string|int|null Filter tahun
     */
    protected $year;

    /**
     * @var int Total keseluruhan (quantity x capital_price)
     */
    protected $totalOverall = 0;

    /**
     * @var int Total kuantitas
     */
    protected $totalQuantity = 0;

    /**
     * Konstruktor - menerima filter dan menghitung totals.
     *
     * @param  string|null  $search
     * @param  string|null  $month
     * @param  string|null  $year
     */
    public function __construct($search = null, $month = null, $year = null)
    {
        $this->search = $search;
        $this->month = $month;
        $this->year = $year;

        $this->calculateTotals();
    }

    /**
     * Menghitung total kuantitas dan total keseluruhan.
     *
     * Menggunakan scope Model untuk filter yang konsisten.
     *
     * @return void
     */
    private function calculateTotals(): void
    {
        $query = ItemStockIn::query()
            ->search($this->search)
            ->filterMonth($this->month)
            ->filterYear($this->year);

        $this->totalQuantity = (clone $query)->sum('quantity');
        $this->totalOverall = (clone $query)
            ->selectRaw('COALESCE(SUM(quantity * capital_price), 0) as total')
            ->value('total') ?? 0;
    }

    /**
     * Query data yang akan di-export.
     *
     * Menggunakan scope Model untuk filter dan eager loading relasi.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return ItemStockIn::query()
            ->with('item')
            ->search($this->search)
            ->filterMonth($this->month)
            ->filterYear($this->year)
            ->orderBy('date', 'desc')
            ->orderBy('id_stock_in', 'desc');
    }

    /**
     * Header kolom Excel.
     *
     * @return array
     */
    public function headings(): array
    {
        $monthName = $this->month ? \DateTime::createFromFormat('!m', $this->month)->format('F') : '';
        $yearName = $this->year ?? '';
        $period = $monthName || $yearName ? "Periode: {$monthName} {$yearName}" : 'Semua Data';

        return [
            ['PT AGHITSNA KARYA INDAH'],
            ['LAPORAN BARANG MASUK'],
            [$period],
            [],
            ['No', 'ID Masuk', 'Nama Barang', 'Jumlah', 'Harga Modal', 'Total', 'Tanggal']
        ];
    }

    /**
     * Mapping data setiap baris record Barang Masuk.
     *
     * @param  ItemStockIn  $record
     * @return array
     */
    public function map($record): array
    {
        static $number = 0;
        $number++;

        return [
            $number,
            $record->id_stock_in,
            $record->item->name_item ?? '-',
            $record->quantity,
            $record->capital_price,
            $record->total_capital,
            $record->date->format('d-m-Y'),
        ];
    }

    /**
     * Lebar kolom Excel.
     *
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 20,
            'C' => 30,
            'D' => 10,
            'E' => 15,
            'F' => 15,
            'G' => 15,
        ];
    }

    /**
     * Style header, border, dan format angka.
     *
     * @param  Worksheet  $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(12);

        // Style header utama
        $sheet->getStyle('A1:G1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2:G2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:G3')->getFont()->setItalic(true)->setSize(12);

        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('A1:G3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Style header kolom
        $sheet->getStyle('A5:G5')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAEAEA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Border untuk seluruh data
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A6:G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('D:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Format Rupiah
        $sheet->getStyle('E6:F' . $lastRow)->getNumberFormat()->setFormatCode('_("Rp"* #,##0_);_("Rp"* \(#,##0\);_("Rp"* "-"??_);_(@_)');
    }

    /**
     * Event setelah sheet dibuat - menambahkan summary rows.
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $summaryStartRow = $lastRow + 2;

                // Total Kuantitas
                $sheet->setCellValue("E{$summaryStartRow}", 'Total Kuantitas:');
                $sheet->setCellValue("F{$summaryStartRow}", $this->totalQuantity);

                // Total Keseluruhan
                $sheet->setCellValue("E" . ($summaryStartRow + 1), 'Total Keseluruhan:');
                $sheet->setCellValue("F" . ($summaryStartRow + 1), $this->totalOverall);

                // Style summary
                $sheet->getStyle("E{$summaryStartRow}:F" . ($summaryStartRow + 1))->getFont()->setBold(true);
                $sheet->getStyle("F" . ($summaryStartRow + 1))->getNumberFormat()->setFormatCode('_("Rp"* #,##0_);_("Rp"* \(#,##0\);_("Rp"* "-"??_);_(@_)');
                $sheet->getStyle("E{$summaryStartRow}:E" . ($summaryStartRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }

    /**
     * Judul sheet Excel.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Barang_Masuk';
    }
}
