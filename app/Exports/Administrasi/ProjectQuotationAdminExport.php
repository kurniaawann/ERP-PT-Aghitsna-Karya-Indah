<?php

namespace App\Exports\Administrasi;

use App\Models\Administrasi\ProjectQuotation;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ProjectQuotationAdminExport implements FromCollection, WithEvents, WithTitle, WithColumnWidths
{
    protected $quotation;

    public function __construct($quotationNumber)
    {
        $this->quotation = ProjectQuotation::query()
            ->where('quotation_number', $quotationNumber)
            ->firstOrFail();
    }

    public function collection()
    {
        return collect([]);
    }

    public function title(): string
    {
        return 'Penawaran_Proyek_Admin_' . $this->quotation->quotation_number;
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

                // Font dasar seluruh surat: Times New Roman 12 (revisi klien). Kop surat diperbesar terpisah.
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

                // Lebar gabungan kolom A:F (untuk estimasi wrap teks)
                $fullWidth = 85;

                // Helper: trim trailing zero pada angka persentase (format PDF).
                $trimNumber = function ($value) {
                    return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
                };

                // ═══ KOP SURAT (logo + nama & alamat perusahaan) ══════════════════════
                $sheet->getRowDimension(1)->setRowHeight(30);
                foreach ([2, 3, 4] as $row) {
                    $sheet->getRowDimension($row)->setRowHeight(14);
                }

                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Company Logo');
                $drawing->setPath(public_path('images/logo.jpeg'));
                $drawing->setWidth(115);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(2);
                $drawing->setOffsetY(8);
                $drawing->setWorksheet($sheet);

                // Teks kop dimulai di kolom B dengan indent agar tidak tertutup logo
                $kopIndent = 8;

                $sheet->mergeCells('B1:F1');
                $sheet->setCellValue('B1', 'PT. AGHITSNA KARYA INDAH');
                $sheet->getStyle('B1')->getFont()->setName('Arial')->setBold(true)->setSize(20)->setColor(new Color('E53935'));
                // Indent hanya berlaku bila perataan horizontal eksplisit LEFT
                $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_BOTTOM)->setIndent($kopIndent);

                $kopLines = [
                    2 => 'JL. PERTIWI NO.36 TANAH BARU RAYA RT.01/05, BEJI. DEPOK, JAWA BARAT',
                    3 => 'Telp. 021-29034923 – 0812.9596.552',
                    4 => 'Email : design@aghitsna.id / Zulkarnainmarzuki@yahoo.com',
                ];
                foreach ($kopLines as $row => $text) {
                    $sheet->mergeCells("B{$row}:F{$row}");
                    $sheet->setCellValue("B{$row}", $text);
                    $sheet->getStyle("B{$row}")->getFont()->setName('Arial')->setBold(true)->setSize(10)->setColor(new Color('1565C0'));
                    $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setIndent($kopIndent);
                }

                // Garis pemisah kop surat (biru tebal)
                $sheet->getStyle('A4:F4')->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_THICK)
                    ->getColor()->setARGB('FF1565C0');

                // ═══ JUDUL SURAT ═════════════════════════════════════════════════════
                $sheet->getRowDimension(6)->setRowHeight(26);
                $sheet->mergeCells('A6:F6');
                $sheet->setCellValue('A6', 'SURAT PENAWARAN HARGA PEMBANGUNAN');
                $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(16)->setUnderline(Font::UNDERLINE_SINGLE);
                $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                // ═══ METADATA SURAT (Nomor / Lampiran / Perihal) ═════════════════════
                $currentRow = 8;
                foreach ([
                    'Nomor' => $quotation->quotation_number,
                    'Lampiran' => $quotation->attachment ?? '-',
                    'Perihal' => $quotation->subject,
                ] as $label => $value) {
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", "{$label} : {$value}");
                    $currentRow++;
                }

                // ═══ PENERIMA SURAT (tidak bold, revisi klien) ═══════════════════════
                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Kepada Yth,');

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Bapak ' . $quotation->recipient);

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Di Tempat');

                // ═══ PARAGRAF PEMBUKA ════════════════════════════════════════════════
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Dengan Hormat,');

                $currentRow++;
                $openingText = $quotation->project_description
                    ? 'Sehubungan dengan rencana pembangunan bangunan ' . $quotation->project_description . ' yang berlokasi di jalan ' . ($quotation->location ?? '-') . ', bersama ini kami sampaikan penawaran harga pelaksanaan pekerjaan pembangunan dengan rincian sebagai berikut :'
                    : ($quotation->location
                        ? 'Sehubungan dengan rencana pembangunan yang berlokasi di jalan ' . $quotation->location . ', bersama ini kami sampaikan penawaran harga pelaksanaan pekerjaan pembangunan dengan rincian sebagai berikut :'
                        : 'Sehubungan dengan rencana pembangunan, bersama ini kami sampaikan penawaran harga pelaksanaan pekerjaan pembangunan dengan rincian sebagai berikut :');
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", $openingText);
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP)->setHorizontal(Alignment::HORIZONTAL_JUSTIFY);
                $sheet->getRowDimension($currentRow)->setRowHeight($this->estimateRowHeight($openingText, $fullWidth));

                // ═══ TABEL ITEMS ═════════════════════════════════════════════════════
                $currentRow += 2;
                $isTextMode = ($quotation->items_mode ?? 'items') === 'text';

                if ($isTextMode) {
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", $quotation->free_text ?? '-');
                    $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP)->setHorizontal(Alignment::HORIZONTAL_JUSTIFY);
                    $sheet->getRowDimension($currentRow)->setRowHeight($this->estimateRowHeight($quotation->free_text ?? '-', $fullWidth));
                } else {

                $tableHeaderRow = $currentRow;

                $sheet->setCellValue("A{$currentRow}", 'No');
                $sheet->setCellValue("B{$currentRow}", 'Keterangan');
                $sheet->setCellValue("C{$currentRow}", 'Volume');
                $sheet->setCellValue("D{$currentRow}", 'Satuan');
                $sheet->setCellValue("E{$currentRow}", 'Harga');
                $sheet->setCellValue("F{$currentRow}", 'Jumlah');

                $sheet->getRowDimension($currentRow)->setRowHeight(20);
                // Header tabel diulang di setiap halaman saat dicetak
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($currentRow, $currentRow);
                $sheet->getStyle("A{$currentRow}:F{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E8E8E8'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $items = $quotation->items ?? [];
                $discountAmount = ($quotation->discount_type && (float) $quotation->discount_value > 0) ? (int) $quotation->getDiscountAmount() : 0;
                $grandTotal = $quotation->getFinalTotal();
                $itemStartRow = $currentRow + 1;

                foreach ($items as $index => $item) {
                    $currentRow++;
                    $sheet->setCellValueExplicit("A{$currentRow}", ($index + 1) . '.', DataType::TYPE_STRING);
                    $sheet->setCellValue("B{$currentRow}", $item['keterangan'] ?? '');
                    $volume = $item['volume'] ?? 0;
                    $sheet->setCellValue("C{$currentRow}", ($volume !== null && $volume !== '') ? number_format((float) $volume, 2, ',', '.') : '-');
                    $sheet->setCellValue("D{$currentRow}", $item['satuan'] ?? '-');
                    $sheet->setCellValue("E{$currentRow}", 'Rp ' . number_format($item['harga'] ?? 0, 0, ',', '.'));
                    $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format((float) ($item['volume'] ?? 0) * ($item['harga'] ?? 0), 0, ',', '.'));
                    // Tinggi baris eksplisit (keterangan panjang di-wrap) + sedikit ruang atas-bawah
                    $sheet->getRowDimension($currentRow)->setRowHeight($this->estimateRowHeight($item['keterangan'] ?? '', 28) + 4);
                }

                $itemEndRow = $currentRow;

                if ($itemEndRow >= $itemStartRow) {
                    // Border + perataan: No/Volume/Satuan/Harga/Jumlah rata tengah, Keterangan rata kiri (wrap)
                    $sheet->getStyle("A{$itemStartRow}:F{$itemEndRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
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

                // Baris Discount (opsional)
                if ($discountAmount > 0) {
                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:D{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", '');
                    $sheet->setCellValue("E{$currentRow}", 'Discount ' . ($quotation->discount_type === 'percentage' ? '(' . $trimNumber($quotation->discount_value) . '%)' : ''));
                    $sheet->setCellValue("F{$currentRow}", 'Rp -' . number_format($discountAmount, 0, ',', '.'));

                    $sheet->getStyle("A{$currentRow}:D{$currentRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                        ],
                    ]);
                    $sheet->getRowDimension($currentRow)->setRowHeight(20);
                    $sheet->getStyle("E{$currentRow}:F{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                }

                // Baris Total (label & nominal rata tengah)
                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:D{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", '');
                $sheet->setCellValue("E{$currentRow}", 'Total');
                $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($grandTotal, 0, ',', '.'));

                $sheet->getStyle("A{$currentRow}:D{$currentRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                    ],
                ]);
                $sheet->getRowDimension($currentRow)->setRowHeight(20);
                $sheet->getStyle("E{$currentRow}:F{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // ═══ TERBILANG (bold, revisi klien) ══════════════════════════════════
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

                } // end else items mode

                // ═══ PARAGRAF PENUTUP ═════════════════════════════════════════════════
                $currentRow += 2;
                $closingText = 'Demikian surat penawaran ini kami sampaikan. Besar harapan kami untuk dapat bekerja sama dalam pelaksanaan pembangunan tersebut. Atas perhatian dan kepercayaan Bapak, kami ucapkan terima kasih.';
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", $closingText);
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP)->setHorizontal(Alignment::HORIZONTAL_JUSTIFY);
                $sheet->getRowDimension($currentRow)->setRowHeight($this->estimateRowHeight($closingText, $fullWidth));

                // ═══ TANDA TANGAN ════════════════════════════════════════════════════
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", ($quotation->city ?? 'Jakarta') . ', ' . Carbon::parse($quotation->date)->isoFormat('D MMMM YYYY'));

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Hormat Kami,');

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", $quotation->division?->name ?? '');

                // Ruang tanda tangan (gambar TTD bila ada), nama penanda tangan tepat di bawahnya
                $currentRow++;
                $sheet->getRowDimension($currentRow)->setRowHeight(48);
                if ($quotation->signedBy?->signature_image) {
                    $signaturePath = storage_path('app/public/' . $quotation->signedBy->signature_image);
                    if (is_file($signaturePath)) {
                        $signatureDrawing = new Drawing();
                        $signatureDrawing->setName('Tanda Tangan');
                        $signatureDrawing->setDescription('Tanda Tangan ' . $quotation->signedBy->name);
                        $signatureDrawing->setPath($signaturePath);
                        $signatureDrawing->setHeight(55);
                        $signatureDrawing->setCoordinates("A{$currentRow}");
                        $signatureDrawing->setOffsetX(2);
                        $signatureDrawing->setOffsetY(4);
                        $signatureDrawing->setWorksheet($sheet);
                    }
                }

                // Nama penanda tangan tidak bold (revisi klien), tetap bergaris bawah
                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", $quotation->signedBy?->name ?? '');
                $sheet->getStyle("A{$currentRow}")->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
            },
        ];
    }
}
