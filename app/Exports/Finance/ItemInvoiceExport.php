<?php

namespace App\Exports\Finance;

use App\Models\Finance\InvoiceBarang;
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
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ItemInvoiceExport implements FromCollection, WithEvents, WithTitle, WithColumnWidths
{
    protected $invoice;

    public function __construct($invoiceNumber)
    {
        $this->invoice = InvoiceBarang::where('invoice_number', $invoiceNumber)->firstOrFail();
    }

    public function collection()
    {
        return collect([]);
    }

    public function title(): string
    {
        return 'Invoice_Barang_' . $this->invoice->invoice_number;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,  // No
            'B' => 31, // Keterangan
            'C' => 9,  // Volume
            'D' => 8,  // Satuan
            'E' => 16, // Harga
            'F' => 16, // Jumlah
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $invoice = $this->invoice;

                // Font dasar seluruh dokumen: Times New Roman 12 (revisi klien). Kop surat diperbesar terpisah.
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(12);

                // Pengaturan cetak: A4 portrait, seluruh kolom muat dalam 1 halaman lebar
                $sheet->getPageSetup()
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);

                // Tinggi 1 baris teks Times New Roman 12pt. Semua baris diberi tinggi eksplisit agar posisi
                // gambar (logo/tanda tangan) konsisten di Excel maupun LibreOffice.
                $lineHeight = 16;

                // Teks panjang di sel gabungan di-wrap; tinggi baris diperkirakan dari jumlah karakter
                // (sel merge tidak bisa auto-fit). $charsPerLine = perkiraan karakter per baris A:F.
                $fitWrappedRow = function (int $row, string $text, int $charsPerLine = 90, string $cell = 'A') use ($sheet, $lineHeight) {
                    $sheet->getStyle("{$cell}{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                    $lines = max(1, (int) ceil(mb_strlen($text) / $charsPerLine));
                    $current = $sheet->getRowDimension($row)->getRowHeight();
                    $sheet->getRowDimension($row)->setRowHeight(max($current, $lines * $lineHeight));
                };

                // ═══ KOP SURAT: logo + judul (lebih besar dari isi) ═══
                $sheet->getRowDimension(1)->setRowHeight(62);

                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Company Logo');
                $drawing->setPath(public_path('images/logo.jpeg'));
                $drawing->setHeight(76);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(5);
                $drawing->setOffsetY(3);
                $drawing->setWorksheet($sheet);

                $sheet->mergeCells('B1:F1');
                $sheet->setCellValue('B1', 'INVOICE');
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(22)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('000000'));
                $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                // Kop surat (revisi klien): nama perusahaan 12pt selebar halaman, alamat/telp/email 11pt
                // di kiri & info invoice di kanan
                $sheet->mergeCells('A2:F2');
                $sheet->setCellValue('A2', 'PT. AGHITSNA KARYA INDAH');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension(2)->setRowHeight(20);

                $companyLines = [
                    3 => 'JL. TANAH BARU RAYA PERTIWI RT. 01/05',
                    4 => 'BEJI, DEPOK, JAWA BARAT',
                    5 => 'Telp. 021 - 29034923 - 0812 9596 552',
                    6 => 'Email : Design@aghitsna.id',
                ];
                foreach ($companyLines as $row => $text) {
                    $sheet->mergeCells("A{$row}:C{$row}");
                    $sheet->setCellValue("A{$row}", $text);
                    $sheet->getStyle("A{$row}")->getFont()->setSize(11);
                    $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                }

                $metaLines = [
                    3 => ['No', $invoice->invoice_number],
                    4 => ['Tanggal', Carbon::parse($invoice->invoice_date)->isoFormat('DD MMMM YYYY')],
                    5 => ['Hal', $invoice->regarding ?? '-'],
                ];
                foreach ($metaLines as $row => [$label, $value]) {
                    $sheet->setCellValue("D{$row}", $label);
                    // Nilai "Hal" boleh 2 baris (merge ke baris bawahnya) agar baris alamat tidak ikut meninggi
                    $sheet->mergeCells($row === 5 ? "E5:F6" : "E{$row}:F{$row}");
                    $sheet->setCellValue("E{$row}", ': ' . $value);
                    $sheet->getStyle("D{$row}:F{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                    $sheet->getStyle("E{$row}")->getAlignment()->setWrapText(true);
                }
                $halLines = (int) ceil(mb_strlen(': ' . ($invoice->regarding ?? '-')) / 32);
                if ($halLines > 2) {
                    $sheet->getRowDimension(6)->setRowHeight(($halLines - 1) * $lineHeight);
                }

                // Jarak tepat 1 baris kosong antara Email dan "Kepada Yth" (revisi klien)
                $sheet->getRowDimension(7)->setRowHeight($lineHeight);
                // Kepada Yth (tidak di-bold)
                $currentRow = 8;
                $sheet->setCellValue("A{$currentRow}", 'Kepada Yth :');
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");

                $currentRow++;
                $sheet->setCellValue("A{$currentRow}", $invoice->recipient);
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");

                if (!empty($invoice->proyek)) {
                    $currentRow++;
                    $sheet->setCellValue("A{$currentRow}", $invoice->proyek);
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                }

                $currentRow += 2;
                // Jarak tepat 1 baris kosong sebelum "Dengan ini kami sampaikan" (revisi klien)
                $sheet->getRowDimension($currentRow - 1)->setRowHeight($lineHeight);
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $descriptionText = $invoice->project_description
                    ? 'Dengan ini kami sampaikan invoice untuk proyek ' . $invoice->project_description . ' sebagai berikut :'
                    : 'Dengan ini kami sampaikan invoice sebagai berikut :';
                $sheet->setCellValue("A{$currentRow}", $descriptionText);
                $fitWrappedRow($currentRow, $descriptionText);

                $currentRow += 2;
                $sheet->setCellValue("A{$currentRow}", 'No');
                $sheet->setCellValue("B{$currentRow}", 'Keterangan');
                $sheet->setCellValue("C{$currentRow}", 'Volume');
                $sheet->setCellValue("D{$currentRow}", 'Satuan');
                $sheet->setCellValue("E{$currentRow}", 'Harga');
                $sheet->setCellValue("F{$currentRow}", 'Jumlah');

                $sheet->getStyle("A{$currentRow}:F{$currentRow}")->applyFromArray([
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

                $items = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
                $totalAmount = 0;
                $itemStartRow = $currentRow + 1;

                foreach ($items as $index => $item) {
                    $currentRow++;
                    $quantity = (int) ($item['quantity'] ?? 0);
                    $sellingPrice = (int) ($item['selling_price'] ?? 0);
                    $jumlah = $sellingPrice * $quantity;
                    $totalAmount += $jumlah;

                    $sheet->setCellValue("A{$currentRow}", $index + 1);
                    $sheet->setCellValue("B{$currentRow}", $item['name_item'] ?? '-');
                    $fitWrappedRow($currentRow, (string) ($item['name_item'] ?? '-'), 32, 'B');
                    $sheet->setCellValue("C{$currentRow}", $quantity);
                    $sheet->setCellValue("D{$currentRow}", $item['satuan'] ?? '');
                    $sheet->setCellValue("E{$currentRow}", format_rupiah($sellingPrice));
                    $sheet->setCellValue("F{$currentRow}", format_rupiah($jumlah));

                    // Volume, Satuan, Harga, Jumlah rata tengah; keterangan di-wrap bila panjang
                    $sheet->getStyle("A{$currentRow}:F{$currentRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
                $sheet->setCellValue("E{$currentRow}", 'Jumlah');
                $sheet->setCellValue("F{$currentRow}", format_rupiah($totalAmount));

                $sheet->getStyle("A{$currentRow}:D{$currentRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFFFF']
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_NONE]
                    ]
                ]);
                $sheet->getStyle("E{$currentRow}:F{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00']
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ],
                    // Label & nominal ringkasan rata tengah
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER
                    ]
                ]);

                // Terbilang di-bold
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $terbilangText = 'Terbilang : ' . ucwords(terbilang($totalAmount)) . ' rupiah';
                $sheet->setCellValue("A{$currentRow}", $terbilangText);
                $sheet->getStyle("A{$currentRow}")->getFont()->setItalic(true)->setBold(true);
                $fitWrappedRow($currentRow, $terbilangText, 80);

                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Pembayaran dapat ditransfer melalui nomor rekening');

                $selectedAccountIds = is_string($invoice->selected_payment_accounts)
                    ? json_decode($invoice->selected_payment_accounts, true)
                    : ($invoice->selected_payment_accounts ?? []);

                if (!empty($selectedAccountIds)) {
                    $paymentAccounts = PaymentAccount::whereIn('id', $selectedAccountIds)
                        ->orderBy('id')
                        ->get();
                } else {
                    $paymentAccounts = PaymentAccount::active()->get();
                }

                $sanitizeForExcel = function ($value) {
                    if (is_string($value) && preg_match('/^[=+\-@]/', $value)) {
                        return "'" . $value;
                    }
                    return $value;
                };

                foreach ($paymentAccounts as $account) {
                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $bankName = $sanitizeForExcel($account->bank_name);
                    $accountNumber = $sanitizeForExcel($account->account_number);
                    $accountHolder = $sanitizeForExcel($account->account_holder);
                    // Nama bank, nomor & pemilik rekening tidak di-bold
                    $sheet->setCellValue("A{$currentRow}", "{$bankName} / No : {$accountNumber} a/n {$accountHolder}");
                }

                // 2 baris kosong di atas kalimat penutup (revisi klien)
                $currentRow += 3;
                $sheet->getRowDimension($currentRow - 2)->setRowHeight($lineHeight);
                $sheet->getRowDimension($currentRow - 1)->setRowHeight($lineHeight);
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $closingText = 'Demikian invoice ini kami sampaikan atas perhatian dan kerja samanya kami ucapkan terimakasih.';
                $sheet->setCellValue("A{$currentRow}", $closingText);
                $fitWrappedRow($currentRow, $closingText, 100);

                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Hormat Kami,');

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                // Nama PT & penandatangan tidak di-bold
                $sheet->setCellValue("A{$currentRow}", 'PT. AGHITSNA KARYA INDAH');

                if ($invoice->signedBy?->signature_image) {
                    $signaturePath = storage_path('app/public/' . $invoice->signedBy->signature_image);
                    if (is_file($signaturePath)) {
                        // Gambar tanda tangan tepat di bawah nama PT
                        $currentRow++;
                        $signatureDrawing = new Drawing();
                        $signatureDrawing->setName('Tanda Tangan');
                        $signatureDrawing->setDescription('Tanda Tangan ' . $invoice->signedBy->name);
                        $signatureDrawing->setPath($signaturePath);
                        $signatureDrawing->setHeight(55);
                        $signatureDrawing->setCoordinates("A{$currentRow}");
                        $signatureDrawing->setOffsetX(10);
                        $signatureDrawing->setOffsetY(2);
                        $signatureDrawing->setWorksheet($sheet);

                        $sheet->getRowDimension($currentRow)->setRowHeight(46);
                        $currentRow++;
                    } else {
                        // Ruang tanda tangan basah
                        $sheet->getRowDimension($currentRow + 1)->setRowHeight(48);
                        $currentRow += 2;
                    }
                } else {
                    // Ruang tanda tangan basah
                    $sheet->getRowDimension($currentRow + 1)->setRowHeight(48);
                    $currentRow += 2;
                }

                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", $invoice->signedBy?->name ?? '');

                if ($invoice->division) {
                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", $invoice->division->name);
                }

                // Baris tanpa tinggi khusus: baris kosong jadi spasi tipis, baris berisi setinggi 1 baris 12pt
                for ($row = 1; $row <= $currentRow; $row++) {
                    if ($sheet->getRowDimension($row)->getRowHeight() >= 0) {
                        continue;
                    }
                    $isEmptyRow = true;
                    foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
                        if ($sheet->cellExists("{$col}{$row}") && (string) $sheet->getCell("{$col}{$row}")->getValue() !== '') {
                            $isEmptyRow = false;
                            break;
                        }
                    }
                    $sheet->getRowDimension($row)->setRowHeight($isEmptyRow ? 8 : $lineHeight);
                }
            },
        ];
    }
}
