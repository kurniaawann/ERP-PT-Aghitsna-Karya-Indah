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
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

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
            'A' => 17, // No. / Label Tanggal & Total Pembayaran
            'B' => 16, // Tanggal / Titik dua
            'C' => 40, // Nama Barang / Nilai Tanggal & Pembayaran
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
                $sheet->setCellValue('C2', Carbon::parse($invoice->invoice_date)->locale('id')->isoFormat('dddd, D MMMM YYYY'));

                // Baris Total Pembayaran
                $sheet->setCellValue('A3', 'Total Pembayaran');
                $sheet->setCellValue('B3', ':');
                $sheet->mergeCells('C3:E3');
                $sheet->setCellValue('C3', format_rupiah($invoice->total_amount ?? 0));

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

                // Fallback rekening untuk proyek yang tersimpan tanpa rekening (invoice lama):
                // pakai rekening aktif, sama seperti invoice lain.
                $fallbackAccounts = PaymentAccount::active()->get();

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
                        $sheet->setCellValue("B{$currentRow}", $item['tanggal'] ? Carbon::parse($item['tanggal'])->translatedFormat('d M Y') : '-');
                        $sheet->setCellValue("C{$currentRow}", $item['nama_barang'] ?? 'SEMEN');
                        $sheet->setCellValue("D{$currentRow}", $qty . ' Zak');
                        $sheet->setCellValue("E{$currentRow}", format_rupiah($jumlah));

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
                    $sheet->setCellValue("E{$currentRow}", format_rupiah($subtotal));

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

                    // Baris Rekening Bank (satu baris per rekening)
                    $account = !empty($project['payment_account_id'])
                        ? PaymentAccount::find($project['payment_account_id'])
                        : null;
                    $accounts = $account ? collect([$account]) : $fallbackAccounts;

                    $bankLines = $accounts->map(function ($acc) {
                        // Prefix "Bank" hanya bila nama bank belum diawali "Bank" (hindari "Bank Bank Mandiri")
                        $bankName = $this->sanitizeExcel($acc->bank_name);
                        $bankLabel = str_starts_with(strtolower($bankName), 'bank') ? $bankName : "Bank {$bankName}";
                        $accountNumber = $this->sanitizeExcel($acc->account_number);
                        $accountHolder = strtoupper($this->sanitizeExcel($acc->account_holder));

                        return "{$bankLabel} : {$accountNumber} / A/N {$accountHolder}";
                    });

                    if ($bankLines->isEmpty()) {
                        $bankLines = collect(['Rekening pembayaran belum diatur']);
                    }

                    foreach ($bankLines as $bankLine) {
                        $currentRow++;
                        $sheet->mergeCells("A{$currentRow}:C{$currentRow}");
                        $sheet->setCellValue("A{$currentRow}", $bankLine);

                        $sheet->getStyle("A{$currentRow}:E{$currentRow}")->applyFromArray([
                            'font' => ['italic' => true, 'size' => 8.5],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                            ],
                        ]);
                    }

                    $currentRow++; // Jarak antar proyek
                }

                // Tinggikan baris info & tabel agar teks tidak menempel garis
                // (baris jarak antar proyek dibiarkan tipis).
                foreach (range(2, $currentRow - 1) as $row) {
                    if ($row === 4) {
                        continue;
                    }
                    $hasValue = trim((string) $sheet->getCell("A{$row}")->getValue()) !== ''
                        || trim((string) $sheet->getCell("E{$row}")->getValue()) !== '';
                    if ($hasValue) {
                        $sheet->getRowDimension($row)->setRowHeight(20);
                    }
                }
                $sheet->getStyle("A2:E" . ($currentRow - 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

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
                $sheet->setCellValue("E{$currentRow}", format_rupiah($grandTotal));

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

                // 8. KOP SURAT (revisi klien): logo + "PT. AGHITSNA KARYA INDAH" 12pt (rata tengah)
                // di atas judul INVOICE, selaras PDF. Disisipkan paling akhir (3 baris di atas) agar
                // penomoran baris isi di atas tidak berubah; merge, style & tinggi baris ikut bergeser.
                $sheet->insertNewRowBefore(1, 3);

                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Company Logo');
                $drawing->setPath(public_path('images/logo.jpeg'));
                $drawing->setHeight(44);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(4);
                $drawing->setOffsetY(2);
                $drawing->setWorksheet($sheet);

                $sheet->mergeCells('B1:E1');
                $sheet->setCellValue('B1', 'PT. AGHITSNA KARYA INDAH');
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_BOTTOM);
                $sheet->getRowDimension(1)->setRowHeight(22);

                $sheet->mergeCells('B2:E2');
                $sheet->setCellValue('B2', 'General Contruction - Engineering');
                $sheet->getStyle('B2')->getFont()->setSize(11);
                $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getRowDimension(2)->setRowHeight(18);

                // Baris 3: jarak tipis antara kop dan judul INVOICE
                $sheet->getRowDimension(3)->setRowHeight(8);
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