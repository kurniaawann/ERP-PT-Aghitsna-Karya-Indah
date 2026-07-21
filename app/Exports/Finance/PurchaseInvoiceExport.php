<?php

namespace App\Exports\Finance;

use App\Models\Finance\PurchaseInvoice;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export class untuk Faktur Pembelian ke Excel.
 *
 * Mendukung filter search, month, year jika $request diberikan.
 * Format: headers, styling, dan column widths sudah dikonfigurasi.
 */
class PurchaseInvoiceExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithEvents
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

        return $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($invoice, $index) {
                return [
                    $index + 1,
                    $invoice->date->format('d/m/Y'),
                    $invoice->material_name,
                    $invoice->npwp,
                    $invoice->tax_number_code,
                    $invoice->item_name,
                    'Rp ' . number_format($invoice->selling_price, 0, ',', '.'),
                    'Rp ' . number_format($invoice->ppn_tax, 0, ',', '.'),
                    'Rp ' . number_format($invoice->selling_price + $invoice->ppn_tax, 0, ',', '.'),
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
            'D' => 18,
            'E' => 30,
            'F' => 20,
            'G' => 15,
            'H' => 20,
            'I' => 15,
            'J' => 35,
        ];
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

                // Wrap text untuk kolom E, H, J
                $sheet->getStyle('E1:E' . $highestRow)->getAlignment()->setWrapText(true);
                $sheet->getStyle('H1:H' . $highestRow)->getAlignment()->setWrapText(true);
                $sheet->getStyle('J1:J' . $highestRow)->getAlignment()->setWrapText(true);
            },
        ];
    }
}
