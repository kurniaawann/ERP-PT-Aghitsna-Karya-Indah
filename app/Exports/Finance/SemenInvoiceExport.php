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

/**
 * Export Invoice Semen per nomor invoice ke Excel.
 *
 * Menyusun header perusahaan, data invoice, lalu blok per proyek
 * dengan tabel data semen (No, Tanggal, Nama Barang, Qty, Harga, Jumlah),
 * subtotal tiap proyek, dan jumlah akhir.
 */
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
            'A' => 6,
            'B' => 14,
            'C' => 32,
            'D' => 9,
            'E' => 16,
            'F' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $invoice = $this->invoice;

                $sheet->getRowDimension(1)->setRowHeight(60);
                $sheet->getRowDimension(2)->setRowHeight(15);

                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Company Logo');
                $drawing->setPath(public_path('images/logo.jpeg'));
                $drawing->setHeight(55);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(5);
                $drawing->setOffsetY(3);
                $drawing->setWorksheet($sheet);

                $sheet->mergeCells('B1:F1');
                $sheet->setCellValue('B1', 'INVOICE');
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(22);
                $sheet->getStyle('B1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->mergeCells('A2:D2');
                $sheet->setCellValue('A2', 'PT. AGHITSNA KARYA INDAH');
                $sheet->getStyle('A2')->getFont()->setBold(true);

                $sheet->mergeCells('A3:D3');
                $sheet->setCellValue('A3', 'JL. TANAH BARU RAYA PERTIWI RT. 01/05 BEJI, DEPOK, JAWA BARAT');

                $sheet->mergeCells('A4:D4');
                $sheet->setCellValue('A4', 'Telp. 021 - 29034923 - 0812 9596 552');

                $sheet->mergeCells('A5:D5');
                $sheet->setCellValue('A5', 'Email : Design@aghitsna.id');

                $sheet->setCellValue('E2', 'No');
                $sheet->setCellValue('F2', ': ' . $invoice->invoice_number);
                $sheet->setCellValue('E3', 'Tanggal');
                $sheet->setCellValue('F3', ': ' . Carbon::parse($invoice->invoice_date)->isoFormat('DD MMMM YYYY'));
                $sheet->setCellValue('E4', 'Hal');
                $sheet->setCellValue('F4', ': Penagihan Semen');

                $currentRow = 7;

                foreach ($this->getProjects() as $project) {
                    $items = $project['items'] ?? [];
                    if (empty($items)) {
                        continue;
                    }

                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", 'Proyek: ' . ($project['nama_proyek'] ?? '-'));
                    $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true)->setSize(12);
                    $currentRow++;

                    $headerRow = $currentRow;
                    $sheet->setCellValue("A{$headerRow}", 'No');
                    $sheet->setCellValue("B{$headerRow}", 'Tanggal');
                    $sheet->setCellValue("C{$headerRow}", 'Nama Barang');
                    $sheet->setCellValue("D{$headerRow}", 'Qty');
                    $sheet->setCellValue("E{$headerRow}", 'Harga');
                    $sheet->setCellValue("F{$headerRow}", 'Jumlah');

                    $sheet->getStyle("A{$headerRow}:F{$headerRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F0F0F0'],
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    $subtotal = 0;
                    $itemStartRow = $currentRow + 1;

                    foreach ($items as $item) {
                        $currentRow++;
                        $qty = (int) ($item['qty'] ?? 0);
                        $harga = (int) ($item['harga'] ?? 0);
                        $jumlah = $qty * $harga;
                        $subtotal += $jumlah;

                        $sheet->setCellValue("A{$currentRow}", $item['no'] ?? ($currentRow - $itemStartRow + 1));
                        $sheet->setCellValue("B{$currentRow}", $item['tanggal']
                            ? Carbon::parse($item['tanggal'])->format('d-m-Y')
                            : '');
                        $sheet->setCellValue("C{$currentRow}", $item['nama_barang'] ?? 'SEMEN');
                        $sheet->setCellValue("D{$currentRow}", $qty);
                        $sheet->setCellValue("E{$currentRow}", 'Rp ' . number_format($harga, 0, ',', '.'));
                        $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($jumlah, 0, ',', '.'));

                        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }

                    $itemEndRow = $currentRow;
                    $sheet->getStyle("A{$itemStartRow}:F{$itemEndRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);

                    $currentRow++;
                    $sheet->setCellValue("A{$currentRow}", '');
                    $sheet->setCellValue("B{$currentRow}", '');
                    $sheet->setCellValue("C{$currentRow}", '');
                    $sheet->setCellValue("D{$currentRow}", '');
                    $sheet->setCellValue("E{$currentRow}", 'Subtotal');
                    $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($subtotal, 0, ',', '.'));

                    $sheet->getStyle("A{$currentRow}:F{$currentRow}")->getFont()->setBold(true);
                    $sheet->getStyle("E{$currentRow}:F{$currentRow}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F5F5F5'],
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);
                    $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    $account = $project['payment_account_id']
                        ? PaymentAccount::find($project['payment_account_id'])
                        : null;

                    if ($account) {
                        $currentRow++;
                        $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                        $bankName = $this->sanitizeExcel($account->bank_name);
                        $accountNumber = $this->sanitizeExcel($account->account_number);
                        $accountHolder = $this->sanitizeExcel($account->account_holder);
                        $sheet->setCellValue("A{$currentRow}",
                            "Pembayaran proyek ini melalui: {$bankName} / No : {$accountNumber} a/n {$accountHolder}");
                        $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
                    }

                    $currentRow += 2;
                }

                $sheet->setCellValue("A{$currentRow}", '');
                $sheet->setCellValue("B{$currentRow}", '');
                $sheet->setCellValue("C{$currentRow}", '');
                $sheet->setCellValue("D{$currentRow}", '');
                $sheet->setCellValue("E{$currentRow}", 'Jumlah');
                $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($invoice->getNetAmount(), 0, ',', '.'));

                $sheet->getStyle("A{$currentRow}:F{$currentRow}")->getFont()->setBold(true);
                $sheet->getStyle("D{$currentRow}:F{$currentRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);
                $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Terbilang : ' . ucwords(terbilang($invoice->getNetAmount())) . ' rupiah');
                $sheet->getStyle("A{$currentRow}")->getFont()->setItalic(true);

                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Demikian invoice semen ini kami sampaikan atas perhatian dan kerjasamanya kami ucapkan terima kasih.');
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true);

                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Hormat Kami,');

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'PT. AGHITSNA KARYA INDAH');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

                $currentRow += 3;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Direktur');
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