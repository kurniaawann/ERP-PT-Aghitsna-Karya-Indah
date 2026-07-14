<?php

namespace App\Exports\Finance;

use Illuminate\Support\Collection;
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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export class untuk Rekap Invoice Proyek ke format Excel (XLSX).
 *
 * Menghasilkan file Excel dengan:
 * - Header perusahaan dan judul laporan
 * - Data invoice proyek dengan format rupiah dan label pembayaran bertahap
 * - Baris total ringkasan
 * - Formatting selengkap Excel
 */
class ProyekRecapExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    /**
     * Koleksi invoice proyek.
     *
     * @var \Illuminate\Support\Collection
     */
    protected Collection $invoices;

    /**
     * Ringkasan total rekap.
     *
     * @var object
     */
    protected object $totals;

    /**
     * Judul periode laporan.
     *
     * @var string
     */
    protected string $periodTitle;

    /**
     * Constructor.
     *
     * @param  \Illuminate\Support\Collection  $invoices  Koleksi invoice
     * @param  object  $totals  Ringkasan total
     * @param  string  $periodTitle  Judul periode
     */
    public function __construct(Collection $invoices, object $totals, string $periodTitle)
    {
        $this->invoices = $invoices;
        $this->totals = $totals;
        $this->periodTitle = $periodTitle;
    }

    /**
     * Data collection untuk di-export.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        $data = [];
        $no = 1;

        foreach ($this->invoices as $invoice) {
            $paymentInstallments = $invoice->payment_installments ?? [];
            $paymentStage = '-';

            if (! empty($paymentInstallments) && is_array($paymentInstallments)) {
                $paymentStage = collect($paymentInstallments)
                    ->map(fn ($payment) => $payment['label'] ?? null)
                    ->filter()
                    ->implode(' | ');
            }

            $data[] = [
                'no' => $no++,
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date?->format('d/m/Y') ?? '-',
                'recipient' => $invoice->recipient ?? '-',
                'project_description' => $invoice->project_description ?? '-',
                'total_amount' => 'Rp '.number_format((int) $invoice->getNetAmount(), 0, ',', '.'),
                'paid_amount' => 'Rp '.number_format((int) $invoice->getTotalPaidAmount(), 0, ',', '.'),
                'remaining_amount' => 'Rp '.number_format((int) $invoice->getRemainingAmount(), 0, ',', '.'),
                'payment_stage' => $paymentStage,
                'status' => strtoupper($invoice->payment_status_label),
            ];
        }

        $data[] = [
            'no' => '',
            'invoice_number' => '',
            'invoice_date' => '',
            'recipient' => 'TOTAL',
            'project_description' => 'JUMLAH REKAP INVOICE PROYEK',
            'total_amount' => 'Rp '.number_format((int) $this->totals->total_invoice, 0, ',', '.'),
            'paid_amount' => 'Rp '.number_format((int) $this->totals->total_paid, 0, ',', '.'),
            'remaining_amount' => 'Rp '.number_format((int) $this->totals->total_remaining, 0, ',', '.'),
            'payment_stage' => '',
            'status' => '',
        ];

        return collect($data);
    }

    /**
     * Header kolom Excel.
     *
     * @return array<int, array<int, string>>
     */
    public function headings(): array
    {
        return [
            ['PT. AGHITSNA KARYA INDAH'],
            ['LAPORAN REKAP INVOICE PROYEK'],
            ['PERIODE '.$this->periodTitle],
            [
                'NO',
                'NO INVOICE',
                'TANGGAL',
                'KEPADA',
                'PROYEK',
                'TOTAL INVOICE',
                'SUDAH DIBAYAR',
                'SISA',
                'PEMBAYARAN KE',
                'STATUS',
            ],
        ];
    }

    /**
     * Formatting seluruh worksheet.
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet  $sheet
     * @return array<int, array>
     */
    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        // Header perusahaan
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Judul laporan
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Periode
        $sheet->mergeCells('A3:J3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Header kolom
        $sheet->getStyle('A4:J4')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);

        $sheet->getRowDimension(4)->setRowHeight(32);

        // Data rows — border dan alignment
        $sheet->getStyle('A5:J'.$highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle('A5:A'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B5:B'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C5:C'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D5:D'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('E5:E'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('F5:H'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('I5:I'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('J5:J'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Baris total
        $sheet->getStyle('A'.$highestRow.':J'.$highestRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E7E6E6'],
            ],
        ]);

        return [];
    }

    /**
     * Lebar kolom Excel.
     *
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 14,
            'C' => 12,
            'D' => 22,
            'E' => 30,
            'F' => 18,
            'G' => 18,
            'H' => 18,
            'I' => 26,
            'J' => 14,
        ];
    }

    /**
     * Nama sheet Excel.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Rekap_Proyek';
    }

    /**
     * Event handler setelah sheet dibuat.
     *
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $highestRow = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getDelegate()->getStyle('E5:E'.$highestRow)->getAlignment()->setWrapText(true);
                $event->sheet->getDelegate()->getStyle('I5:I'.$highestRow)->getAlignment()->setWrapText(true);
            },
        ];
    }
}
