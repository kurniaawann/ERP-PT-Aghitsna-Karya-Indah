<?php

namespace App\Exports\Administrasi;

use App\Models\Administrasi\AluminiumQuotation;
use App\Models\Finance\PaymentAccount;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Carbon\Carbon;

class AluminiumQuotationExport implements FromCollection, WithEvents, WithTitle, WithColumnWidths
{
    protected $quotation;

    public function __construct($quotationNumber)
    {
        $this->quotation = AluminiumQuotation::where('quotation_number', $quotationNumber)
            ->firstOrFail();
    }

    public function collection()
    {
        return collect([]);
    }

    public function title(): string
    {
        return 'Penawaran_Aluminium_' . $this->quotation->quotation_number;
    }

    public function columnWidths(): array
    {
        // Lebar dalam satuan karakter font dasar (Times New Roman 12); total ±85 agar muat lebar A4
        return [
            'A' => 5,   // No
            'B' => 30,  // Keterangan
            'C' => 9,   // Volume
            'D' => 9,   // Satuan
            'E' => 16,  // Harga
            'F' => 16,  // Jumlah
        ];
    }

    /**
     * Estimasi tinggi baris (pt) untuk teks yang di-wrap selebar $widthChars (satuan lebar kolom).
     * Dibutuhkan karena Excel tidak auto-fit tinggi baris untuk sel yang di-merge.
     */
    private function estimateRowHeight(?string $text, float $widthChars, float $lineHeight = 15.75): float
    {
        // Rata-rata lebar huruf Times New Roman 12 ≈ 0,95 lebar digit (acuan satuan lebar kolom)
        $charsPerLine = max(1, (int) floor($widthChars / 0.95));
        $lines = 0;
        foreach (preg_split('/\r\n|\r|\n/', (string) $text) as $paragraph) {
            $lines += max(1, (int) ceil(mb_strlen($paragraph) / $charsPerLine));
        }

        return $lines * $lineHeight;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $quotation = $this->quotation;

                // Font dasar seluruh dokumen: Times New Roman 12 (revisi klien). Kop surat diperbesar terpisah.
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(12);
                // Tinggi baris standar untuk TNR 12 (posisi gambar TTD konsisten di Excel & LibreOffice)
                $sheet->getDefaultRowDimension()->setRowHeight(15.75);

                // Pengaturan cetak: A4 portrait, seluruh kolom muat dalam 1 halaman lebar
                $sheet->getPageSetup()
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);

                // Lebar gabungan kolom (untuk estimasi wrap teks)
                $fullWidth = 85;   // A:F
                $infoWidth = 32;   // E:F

                // ═══ KOP SURAT ═══════════════════════════════════════════════════════
                // Baris 1: logo (kiri) + judul dokumen (tengah halaman)
                $sheet->getRowDimension(1)->setRowHeight(58);

                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Company Logo');
                $drawing->setPath(public_path('images/logo.jpeg'));
                $drawing->setWidth(115);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(2);
                $drawing->setOffsetY(5);
                $drawing->setWorksheet($sheet);

                $sheet->mergeCells('A1:F1');
                $sheet->setCellValue('A1', 'PENAWARAN');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(20);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                // Baris 2: nama usaha
                $sheet->getRowDimension(2)->setRowHeight(24);
                $sheet->mergeCells('A2:F2');
                $sheet->setCellValue('A2', 'AGHITSNA ALUMUNIUM DAN BAJA RINGAN');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A2')->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);

                // Baris 3-6: alamat perusahaan (kiri, A:C)
                $companyLines = [
                    3 => 'JL. CEMARA RT 02 RW 07, KEL. GROGOL,',
                    4 => 'KEC. LIMO, KOTA DEPOK',
                    5 => 'Telp : 0838 9004 1408 / 0818 0844 4519',
                    6 => 'Email : Design@aghitsna.id',
                ];
                foreach ($companyLines as $row => $text) {
                    $sheet->mergeCells("A{$row}:C{$row}");
                    $sheet->setCellValue("A{$row}", $text);
                    $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                }

                // Baris 3-5: informasi penawaran (kanan: label di D, nilai di E:F)
                $quotationDate = Carbon::parse($quotation->date)->isoFormat('DD MMMM YYYY');
                $infoLines = [
                    3 => ['No', $quotation->quotation_number],
                    4 => ['Tanggal', $quotationDate],
                    5 => ['Hal', $quotation->subject],
                ];
                foreach ($infoLines as $row => [$label, $value]) {
                    $sheet->setCellValue("D{$row}", $label);
                    // Nilai "Hal" di-merge sampai baris 6 agar perihal panjang bisa turun ke baris berikutnya
                    $sheet->mergeCells($row === 5 ? 'E5:F6' : "E{$row}:F{$row}");
                    $sheet->setCellValue("E{$row}", ': ' . $value);
                    $sheet->getStyle("D{$row}:E{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
                }
                // Perihal lebih dari 2 baris: baris 6 dipertinggi
                $halHeight = $this->estimateRowHeight(': ' . $quotation->subject, $infoWidth);
                if ($halHeight > 2 * 15.75) {
                    $sheet->getRowDimension(6)->setRowHeight($halHeight - 15.75);
                }

                // ═══ PENERIMA (tidak bold, revisi klien) ═════════════════════════════
                // Jarak ±1 baris kosong antara Email dan "Kepada Yth" (revisi klien)
                $sheet->getRowDimension(7)->setRowHeight(20);
                $currentRow = 8;
                $sheet->setCellValue("A{$currentRow}", 'Kepada Yth :');
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");

                // Recipient Name (Row 9)
                $currentRow++;
                $sheet->setCellValue("A{$currentRow}", $quotation->recipient);
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");

                // Nama Proyek (Row 10)
                if (!empty($quotation->proyek)) {
                    $currentRow++;
                    $sheet->setCellValue("A{$currentRow}", $quotation->proyek);
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                }

                // Opening text
                $currentRow += 2;
                // Jarak ±1 baris kosong sebelum "Dengan ini kami sampaikan" (revisi klien)
                $sheet->getRowDimension($currentRow - 1)->setRowHeight(20);
                $openingText = $quotation->project_description
                    ? 'Dengan ini kami sampaikan penawaran untuk proyek ' . $quotation->project_description . ' sebagai berikut :'
                    : 'Dengan ini kami sampaikan penawaran sebagai berikut :';
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", $openingText);
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                $openingHeight = $this->estimateRowHeight($openingText, $fullWidth);
                if ($openingHeight > 15.75) {
                    $sheet->getRowDimension($currentRow)->setRowHeight($openingHeight);
                }

                // ═══ TABEL ITEMS ═════════════════════════════════════════════════════
                $currentRow += 2;
                $tableHeaderRow = $currentRow;

                $sheet->setCellValue("A{$currentRow}", 'No');
                $sheet->setCellValue("B{$currentRow}", 'Keterangan');
                $sheet->setCellValue("C{$currentRow}", 'Volume');
                $sheet->setCellValue("D{$currentRow}", 'Satuan');
                $sheet->setCellValue("E{$currentRow}", 'Harga');
                $sheet->setCellValue("F{$currentRow}", 'Jumlah');

                // Style table header
                $sheet->getRowDimension($currentRow)->setRowHeight(20);
                // Header tabel diulang di setiap halaman saat dicetak
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($currentRow, $currentRow);
                $sheet->getStyle("A{$currentRow}:F{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0F0F0']
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Items and financial summary
                $items = $quotation->items ?? [];
                // Total akhir = setelah diskon (sama dengan total invoice yang dibuat dari penawaran)
                $grandTotal = $quotation->getFinalTotal();
                $discountAmount = ($quotation->discount_type && (float) $quotation->discount_value > 0) ? (int) $quotation->getDiscountAmount() : 0;
                $itemStartRow = $currentRow + 1;

                foreach ($items as $index => $item) {
                    $currentRow++;
                    $sheet->setCellValueExplicit("A{$currentRow}", ($index + 1) . '.', DataType::TYPE_STRING);
                    $sheet->setCellValue("B{$currentRow}", $item['keterangan'] ?? '');
                    // Tinggi baris eksplisit (keterangan panjang di-wrap) + sedikit ruang atas-bawah;
                    // juga menjaga posisi gambar TTD tetap benar saat dibuka di LibreOffice
                    $sheet->getRowDimension($currentRow)->setRowHeight($this->estimateRowHeight($item['keterangan'] ?? '', 28) + 4);
                    $volume = $item['volume'] ?? 0;
                    $sheet->setCellValue("C{$currentRow}", ($volume !== null && $volume !== '') ? number_format((float) $volume, 2, ',', '.') : '-');
                    $sheet->setCellValue("D{$currentRow}", $item['satuan'] ?? '-');
                    $sheet->setCellValue("E{$currentRow}", 'Rp ' . number_format($item['harga'] ?? 0, 0, ',', '.'));
                    $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format((float) ($item['volume'] ?? 0) * ($item['harga'] ?? 0), 0, ',', '.'));
                }

                $itemEndRow = $currentRow;

                if ($itemEndRow >= $itemStartRow) {
                    // Border + perataan: No/Volume/Satuan/Harga/Jumlah rata tengah, Keterangan rata kiri (wrap)
                    $sheet->getStyle("A{$itemStartRow}:F{$itemEndRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                    $sheet->getStyle("B{$itemStartRow}:B{$itemEndRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                        ->setWrapText(true)
                        ->setIndent(1);
                }

                // Discount row (optional)
                if ($discountAmount > 0) {
                    $currentRow++;
                    // Persentase tanpa nol di belakang koma (sama seperti PDF), mis. "5%" bukan "5,00%"
                    $discountPercent = rtrim(rtrim(number_format((float) $quotation->discount_value, 2, ',', '.'), '0'), ',');
                    $sheet->setCellValue("E{$currentRow}", 'Discount' . ($quotation->discount_type === 'percentage' ? ' (' . $discountPercent . '%)' : ''));
                    $sheet->setCellValue("F{$currentRow}", 'Rp -' . number_format($discountAmount, 0, ',', '.'));
                    $sheet->getRowDimension($currentRow)->setRowHeight(20);
                    $sheet->getStyle("E{$currentRow}:F{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                }

                // Grand Total row - match PDF layout: empty 4 cols + "Total" in E + amount in F
                $currentRow++;

                // Empty cells for No, Keterangan, Volume, Satuan (no background, no border)
                $sheet->mergeCells("A{$currentRow}:D{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", '');

                // "Total" label in Harga column
                $sheet->setCellValue("E{$currentRow}", 'Total');

                // Amount
                $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($grandTotal, 0, ',', '.'));

                // Style empty cells (no border)
                $sheet->getStyle("A{$currentRow}:D{$currentRow}")->applyFromArray([
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_NONE]
                    ]
                ]);

                // Style yellow cells (E-F), label & nominal rata tengah
                $sheet->getRowDimension($currentRow)->setRowHeight(20);
                $sheet->getStyle("E{$currentRow}:F{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00']
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Terbilang (bold, revisi klien)
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $amountInWords = ucwords(terbilang($grandTotal)) . ' rupiah';
                $terbilangText = 'Terbilang : ' . $amountInWords;
                $sheet->setCellValue("A{$currentRow}", $terbilangText);
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true)->setItalic(true);
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                // Teks bold lebih lebar ±10% dari teks biasa
                $terbilangHeight = $this->estimateRowHeight($terbilangText, $fullWidth / 1.1);
                if ($terbilangHeight > 15.75) {
                    $sheet->getRowDimension($currentRow)->setRowHeight($terbilangHeight);
                }

                // Payment Information
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Pembayaran dapat ditransfer melalui rekening');

                // Get selected payment accounts
                $selectedAccountIds = is_string($quotation->selected_payment_accounts)
                    ? json_decode($quotation->selected_payment_accounts, true)
                    : ($quotation->selected_payment_accounts ?? []);

                if (!empty($selectedAccountIds)) {
                    $paymentAccounts = PaymentAccount::whereIn('id', $selectedAccountIds)
                        ->orderBy('id')
                        ->get();
                } else {
                    $paymentAccounts = PaymentAccount::active()->get();
                }

                // Helper to sanitize Excel cell values
                $sanitizeForExcel = function ($value) {
                    if (is_string($value) && preg_match('/^[=+\-@]/', $value)) {
                        return "'" . $value;
                    }
                    return $value;
                };

                // Nama bank, nomor rekening & a/n tidak bold (revisi klien)
                foreach ($paymentAccounts as $account) {
                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $bankName = $sanitizeForExcel($account->bank_name);
                    $accountNumber = $sanitizeForExcel($account->account_number);
                    $accountHolder = $sanitizeForExcel($account->account_holder);
                    $sheet->setCellValue("A{$currentRow}", "Bank {$bankName} / No : {$accountNumber} a/n {$accountHolder}");
                }

                // Closing
                $currentRow += 2;
                $closingText = 'Demikian penawaran ini kami sampaikan atas perhatian dan kerjasamanya kami ucapkan terimakasih';
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", $closingText);
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                $closingHeight = $this->estimateRowHeight($closingText, $fullWidth);
                if ($closingHeight > 15.75) {
                    $sheet->getRowDimension($currentRow)->setRowHeight($closingHeight);
                }

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Hormat Kami,');

                // Nama perusahaan tidak bold (revisi klien)
                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'PT. AGHITSNA KARYA INDAH');

                // Signature space
                if ($quotation->signedBy?->signature_image) {
                    $signaturePath = storage_path('app/public/' . $quotation->signedBy->signature_image);
                    if (is_file($signaturePath)) {
                        // Gambar TTD tepat di bawah nama perusahaan, nama penanda tangan tepat di bawahnya
                        $currentRow++;
                        $signatureDrawing = new Drawing();
                        $signatureDrawing->setName('Tanda Tangan');
                        $signatureDrawing->setDescription('Tanda Tangan ' . $quotation->signedBy->name);
                        $signatureDrawing->setPath($signaturePath);
                        $signatureDrawing->setHeight(55);
                        $signatureDrawing->setCoordinates("A{$currentRow}");
                        $signatureDrawing->setOffsetX(10);
                        $signatureDrawing->setOffsetY(4);
                        $signatureDrawing->setWorksheet($sheet);

                        $sheet->getRowDimension($currentRow)->setRowHeight(48);
                        $currentRow++;
                    } else {
                        $currentRow += 4;
                    }
                } else {
                    $currentRow += 4;
                }

                // Nama penanda tangan tidak bold (revisi klien)
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", $quotation->signedBy?->name ?? '');

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", $quotation->division?->name ?? '');
            },
        ];
    }
}
