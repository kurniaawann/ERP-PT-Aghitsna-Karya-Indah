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

                // Gaya baris ringkasan keuangan (Discount/PPN/cicilan/Jumlah) — sama dengan PDF admin:
                // label di bawah kolom Harga + %, nominal di bawah kolom Jumlah.
                $applySummaryRow = function ($row, $label, $amount) use ($sheet) {
                    $sheet->mergeCells("A{$row}:B{$row}");
                    $sheet->mergeCells("C{$row}:D{$row}");
                    $sheet->mergeCells("E{$row}:F{$row}");
                    $sheet->setCellValue("A{$row}", '');
                    $sheet->setCellValue("C{$row}", $label);
                    $sheet->setCellValue("E{$row}", format_rupiah($amount));

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

                // Kop surat (revisi klien): nama perusahaan 12pt, alamat/telp/email 11pt
                $sheet->mergeCells('A3:C3');
                $sheet->setCellValue('A3', 'PT. AGHITSNA KARYA INDAH');
                $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A3')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getRowDimension(3)->setRowHeight($lineHeight);

                $companyLines = [
                    4 => 'JL. TANAH BARU RAYA PERTIWI RT.01/05',
                    5 => 'BEJI. DEPOK.JAWA BARAT',
                    6 => 'Telp. 021-29034923 – 0812.9596.552',
                    7 => 'Email : Design@aghitsna.id',
                ];
                foreach ($companyLines as $row => $text) {
                    $sheet->mergeCells("A{$row}:C{$row}");
                    $sheet->setCellValue("A{$row}", $text);
                    $sheet->getStyle("A{$row}")->getFont()->setSize(11);
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
                // Tepat 1 baris kosong sebelum "Kepada Yth :" (revisi klien)
                $sheet->getRowDimension(8)->setRowHeight($lineHeight);
                $currentRow = 9;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                // Kepada Yth tidak di-bold
                $sheet->setCellValue("A{$currentRow}", 'Kepada Yth :');

                // Nama penerima ditulis apa adanya (tanpa awalan "Bpk.")
                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", $invoice->recipient);

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Di Tempat');

                // ═══ PARAGRAF PEMBUKA ════════════════════════════════════════════════
                // Tepat 1 baris kosong sebelum "Dengan Hormat," (revisi klien)
                $currentRow += 2;
                $sheet->getRowDimension($currentRow - 1)->setRowHeight($lineHeight);
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Dengan Hormat,');

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $location = $invoice->location ?? $invoice->quotation?->location ?? '-';
                $openingText = $invoice->project_description
                    ? 'Dengan ini kami sampaikan invoice untuk pekerjaan ' . $invoice->project_description . ', Lokasi ' . $location . ', sebagai berikut :'
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
                    // % per item opsional (null bila kosong): sel % dikosongkan dan Jumlah = Harga.
                    // Pakai InvoiceProyek::itemAmount() (bukan $item['jumlah']) karena item lama belum punya key itu.
                    $hasPersentase = isset($item['persentase']) && $item['persentase'] !== '';
                    $jumlah = InvoiceProyek::itemAmount(is_array($item) ? $item : []);
                    $totalAmount += $jumlah;

                    $sheet->setCellValueExplicit("A{$currentRow}", ($index + 1) . '.', DataType::TYPE_STRING);
                    $sheet->setCellValue("B{$currentRow}", '   ' . ($item['deskripsi'] ?? ''));
                    $fitWrappedRow($currentRow, '   ' . ($item['deskripsi'] ?? ''), 32, 'B');
                    $sheet->setCellValue("C{$currentRow}", format_rupiah($harga));
                    // % tanpa nol di belakang koma (25%, 12,5%)
                    $sheet->setCellValueExplicit("D{$currentRow}", $hasPersentase ? format_persen($item['persentase']) . '%' : '', DataType::TYPE_STRING);
                    $sheet->setCellValue("E{$currentRow}", format_rupiah($jumlah));
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
                // Urutan (revisi klien): [Discount] → PPN → Pembayaran Ke-n → Jumlah (paling bawah).
                // Jumlah = total item setelah discount + PPN; baris "Total" ditiadakan.
                $discountAmount = ($invoice->discount_value && $invoice->discount_value > 0)
                    ? $invoice->getDiscountAmount($totalAmount)
                    : 0;
                $ppnAmount = $invoice->getPpnAmount();
                $finalAmount = $totalAmount - $discountAmount + $ppnAmount;

                // Discount (bila ada)
                if ($discountAmount > 0) {
                    $currentRow++;
                    $discountLabel = 'Discount' . ($invoice->discount_type === 'percentage' ? ' (' . format_persen($invoice->discount_value) . '%)' : '');
                    $applySummaryRow($currentRow, $discountLabel, $discountAmount);
                }

                // PPN selalu tampil, walau kosong
                $currentRow++;
                $ppnLabel = $ppnAmount > 0 ? 'PPN (' . format_persen($invoice->ppn) . '%)' : 'PPN';
                $applySummaryRow($currentRow, $ppnLabel, $ppnAmount);

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

                // Jumlah akhir
                $currentRow++;
                $applySummaryRow($currentRow, 'Jumlah', $finalAmount);

                // ═══ TERBILANG ═══════════════════════════════════════════════════════
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $terbilangText = 'Terbilang : ' . ucwords(terbilang(round($finalAmount))) . ' Rupiah';
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
                // 2 baris kosong di atas kalimat penutup (revisi klien)
                $currentRow += 3;
                $sheet->getRowDimension($currentRow - 2)->setRowHeight($lineHeight);
                $sheet->getRowDimension($currentRow - 1)->setRowHeight($lineHeight);
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $closingText = 'Demikian invoice ini kami sampaikan atas perhatian dan kerja samanya kami ucapkan terimakasih.';
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

                // Stamp LUNAS jika invoice lunas (gambar baru, transparan; rasio ±3:1)
                if ($invoice->isFullyPaid()) {
                    $stampPath = $this->transparentStampPath(public_path('images/watermark_lunas.jpeg'));
                    if ($stampPath) {
                        $currentRow -= 3;
                        $stampDrawing = new Drawing();
                        $stampDrawing->setName('LUNAS');
                        $stampDrawing->setDescription('Stamp Lunas');
                        $stampDrawing->setPath($stampPath);
                        $stampDrawing->setWidth(220);
                        $stampDrawing->setCoordinates("D{$currentRow}");
                        $stampDrawing->setOffsetX(20);
                        $stampDrawing->setOffsetY(2);
                        $stampDrawing->setWorksheet($sheet);
                    }
                }
            },
        ];
    }

    /**
     * Membuat versi PNG transparan dari gambar stempel LUNAS (latar putih → transparan,
     * tinta diberi opacity rendah) karena gambar di Excel tidak mendukung pengaturan opacity.
     *
     * Hasil disimpan sebagai cache di direktori temp sistem (dibuat ulang bila sumber berubah).
     *
     * @param  string  $sourcePath  Path gambar stempel (JPEG/PNG berlatar putih)
     * @param  float  $opacity  Opacity tinta stempel (0-1)
     * @return string|null  Path PNG transparan, atau path sumber bila GD tidak tersedia
     */
    private function transparentStampPath(string $sourcePath, float $opacity = 0.25): ?string
    {
        if (!is_file($sourcePath)) {
            return null;
        }

        if (!function_exists('imagecreatefromstring')) {
            return $sourcePath;
        }

        $cachePath = sys_get_temp_dir() . '/aghitsna_stamp_' . md5($sourcePath . filemtime($sourcePath) . $opacity) . '.png';
        if (is_file($cachePath)) {
            return $cachePath;
        }

        $source = @imagecreatefromstring((string) file_get_contents($sourcePath));
        if (!$source) {
            return $sourcePath;
        }

        // Diperkecil dulu agar proses per piksel ringan (lebar 800px cukup untuk stempel)
        $width = min(800, imagesx($source));
        $height = (int) round(imagesy($source) * $width / imagesx($source));
        $resized = imagecreatetruecolor($width, $height);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));
        imagedestroy($source);

        $output = imagecreatetruecolor($width, $height);
        imagealphablending($output, false);
        imagesavealpha($output, true);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($resized, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Kepekatan tinta = seberapa jauh dari putih (0 = putih/latar, 1 = tinta penuh)
                $ink = (255 - min($r, $g, $b)) / 255;
                // Alpha GD: 0 = opak, 127 = transparan penuh
                $alpha = 127 - (int) round(127 * $ink * $opacity);
                imagesetpixel($output, $x, $y, imagecolorallocatealpha($output, 3, 52, 195, $alpha));
            }
        }
        imagedestroy($resized);

        $saved = @imagepng($output, $cachePath);
        imagedestroy($output);

        return $saved ? $cachePath : $sourcePath;
    }
}
