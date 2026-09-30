<?php

namespace App\Exports\Finance;

use App\Models\Finance\InvoiceProyek;
use App\Models\Finance\PaymentAccount;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ProyekInvoiceAdminExport implements FromCollection, WithEvents, WithTitle, WithColumnWidths
{
    protected $invoice;

    public function __construct($invoiceNumber)
    {
        $this->invoice = InvoiceProyek::where('invoice_number', $invoiceNumber)->firstOrFail();
    }

    public function collection()
    {
        return collect([]);
    }

    public function title(): string
    {
        return 'Invoice_Admin_' . $this->invoice->invoice_number;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,   // No
            'B' => 31,  // Deskripsi
            'C' => 16,  // Harga
            'D' => 9,   // %
            'E' => 12,  // Jumlah
            'F' => 13,  // Jumlah (lanjutan)
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
                // gambar (logo/tanda tangan/stempel) konsisten di Excel maupun LibreOffice.
                $lineHeight = 16;

                // Teks panjang di sel gabungan di-wrap; tinggi baris diperkirakan dari jumlah karakter
                // (sel merge tidak bisa auto-fit). $charsPerLine = perkiraan karakter per baris A:F.
                $fitWrappedRow = function (int $row, string $text, int $charsPerLine = 90, string $cell = 'A') use ($sheet, $lineHeight) {
                    $sheet->getStyle("{$cell}{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                    $lines = max(1, (int) ceil(mb_strlen($text) / $charsPerLine));
                    $current = $sheet->getRowDimension($row)->getRowHeight();
                    $sheet->getRowDimension($row)->setRowHeight(max($current, $lines * $lineHeight));
                };

                // Helper: trim trailing zero pada angka persentase (format PDF).
                $trimNumber = function ($value) {
                    return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
                };

                // Gaya baris ringkasan keuangan (Jumlah/Discount/PPN/DP/Sisa) — sama dengan PDF admin:
                // label di bawah kolom Harga + %, nominal di bawah kolom Jumlah.
                $applySummaryRow = function ($row, $label, $amount, $amountRaw = null) use ($sheet, $trimNumber) {
                    $sheet->mergeCells("A{$row}:B{$row}");
                    $sheet->mergeCells("C{$row}:D{$row}");
                    $sheet->mergeCells("E{$row}:F{$row}");
                    $sheet->setCellValue("A{$row}", '');
                    $sheet->setCellValue("C{$row}", $label);
                    $sheet->setCellValue("E{$row}", $amountRaw ?? 'Rp ' . number_format((float) $amount, 0, ',', '.'));

                    $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                        ],
                    ]);
                    $sheet->getStyle("C{$row}:F{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'A6A6A6'],
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);
                    // Label & nominal ringkasan rata tengah
                    $sheet->getStyle("C{$row}:F{$row}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                };

                // ═══ HEADER (logo + judul INVOICE, lebih besar dari isi) ═══════════════
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

                // Garis pemisah header (hitam tebal)
                $sheet->getStyle('A2:F2')->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_MEDIUM)
                    ->getColor()->setARGB('FF000000');
                $sheet->getRowDimension(2)->setRowHeight(6);

                // ═══ INFO PERUSAHAAN & META SURAT ════════════════════════════════════
                $invoiceDate = Carbon::parse($invoice->invoice_date)->isoFormat('D MMMM YYYY');

                // Nama perusahaan 16pt (kop), alamat 12pt
                $sheet->mergeCells('A3:C3');
                $sheet->setCellValue('A3', 'PT. AGHITSNA KARYA INDAH');
                $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A3')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getRowDimension(3)->setRowHeight(24);

                $companyLines = [
                    4 => 'JL. TANAH BARU RAYA PERTIWI RT.01/05',
                    5 => 'BEJI. DEPOK.JAWA BARAT',
                    6 => 'Telp. 021-29034923 – 0812.9596.552',
                    7 => 'Email : Design@aghitsna.id',
                ];
                foreach ($companyLines as $row => $text) {
                    $sheet->mergeCells("A{$row}:C{$row}");
                    $sheet->setCellValue("A{$row}", $text);
                    $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                }

                $metaLines = [
                    3 => ['No', $invoice->invoice_number],
                    4 => ['Tanggal', $invoiceDate],
                    5 => ['Hal', $invoice->regarding ?? 'Penagihan Pembayaran'],
                ];
                foreach ($metaLines as $row => [$label, $value]) {
                    $sheet->setCellValue("D{$row}", $label);
                    // Nilai "Hal" boleh 2 baris (merge ke baris bawahnya) agar baris alamat tidak ikut meninggi
                    $sheet->mergeCells($row === 5 ? "E5:F6" : "E{$row}:F{$row}");
                    $sheet->setCellValue("E{$row}", ': ' . $value);
                    $sheet->getStyle("D{$row}:F{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                    $sheet->getStyle("E{$row}")->getAlignment()->setWrapText(true);
                }
                $halLines = (int) ceil(mb_strlen(': ' . ($invoice->regarding ?? 'Penagihan Pembayaran')) / 25);
                if ($halLines > 2) {
                    $sheet->getRowDimension(6)->setRowHeight(($halLines - 1) * $lineHeight);
                }

                // ═══ PENERIMA SURAT ═════════════════════════════════════════════════
                $currentRow = 9;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                // Kepada Yth tidak di-bold
                $sheet->setCellValue("A{$currentRow}", 'Kepada Yth :');

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Bpk. ' . $invoice->recipient);

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Di Tempat');

                // ═══ PARAGRAF PEMBUKA ════════════════════════════════════════════════
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Dengan Hormat,');

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $location = $invoice->location ?? $invoice->quotation?->location ?? '-';
                $openingText = $invoice->project_description
                    ? 'Dengan ini kami sampaikan Invoice untuk pekerjaan ' . $invoice->project_description . ', ' . $location . ', sebagai berikut :'
                    : (($invoice->location ?? $invoice->quotation?->location)
                        ? 'Dengan ini kami sampaikan invoice sebagai berikut : Lokasi ' . ($invoice->location ?? $invoice->quotation?->location)
                        : 'Dengan ini kami sampaikan invoice sebagai berikut :');
                $sheet->setCellValue("A{$currentRow}", $openingText);
                $fitWrappedRow($currentRow, $openingText);

                // ═══ TABEL ITEMS ═════════════════════════════════════════════════════
                $currentRow += 2;
                $tableHeaderRow = $currentRow;

                $sheet->setCellValue("A{$currentRow}", 'No');
                $sheet->setCellValue("B{$currentRow}", 'Deskripsi');
                $sheet->setCellValue("C{$currentRow}", 'Harga');
                $sheet->setCellValue("D{$currentRow}", '%');
                $sheet->setCellValue("E{$currentRow}", 'Jumlah');
                $sheet->mergeCells("E{$currentRow}:F{$currentRow}");

                $sheet->getStyle("A{$currentRow}:F{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'A6A6A6'],
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
                    $harga = (float) ($item['harga'] ?? 0);
                    $persentase = (float) ($item['persentase'] ?? 0);
                    $jumlah = $harga * ($persentase / 100);
                    $totalAmount += $jumlah;

                    $sheet->setCellValueExplicit("A{$currentRow}", ($index + 1) . '.', DataType::TYPE_STRING);
                    $sheet->setCellValue("B{$currentRow}", '   ' . ($item['deskripsi'] ?? ''));
                    $fitWrappedRow($currentRow, '   ' . ($item['deskripsi'] ?? ''), 32, 'B');
                    $sheet->setCellValue("C{$currentRow}", 'Rp ' . number_format($harga, 0, ',', '.'));
                    $sheet->setCellValue("D{$currentRow}", number_format($persentase, 2, ',', '.') . '%');
                    $sheet->setCellValue("E{$currentRow}", 'Rp ' . number_format($jumlah, 0, ',', '.'));
                    $sheet->mergeCells("E{$currentRow}:F{$currentRow}");

                    // Harga, %, Jumlah rata tengah
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

                // ═══ RINGKASAN KEUANGAN ══════════════════════════════════════════════
                $ppnAmount = $invoice->getPpnAmount();
                $finalAmount = $totalAmount + $ppnAmount;

                // Jumlah
                $currentRow++;
                $applySummaryRow($currentRow, 'Jumlah', $totalAmount);

                // PPN
                if ($ppnAmount > 0) {
                    $currentRow++;
                    $ppnLabel = 'PPN (' . $trimNumber($invoice->ppn) . '%)';
                    $applySummaryRow($currentRow, $ppnLabel, $ppnAmount);
                }

                // Total
                $currentRow++;
                $applySummaryRow($currentRow, 'Total', $finalAmount);

                // Cicilan (payment_installments)
                if ($invoice->payment_installments) {
                    $paymentInstallments = is_string($invoice->payment_installments)
                        ? json_decode($invoice->payment_installments, true)
                        : $invoice->payment_installments;

                    if (is_array($paymentInstallments) && count($paymentInstallments) > 0) {
                        foreach ($paymentInstallments as $installment) {
                            $currentRow++;
                            $applySummaryRow($currentRow, $installment['label'] ?? 'Pembayaran', $installment['amount'] ?? 0);
                        }
                    }
                }

                // ═══ TERBILANG ═══════════════════════════════════════════════════════
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $terbilangText = 'Terbilang : ' . ucwords(terbilang($finalAmount)) . ' Rupiah';
                $sheet->setCellValue("A{$currentRow}", $terbilangText);
                $sheet->getStyle("A{$currentRow}")->getFont()->setItalic(true)->setBold(true);
                $fitWrappedRow($currentRow, $terbilangText, 80);

                // ═══ INFO PEMBAYARAN (rekening) ═══════════════════════════════════════
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

                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Pembayaran dapat ditransfer melalui nomor rekening :');

                foreach ($paymentAccounts as $account) {
                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $bankName = $sanitizeForExcel($account->bank_name);
                    $accountNumber = $sanitizeForExcel($account->account_number);
                    $accountHolder = $sanitizeForExcel($account->account_holder);
                    // Nama bank, nomor & pemilik rekening tidak di-bold
                    $sheet->setCellValue("A{$currentRow}", "{$bankName} / No : {$accountNumber} a/n {$accountHolder}");
                }

                // ═══ PENUTUP ═════════════════════════════════════════════════════════
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $closingText = 'Demikian Invoice ini kami sampaikan atas perhatian dan kerja samanya kami ucapkan terimakasih.';
                $sheet->setCellValue("A{$currentRow}", $closingText);
                $fitWrappedRow($currentRow, $closingText, 100);

                // ═══ TANDA TANGAN ════════════════════════════════════════════════════
                $currentRow += 2;
                $sheet->mergeCells("B{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("B{$currentRow}", 'Hormat Kami,');

                $currentRow++;
                $sheet->mergeCells("B{$currentRow}:F{$currentRow}");
                // Nama PT & penandatangan tidak di-bold
                $sheet->setCellValue("B{$currentRow}", 'PT. AGHITSNA KARYA INDAH');

                if ($invoice->signedBy?->signature_image) {
                    $signaturePath = storage_path('app/public/' . $invoice->signedBy->signature_image);
                    if (is_file($signaturePath)) {
                        $currentRow++;
                        $signatureDrawing = new Drawing();
                        $signatureDrawing->setName('Tanda Tangan');
                        $signatureDrawing->setDescription('Tanda Tangan ' . $invoice->signedBy->name);
                        $signatureDrawing->setPath($signaturePath);
                        $signatureDrawing->setHeight(50);
                        $signatureDrawing->setCoordinates("B{$currentRow}");
                        $signatureDrawing->setOffsetX(0);
                        $signatureDrawing->setOffsetY(2);
                        $signatureDrawing->setWorksheet($sheet);
                        $sheet->getRowDimension($currentRow)->setRowHeight(45);
                        $currentRow += 2;
                    } else {
                        // Ruang tanda tangan basah
                        $sheet->getRowDimension($currentRow + 1)->setRowHeight(45);
                        $currentRow += 2;
                    }
                } else {
                    // Ruang tanda tangan basah
                    $sheet->getRowDimension($currentRow + 1)->setRowHeight(45);
                    $currentRow += 2;
                }

                $sheet->mergeCells("B{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("B{$currentRow}", $invoice->signedBy?->name ?? '');
                $sheet->getStyle("B{$currentRow}")->getFont()->setUnderline(Font::UNDERLINE_SINGLE);

                $currentRow++;
                $sheet->mergeCells("B{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("B{$currentRow}", $invoice->signedBy?->position ?? '');

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

                // Stamp LUNAS jika invoice lunas
                if ($invoice->isFullyPaid()) {
                    $stampPath = public_path('images/status_paid_proyek_and_item.jpeg');
                    if (is_file($stampPath)) {
                        $currentRow -= 3;
                        $stampDrawing = new Drawing();
                        $stampDrawing->setName('LUNAS');
                        $stampDrawing->setDescription('Stamp Lunas');
                        $stampDrawing->setPath($stampPath);
                        $stampDrawing->setHeight(75);
                        $stampDrawing->setCoordinates("E{$currentRow}");
                        $stampDrawing->setOffsetX(5);
                        $stampDrawing->setOffsetY(2);
                        $stampDrawing->setWorksheet($sheet);
                    }
                }
            },
        ];
    }
}
