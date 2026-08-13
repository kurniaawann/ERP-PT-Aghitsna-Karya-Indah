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
        return [
            'A' => 5,   // No
            'B' => 36,  // Keterangan
            'C' => 12,  // Volume
            'D' => 12,  // Satuan
            'E' => 20,  // Harga
            'F' => 22,  // Jumlah
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $quotation = $this->quotation;

                // Helper: trim trailing zero pada angka persentase (format PDF).
                $trimNumber = function ($value) {
                    return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
                };

                // ═══ KOP SURAT (logo + nama & alamat perusahaan) ══════════════════════
                $sheet->getRowDimension(1)->setRowHeight(60);

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
                $sheet->setCellValue('B1', 'PT. AGHITSNA KARYA INDAH');
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16)->setColor(new Color('E53935'));
                $sheet->getStyle('B1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->mergeCells('B2:F2');
                $sheet->setCellValue('B2', 'JL. PERTIWI NO.36 TANAH BARU RAYA RT.01/05, BEJI. DEPOK, JAWA BARAT');
                $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(10)->setColor(new Color('1565C0'));

                $sheet->mergeCells('B3:F3');
                $sheet->setCellValue('B3', 'Telp. 021-29034923 – 0812.9596.552, Email : design@aghitsna.id / Zulkarnainmarzuki@yahoo.com');
                $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(10)->setColor(new Color('1565C0'));

                // Garis pemisah kop surat (biru tebal)
                $sheet->getStyle('A4:F4')->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_MEDIUM)
                    ->getColor()->setARGB('FF1565C0');

                // ═══ JUDUL SURAT ═════════════════════════════════════════════════════
                $sheet->getRowDimension(6)->setRowHeight(24);
                $sheet->mergeCells('A6:F6');
                $sheet->setCellValue('A6', 'SURAT PENAWARAN HARGA PEMBANGUNAN');
                $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(13)->setUnderline(Font::UNDERLINE_SINGLE);
                $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // ═══ METADATA SURAT (Nomor / Lampiran / Perihal) ═════════════════════
                $currentRow = 7;
                foreach ([
                    'Nomor' => $quotation->quotation_number,
                    'Lampiran' => $quotation->attachment ?? '-',
                    'Perihal' => $quotation->subject,
                ] as $label => $value) {
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", "{$label} : {$value}");
                    $currentRow++;
                }

                // ═══ PENERIMA SURAT ═════════════════════════════════════════════════
                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Kepada Yth,');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

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
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}",
                    $quotation->project_description
                        ? 'Sehubungan dengan rencana pembangunan bangunan ' . $quotation->project_description . ' yang berlokasi di jalan ' . ($quotation->location ?? '-') . ', bersama ini kami sampaikan penawaran harga pelaksanaan pekerjaan pembangunan dengan rincian sebagai berikut :'
                        : ($quotation->location
                            ? 'Sehubungan dengan rencana pembangunan yang berlokasi di jalan ' . $quotation->location . ', bersama ini kami sampaikan penawaran harga pelaksanaan pekerjaan pembangunan dengan rincian sebagai berikut :'
                            : 'Sehubungan dengan rencana pembangunan, bersama ini kami sampaikan penawaran harga pelaksanaan pekerjaan pembangunan dengan rincian sebagai berikut :'));
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true);
                $sheet->getRowDimension($currentRow)->setRowHeight(42);

                // ═══ TABEL ITEMS ═════════════════════════════════════════════════════
                $currentRow += 2;
                $tableHeaderRow = $currentRow;

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
                $grandTotal = (int) ($quotation->total_amount ?? 0) - $discountAmount;
                $itemStartRow = $currentRow + 1;

                foreach ($items as $index => $item) {
                    $currentRow++;
                    $sheet->setCellValueExplicit("A{$currentRow}", ($index + 1) . '.', DataType::TYPE_STRING);
                    $sheet->setCellValue("B{$currentRow}", '   ' . ($item['keterangan'] ?? ''));
                    $volume = $item['volume'] ?? 0;
                    $sheet->setCellValue("C{$currentRow}", ($volume !== null && $volume !== '') ? number_format((float) $volume, 2, ',', '.') : '-');
                    $sheet->setCellValue("D{$currentRow}", $item['satuan'] ?? '-');
                    $sheet->setCellValue("E{$currentRow}", 'Rp ' . number_format($item['harga'] ?? 0, 0, ',', '.'));
                    $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format((float) ($item['volume'] ?? 0) * ($item['harga'] ?? 0), 0, ',', '.'));

                    $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $itemEndRow = $currentRow;

                $sheet->getStyle("A{$itemStartRow}:F{$itemEndRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

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
                    $sheet->getStyle("E{$currentRow}:F{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);
                    $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // Baris Total
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
                $sheet->getStyle("E{$currentRow}:F{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);
                $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // ═══ TERBILANG ═══════════════════════════════════════════════════════
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $amountInWords = $quotation->amount_in_words ?? ucwords(terbilang($grandTotal)) . ' rupiah';
                $sheet->setCellValue("A{$currentRow}", 'Terbilang : ' . $amountInWords);
                $sheet->getStyle("A{$currentRow}")->getFont()->setItalic(true);

                // ═══ PARAGRAF PENUTUP ═════════════════════════════════════════════════
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Demikian surat penawaran ini kami sampaikan. Besar harapan kami untuk dapat bekerja sama dalam pelaksanaan pembangunan tersebut. Atas perhatian dan kepercayaan Bapak, kami ucapkan terima kasih.');
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true);
                $sheet->getRowDimension($currentRow)->setRowHeight(30);

                // ═══ TANDA TANGAN ════════════════════════════════════════════════════
                $currentRow += 2;
                $sheet->mergeCells("B{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("B{$currentRow}", ($quotation->city ?? 'Jakarta') . ', ' . Carbon::parse($quotation->date)->isoFormat('D MMMM YYYY'));

                $currentRow++;
                $sheet->mergeCells("B{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("B{$currentRow}", 'Hormat Kami,');

                $currentRow++;
                $sheet->mergeCells("B{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("B{$currentRow}", $quotation->division?->name ?? '');

                if ($quotation->signedBy?->signature_image) {
                    $signaturePath = storage_path('app/public/' . $quotation->signedBy->signature_image);
                    if (is_file($signaturePath)) {
                        $currentRow++;
                        $signatureDrawing = new Drawing();
                        $signatureDrawing->setName('Tanda Tangan');
                        $signatureDrawing->setDescription('Tanda Tangan ' . $quotation->signedBy->name);
                        $signatureDrawing->setPath($signaturePath);
                        $signatureDrawing->setHeight(50);
                        $signatureDrawing->setCoordinates("B{$currentRow}");
                        $signatureDrawing->setOffsetX(0);
                        $signatureDrawing->setOffsetY(2);
                        $signatureDrawing->setWorksheet($sheet);
                        $sheet->getRowDimension($currentRow)->setRowHeight(45);
                        $currentRow += 2;
                    } else {
                        $currentRow += 2;
                    }
                } else {
                    $currentRow += 2;
                }

                $sheet->mergeCells("B{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("B{$currentRow}", $quotation->signedBy?->name ?? '');
                $sheet->getStyle("B{$currentRow}")->getFont()->setBold(true)->setUnderline(Font::UNDERLINE_SINGLE);
            },
        ];
    }
}
