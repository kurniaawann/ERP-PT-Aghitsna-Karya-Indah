<?php

namespace App\Exports\Inventory;

use App\Models\Inventory\ItemStockOut;
use Maatwebsite\Excel\Concerns\FromCollection;
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
 * Export data Barang Keluar ke Excel.
 *
 * Menghasilkan file Excel (.xlsx) dengan header perusahaan,
 * data barang keluar, dan ringkasan total kuantitas.
 */
class StockOutExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    /** @var string|null Kata kunci pencarian */
    protected $search;

    /** @var string|int|null Bulan filter (1-12) */
    protected $month;

    /** @var string|int|null Tahun filter */
    protected $year;

    /** @var int Total kuantitas seluruh data (untuk summary) */
    protected $totalQuantity = 0;

    /**
     * @param  string|null $search  Kata kunci pencarian
     * @param  string|int|null $month Bulan filter (1-12)
     * @param  string|int|null $year  Tahun filter
     */
    public function __construct($search = null, $month = null, $year = null)
    {
        $this->search = $search;
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * Mengambil koleksi data untuk di-export.
     *
     * Menggunakan Model scopes untuk filtering, sama dengan controller.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $stockOuts = ItemStockOut::query()
            ->with(['item', 'returns'])
            ->search($this->search)
            ->filterMonth($this->month)
            ->filterYear($this->year)
            ->orderBy('date', 'desc')
            ->orderBy('id_stock_out', 'desc')
            ->get();

        $this->totalQuantity = $stockOuts->sum('quantity');

        return $stockOuts;
    }

    /**
     * Header baris pertama (judul laporan).
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
            ['LAPORAN BARANG KELUAR'],
            [$period],
            [],
            ['No', 'ID Keluar', 'Nama Barang', 'Jumlah', 'Sisa Barang', 'Tanggal', 'Proyek'],
        ];
    }

    /**
     * Mapping data per baris.
     *
     * @param  \App\Models\Inventory\ItemStockOut $record
     * @return array
     */
    public function map($record): array
    {
        static $number = 0;
        $number++;

        return [
            $number,
            $record->id_stock_out,
            $record->item->name_item ?? '-',
            $record->quantity,
            $record->remaining_quantity ?? '-',
            $record->date->format('d-m-Y'),
            $record->project_name ?? '-',
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
            'G' => 20,
        ];
    }

    /**
     * Styling header, judul, dan border.
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(12);

        $sheet->getStyle('A1:G1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2:G2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:G3')->getFont()->setItalic(true)->setSize(12);

        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('A1:G3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A5:G5')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAEAEA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A6:G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('D:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /**
     * Event setelah sheet dibuat — menambahkan baris ringkasan total.
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

                $sheet->setCellValue("D{$summaryStartRow}", 'Total Kuantitas:');
                $sheet->setCellValue("E{$summaryStartRow}", $this->totalQuantity);

                $sheet->getStyle("D{$summaryStartRow}:E{$summaryStartRow}")->getFont()->setBold(true);
                $sheet->getStyle("D{$summaryStartRow}:D{$summaryStartRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }

    /**
     * Nama sheet tab di Excel.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Barang_Keluar';
    }
}
