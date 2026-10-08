<?php

namespace App\Exports\Finance;

use App\Models\Finance\PurchaseInvoice;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export class untuk Faktur Pembelian ke Excel.
 *
 * Mendukung filter search, month, year jika $request diberikan.
 * Format: headers, styling, dan column widths sudah dikonfigurasi.
 */
class PurchaseInvoiceExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithEvents, WithDefaultStyles
{
    /**
     * Request untuk filter data (opsional).
     *
     * @var Request|null
     */
    protected $request;

    /**
     * @param  Request|null $request  Request berisi filter search/month/year
     */
    public function __construct($request = null)
    {
        $this->request = $request;
    }

    /**
     * Query data faktur pembelian untuk export.
     *
     * Menggunakan model scopes untuk filter agar tidak duplikasi logic.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = PurchaseInvoice::query();

        if ($this->request) {
            $search = $this->request->input('search');
            $month  = $this->request->input('month');
            $year   = $this->request->input('year');

            $query->search($search)
                ->filterByMonth($month)
                ->filterByYear($year);
        }

        // Urut tanggal faktur (lama → baru); tanggal sama → urutan input.
        return $query->orderBy('date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(function ($invoice, $index) {
                return [
                    $index + 1,
                    $invoice->date->format('d/m/Y'),
                    $invoice->material_name,
                    $invoice->npwp,
                    $invoice->tax_number_code,
                    $invoice->item_name,
                    format_rupiah($invoice->selling_price),
                    format_rupiah($invoice->ppn_tax),
                    format_rupiah($invoice->selling_price + $invoice->ppn_tax),
                    $invoice->notes ?? '',
                ];
            });
    }

    /**
     * Header kolom Excel.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'NO',
            'TANGGAL',
            'NAMA MATERIAL',
            'NPWP',
            'KODE NOMOR SERI PAJAK',
            'NAMA BARANG',
            'HARGA JUAL',
            'PPN PENGENAAN PAJAK',
            'TOTAL',
            'KETERANGAN',
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
            'B' => 12,
            'C' => 25,
            'D' => 22,
            'E' => 24,
            'F' => 26,
            'G' => 16,
            'H' => 18,
            'I' => 16,
            'J' => 35,
        ];
    }

    /**
     * Font default workbook: Times New Roman 11pt (seragam dengan PDF).
     *
     * @param  Style $defaultStyle
     * @return array
     */
    public function defaultStyles(Style $defaultStyle)
    {
        return ['font' => ['name' => 'Times New Roman', 'size' => 11]];
    }

    /**
     * Styling header row.
     *
     * @param  Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }

    /**
     * Event setelah sheet dibuat: apply borders dan alignment.
     *
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Apply borders ke semua data rows
                $highestRow = $sheet->getHighestRow();
                for ($row = 2; $row <= $highestRow; $row++) {
                    for ($col = 'A'; $col <= 'J'; $col++) {
                        $sheet->getStyle($col . $row)
                            ->getBorders()
                            ->getAllBorders()
                            ->setBorderStyle(Border::BORDER_THIN);
                    }
                }

                // Center align kolom NO dan TANGGAL
                $sheet->getStyle('A2:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B2:B' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Revisi klien: NAMA BARANG (F), HARGA JUAL (G), PPN PAJAK (H) rata tengah
                $sheet->getStyle('F2:H' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Semua data rata tengah secara vertikal agar rapi saat ada teks yang wrap
                $sheet->getStyle('A1:J' . $highestRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // Setup cetak: A4 landscape, muat 1 halaman lebar, header tabel berulang tiap halaman
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

                // Wrap text untuk kolom E, F, H, J
                $sheet->getStyle('E1:E' . $highestRow)->getAlignment()->setWrapText(true);
                $sheet->getStyle('F1:F' . $highestRow)->getAlignment()->setWrapText(true);
                $sheet->getStyle('H1:H' . $highestRow)->getAlignment()->setWrapText(true);
                $sheet->getStyle('J1:J' . $highestRow)->getAlignment()->setWrapText(true);
            },
        ];
    }
}
