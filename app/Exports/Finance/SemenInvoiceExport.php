<?php

namespace App\Exports\Finance;

use App\Models\Finance\InvoiceSemen;
use App\Models\Finance\PaymentAccount;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SemenInvoiceExport implements FromCollection, WithEvents, WithTitle, WithColumnWidths
{
    protected $invoice;

    public function __construct($invoiceNumber)
    {
        $this->invoice = InvoiceSemen::where('invoice_number', $invoiceNumber)->firstOrFail();
    }

    public function collection()
    {
        return collect([]);
    }

    public function title(): string
    {
        return 'Invoice_Semen_' . $this->invoice->invoice_number;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12, // No. / Label Tanggal
            'B' => 16, // Tanggal / Titik dua
            'C' => 45, // Nama Barang / Nilai Tanggal & Pembayaran
            'D' => 12, // QTY
            'E' => 22, // Jumlah
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $invoice = $this->invoice;

                // Set Font Default ke Times New Roman
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(9.5);

                // 1. JUDUL HEADER INVOICE (HIJAU SAGE)
                $sheet->mergeCells('A1:E1');
                $sheet->setCellValue('A1', 'INVOICE');
                $sheet->getStyle('A1:E1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'A2C48C'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(22);

                // 2. INFORMASI TANGGAL & TOTAL PEMBAYARAN
                // Baris Tanggal
                $sheet->setCellValue('A2', 'Tanggal');
                $sheet->setCellValue('B2', ':');
                $sheet->mergeCells('C2:E2');
                $sheet->setCellValue('C2', Carbon::parse($invoice->invoice_date)->isoFormat('dddd, D MMMM YYYY'));

                // Baris Total Pembayaran
                $sheet->setCellValue('A3', 'Total Pembayaran');
                $sheet->setCellValue('B3', ':');
                $sheet->mergeCells('C3:E3');
                $sheet->setCellValue('C3', 'Rp. ' . number_format($invoice->total_amount ?? 0, 0, ',', '.'));

                // Styling Info Atas (A2:E3)
                $sheet->getStyle('B2:B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A2:E3')->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                // 3. BARIS KOSONG PEMISAH DENGAN GARIS TERHUBUNG LURUS
                $sheet->getStyle('A4:E4')->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(10);

                $currentRow = 5;
                $grandTotal = 0;
                $projects = $this->getProjects();
                $totalProjects = count($projects);

                // 4. LOOPING PROYEK & BARANG
                foreach ($projects as $project) {
                    $items = $project['items'] ?? [];
                    $subtotal = 0;
                    foreach ($items as $item) {
                        $subtotal += (int) ($item['jumlah'] ?? 0);
                    }
                    $grandTotal += $subtotal;

                    // Header Kolom Tabel Barang (Hijau Sage)
                    $headerRow = $currentRow;
                    $sheet->setCellValue("A{$headerRow}", 'No.');
                    $sheet->setCellValue("B{$headerRow}", 'Tanggal');
                    $sheet->setCellValue("C{$headerRow}", 'Nama Barang');
                    $sheet->setCellValue("D{$headerRow}", 'QTY');
                    $sheet->setCellValue("E{$headerRow}", 'Jumlah');

                    $sheet->getStyle("A{$headerRow}:E{$headerRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'A2C48C'],
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    // Baris Nama Proyek (Kuning)
                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:C{$currentRow}");
                    $projectTitle = 'Proyek ' . ($project['nama_proyek'] ?? '-');
                    if (!empty($project['pengurus_proyek'])) {
                        $projectTitle .= ' (' . $project['pengurus_proyek'] . ')';
                    }
                    $sheet->setCellValue("A{$currentRow}", $projectTitle);

                    $sheet->getStyle("A{$currentRow}:E{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFFF00'],
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);

                    // Loop Item Barang
                    $itemStartRow = $currentRow + 1;
                    foreach ($items as $index => $item) {
                        $currentRow++;
                        $qty = (int) ($item['qty'] ?? 0);
                        $jumlah = (int) ($item['jumlah'] ?? 0);

                        $sheet->setCellValue("A{$currentRow}", ($item['no'] ?? ($index + 1)) . '.');
                        $sheet->setCellValue("B{$currentRow}", $item['tanggal'] ? Carbon::parse($item['tanggal'])->format('d M Y') : '-');
                        $sheet->setCellValue("C{$currentRow}", $item['nama_barang'] ?? 'SEMEN');
                        $sheet->setCellValue("D{$currentRow}", $qty . ' Zak');
                        $sheet->setCellValue("E{$currentRow}", 'Rp ' . number_format($jumlah, 0, ',', '.'));

                        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }

                    $itemEndRow = $currentRow;
                    $sheet->getStyle("A{$itemStartRow}:E{$itemEndRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);

                    // Total 1 Bon Proyek (Kuning)
                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:C{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", 'TOTAL 1 BON PROYEK ' . strtoupper($project['nama_proyek'] ?? ''));
                    $sheet->setCellValue("E{$currentRow}", 'Rp ' . number_format($subtotal, 0, ',', '.'));

                    $sheet->getStyle("A{$currentRow}:E{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFFF00'],
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);
                    $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Baris Rekening Bank
                    $account = !empty($project['payment_account_id'])
                        ? PaymentAccount::find($project['payment_account_id'])
                        : null;

                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:C{$currentRow}");
                    
                    $bankName = $this->sanitizeExcel($account->bank_name ?? 'BCA');
                    $accountNumber = $this->sanitizeExcel($account->account_number ?? 'Nomor rekening');
                    $accountHolder = strtoupper($this->sanitizeExcel($account->account_holder ?? 'PEMILIK'));

                    $sheet->setCellValue("A{$currentRow}", "Bank {$bankName} : {$accountNumber} / A/N {$accountHolder}");

                    $sheet->getStyle("A{$currentRow}:E{$currentRow}")->applyFromArray([
                        'font' => ['italic' => true, 'size' => 8.5],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);

                    $currentRow++; // Jarak antar proyek
                }

                // 5. CATATAN NB
                $noteText = !empty($invoice->note) 
                    ? 'NB : ' . $invoice->note 
                    : '';

                if (!empty($noteText)) {
                    $sheet->setCellValue("A{$currentRow}", $noteText);
                    $sheet->getStyle("A{$currentRow}")->getFont()->setItalic(true)->setBold(true)->setSize(8.5);
                    $currentRow += 2;
                } else {
                    $currentRow++;
                }

                // 6. GRAND TOTAL (DOUBLE UNDERLINE)
                $sheet->setCellValue("D{$currentRow}", "TOTAL {$totalProjects} INVOICE:");
                $sheet->setCellValue("E{$currentRow}", 'Rp ' . number_format($grandTotal, 0, ',', '.'));

                $sheet->getStyle("D{$currentRow}:E{$currentRow}")->getFont()->setBold(true);
                $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Border Top Single + Border Bottom Double pada angka Grand Total
                $sheet->getStyle("E{$currentRow}")->applyFromArray([
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_THIN],
                        'bottom' => ['borderStyle' => Border::BORDER_DOUBLE],
                    ],
                ]);

                // 7. TANDA TANGAN
                $currentRow += 3;
                $signedBy = $invoice->signedBy;

                $sheet->setCellValue("E{$currentRow}", $signedBy?->position ?? 'Manager Divisi Hollo');
                $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $currentRow += 4; // Space untuk tanda tangan
                $sheet->setCellValue("E{$currentRow}", $signedBy?->name ?? '................');
                $sheet->getStyle("E{$currentRow}")->getFont()->setUnderline(true);
                $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }

    protected function getProjects(): array
    {
        $projects = $this->invoice->projects;

        return is_string($projects) ? json_decode($projects, true) : $projects ?? [];
    }

    protected function sanitizeExcel($value): string
    {
        $value = (string) $value;

        if (preg_match('/^[=+\-@]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}