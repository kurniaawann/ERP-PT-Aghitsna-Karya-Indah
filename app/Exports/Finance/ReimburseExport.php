<?php

namespace App\Exports\Finance;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Events\AfterSheet;

class ReimburseExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $reimburses;
    protected $statusFilter;

    /**
     * Constructor untuk menerima data reimburses dan filter status
     */
    public function __construct($reimburses, $statusFilter = null)
    {
        $this->reimburses = $reimburses;
        $this->statusFilter = $statusFilter;
    }

    /**
     * Return collection data untuk export
     */
    public function collection()
    {
        $data = [];
        $no = 1;
        $totalAmount = 0;

        foreach ($this->reimburses as $reimburse) {
            $data[] = [
                'no' => $no++,
                'reimburse_code' => $reimburse->reimburse_code,
                'date' => $reimburse->formatted_date,
                'project_name' => $reimburse->project_name,
                'expense_description' => $reimburse->expense_description,
                'total_amount' => 'Rp ' . number_format($reimburse->total_amount, 0, ',', '.'),
                'due_date' => $reimburse->formatted_due_date,
                'status' => strtoupper($reimburse->status_label),
                'notes' => $reimburse->notes ?? '-',
                'submitted_by' => $reimburse->submitter->name ?? '-',
                'approved_by' => $reimburse->approver->name ?? '-',
            ];

            $totalAmount += $reimburse->total_amount;
        }

        // Baris total
        $data[] = [
            'no' => '',
            'reimburse_code' => '',
            'date' => '',
            'project_name' => '',
            'expense_description' => 'TOTAL',
            'total_amount' => 'Rp ' . number_format($totalAmount, 0, ',', '.'),
            'due_date' => '',
            'status' => '',
            'notes' => '',
            'submitted_by' => '',
            'approved_by' => '',
        ];

        return collect($data);
    }

    /**
     * Headings untuk Excel
     */
    public function headings(): array
    {
        $statusText = 'SEMUA STATUS';
        if ($this->statusFilter) {
            $statusLabels = [
                'draft' => 'DRAFT',
                'approved' => 'DISETUJUI',
                'rejected' => 'DITOLAK',
            ];
            $statusText = $statusLabels[$this->statusFilter] ?? strtoupper($this->statusFilter);
        }

        return [
            ['LAPORAN REIMBURSEMENT'],
            ['STATUS: ' . $statusText],
            [''],
            [
                'NO',
                'KODE',
                'TANGGAL',
                'NAMA PROYEK',
                'KETERANGAN BELANJA',
                'TOTAL',
                'TGL JATUH TEMPO',
                'STATUS',
                'CATATAN',
                'DIAJUKAN OLEH',
                'DISETUJUI OLEH',
            ],
        ];
    }

    /**
     * Styling untuk Excel
     */
    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // Merge title
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Merge subtitle
        $sheet->mergeCells('A2:K2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Empty row
        $sheet->getRowDimension(3)->setRowHeight(5);

        // Header row styling
        $sheet->getStyle('A4:K4')->applyFromArray([
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

        // Set row heights
        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(4)->setRowHeight(40);

        // Data rows border
        $dataEndRow = $highestRow;
        $sheet->getStyle('A5:K' . $dataEndRow)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Align columns
        $sheet->getStyle('A5:A' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // NO
        $sheet->getStyle('B5:B' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // KODE
        $sheet->getStyle('C5:C' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // TANGGAL
        $sheet->getStyle('D5:D' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // NAMA PROYEK
        $sheet->getStyle('E5:E' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // KETERANGAN
        $sheet->getStyle('F5:F' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // TOTAL
        $sheet->getStyle('G5:G' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // TGL TEMPO
        $sheet->getStyle('H5:H' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // STATUS
        $sheet->getStyle('I5:I' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // CATATAN
        $sheet->getStyle('J5:J' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // DIAJUKAN
        $sheet->getStyle('K5:K' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // DISETUJUI

        // Bold untuk baris total (row terakhir)
        $sheet->getStyle('A' . $highestRow . ':K' . $highestRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E7E6E6'],
            ],
        ]);

        return [];
    }

    /**
     * Column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 6,   // NO
            'B' => 12,  // KODE
            'C' => 12,  // TANGGAL
            'D' => 25,  // NAMA PROYEK
            'E' => 40,  // KETERANGAN BELANJA
            'F' => 18,  // TOTAL
            'G' => 14,  // TGL JATUH TEMPO
            'H' => 12,  // STATUS
            'I' => 30,  // CATATAN
            'J' => 20,  // DIAJUKAN OLEH
            'K' => 20,  // DISETUJUI OLEH
        ];
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Reimburse';
    }

    /**
     * Register events
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $highestRow = $event->sheet->getDelegate()->getHighestRow();

                // Auto-wrap text untuk kolom keterangan dan catatan
                $event->sheet->getDelegate()->getStyle('E5:E' . $highestRow)->getAlignment()->setWrapText(true);
                $event->sheet->getDelegate()->getStyle('I5:I' . $highestRow)->getAlignment()->setWrapText(true);
            },
        ];
    }
}
