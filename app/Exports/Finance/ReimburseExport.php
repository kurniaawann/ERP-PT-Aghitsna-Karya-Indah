<?php

namespace App\Exports\Finance;

use Illuminate\Support\Collection;
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
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Export class untuk data Reimbursement ke Excel.
 *
 * Menghasilkan file Excel dengan:
 * - Header "LAPORAN REIMBURSEMENT"
 * - Sub-header status: rasio persetujuan (mis. "Disetujui 3 dari 4 yang diajukan")
 * - Tabel data reimburse
 * - Baris total di akhir
 * - Styling: border, warna header biru, bold total
 */
class ReimburseExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    /**
     * Data reimburse yang akan di-export.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $reimburses;

    /**
     * Filter status yang diterapkan.
     *
     * @var string|null
     */
    protected $statusFilter;

    /**
     * Teks status untuk sub-header (dari ReimburseService::buildStatusText).
     *
     * @var string|null
     */
    protected $statusText;

    /**
     * Constructor untuk menerima data reimburses dan filter status.
     *
     * @param  \Illuminate\Support\Collection $reimburses   Data reimburse
     * @param  string|null                    $statusFilter Filter status (draft/approved/rejected)
     * @param  string|null                    $statusText   Teks status siap tampil (rasio persetujuan)
     */
    public function __construct(Collection $reimburses, ?string $statusFilter = null, ?string $statusText = null)
    {
        $this->reimburses = $reimburses;
        $this->statusFilter = $statusFilter;
        $this->statusText = $statusText;
    }

    /**
     * Return collection data untuk export.
     *
     * Setiap baris berisi: no, kode, tanggal, nama proyek, keterangan belanja,
     * total, status, tanggal perubahan status, catatan (Tgl Jatuh Tempo
     * dihapus — revisi klien). Baris terakhir adalah total.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
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
                'total_amount' => format_rupiah($reimburse->total_amount),
                'status' => strtoupper($reimburse->status_label),
                'status_changed_at' => $reimburse->formatted_status_changed_at,
                'notes' => $reimburse->notes ?? '-',
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
            'total_amount' => format_rupiah($totalAmount),
            'status' => '',
            'status_changed_at' => '',
            'notes' => '',
        ];

        return collect($data);
    }

    /**
     * Headings untuk Excel.
     *
     * Mengembalikan 4 baris: judul, sub-judul status, baris kosong, header kolom.
     *
     * @return array<int, array<int, string>>
     */
    public function headings(): array
    {
        $statusText = 'SEMUA STATUS';
        if ($this->statusText) {
            $statusText = $this->statusText;
        } elseif ($this->statusFilter) {
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
                'STATUS',
                'TGL PERUBAHAN STATUS',
                'CATATAN',
            ],
        ];
    }

    /**
     * Styling untuk Excel.
     *
     * Mengatur: merge sel judul, warna header, border, alignment, bold total.
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        // Merge title
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Merge subtitle
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Empty row
        $sheet->getRowDimension(3)->setRowHeight(5);

        // Header row styling
        $sheet->getStyle('A4:I4')->applyFromArray([
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
        $sheet->getStyle('A5:I' . $dataEndRow)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Alignment per kolom
        $sheet->getStyle('A5:A' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B5:B' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C5:C' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D5:D' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('E5:E' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('F5:F' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G5:G' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H5:H' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I5:I' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Bold + background untuk baris total (row terakhir)
        $sheet->getStyle('A' . $highestRow . ':I' . $highestRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E7E6E6'],
            ],
        ]);

        return [];
    }

    /**
     * Column widths.
     *
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 12,
            'C' => 12,
            'D' => 25,
            'E' => 40,
            'F' => 18,
            'G' => 12,
            'H' => 18,
            'I' => 30,
        ];
    }

    /**
     * Sheet title.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Reimburse';
    }

    /**
     * Register events.
     *
     * Mengatur auto-wrap text untuk kolom keterangan dan catatan, serta
     * setup cetak (A4 landscape, muat 1 halaman lebar).
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $highestRow = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getDelegate()->getStyle('E5:E' . $highestRow)->getAlignment()->setWrapText(true);
                $event->sheet->getDelegate()->getStyle('I5:I' . $highestRow)->getAlignment()->setWrapText(true);

                // Setup cetak: A4 landscape, muat 1 halaman lebar, header tabel berulang
                $event->sheet->getDelegate()->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $event->sheet->getDelegate()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(4, 4);
            },
        ];
    }
}
