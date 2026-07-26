<?php

namespace App\Exports\Administrasi;

use App\Models\Administrasi\AluminiumQuotation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class AluminiumQuotationExport implements FromCollection, WithEvents, WithTitle, WithColumnWidths
{
    protected $quotation;

    public function __construct($quotationNumber)
    {
        $this->quotation = AluminiumQuotation::with(['groups.items'])
            ->where('quotation_number', $quotationNumber)
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
        return [
            'A' => 5,   // No
            'B' => 35,  // Keterangan
            'C' => 12,  // Volume
            'D' => 10,  // Satuan
            'E' => 18,  // Harga
            'F' => 20,  // Jumlah
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $quotation = $this->quotation;

                // Set row heights
                $sheet->getRowDimension(1)->setRowHeight(60);
                $sheet->getRowDimension(2)->setRowHeight(15);

                // Add logo image
                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Company Logo');
                $drawing->setPath(public_path('images/logo.jpeg'));
                $drawing->setHeight(55);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(5);
                $drawing->setOffsetY(3);
                $drawing->setWorksheet($sheet);

                // Title (centered)
                $sheet->mergeCells('B1:F1');
                $sheet->setCellValue('B1', 'PENAWARAN ALUMUNIUM');
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(22)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('000000'));
                $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                // Company Name & Info (Row 2-6)
                $sheet->mergeCells('A2:D2');
                $sheet->setCellValue('A2', 'AGHITSNA ALUMUNIUM DAN BAJA RINGAN');
                $sheet->getStyle('A2')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->mergeCells('A3:D3');
                $sheet->setCellValue('A3', 'JL. TANAH BARU RAYA PERTIWI RT.01/05');
                $sheet->getStyle('A3')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->mergeCells('A4:D4');
                $sheet->setCellValue('A4', 'BEJI, DEPOK, JAWA BARAT');
                $sheet->getStyle('A4')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->mergeCells('A5:D5');
                $sheet->setCellValue('A5', 'Telp. 021-29034923 - 0812.9596.552');
                $sheet->getStyle('A5')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->mergeCells('A6:D6');
                $sheet->setCellValue('A6', 'Email : Design@aghitsna.id');
                $sheet->getStyle('A6')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                // Quotation Information (right side)
                $quotationDate = Carbon::parse($quotation->date)->isoFormat('DD MMMM YYYY');

                $sheet->setCellValue('E2', 'No');
                $sheet->setCellValue('F2', ': ' . $quotation->quotation_number);
                $sheet->getStyle('E2')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle('F2')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->setCellValue('E3', 'Tanggal');
                $sheet->setCellValue('F3', ': ' . $quotationDate);
                $sheet->getStyle('E3')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle('F3')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->setCellValue('E4', 'Hal');
                $sheet->setCellValue('F4', ': ' . $quotation->subject);
                $sheet->getStyle('E4')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle('F4')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                // Recipient (Row 8)
                $currentRow = 8;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Kepada Yth :');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

                // Recipient Name (Row 9)
                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", '            ' . $quotation->recipient);

                // Opening text (Row 11)
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Dengan ini kami sampaikan ' . ($quotation->project_description ?? ''));

                // Table Header (Row 14)
                $currentRow += 2;
                $tableHeaderRow = $currentRow;

                $sheet->setCellValue("A{$currentRow}", 'No');
                $sheet->setCellValue("B{$currentRow}", 'Keterangan');
                $sheet->setCellValue("C{$currentRow}", 'Volume');
                $sheet->setCellValue("D{$currentRow}", 'Satuan');
                $sheet->setCellValue("E{$currentRow}", 'Harga');
                $sheet->setCellValue("F{$currentRow}", 'Jumlah');

                // Style table header
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

                // Groups and Items
                $groups = $quotation->groups()->orderBy('order_number')->get();
                $grandTotal = 0;
                $itemStartRow = $currentRow + 1;

                foreach ($groups as $groupIndex => $group) {
                    // Group header row
                    $currentRow++;
                    $sheet->setCellValue("A{$currentRow}", ($groupIndex + 1) . '.');
                    $sheet->mergeCells("B{$currentRow}:F{$currentRow}");
                    $sheet->setCellValue("B{$currentRow}", $group->name);
                    $sheet->getStyle("A{$currentRow}:F{$currentRow}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Group items
                    $items = $group->items()->orderBy('order_number')->get();
                    foreach ($items as $item) {
                        $currentRow++;
                        $sheet->setCellValue("A{$currentRow}", '');
                        $sheet->setCellValue("B{$currentRow}", '   ' . $item->description);
                        $sheet->setCellValue("C{$currentRow}", $item->volume ? number_format($item->volume, 2, ',', '.') : '-');
                        $sheet->setCellValue("D{$currentRow}", $item->unit ?? '-');
                        $sheet->setCellValue("E{$currentRow}", 'Rp ' . number_format($item->unit_price, 0, ',', '.'));
                        $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($item->total_price, 0, ',', '.'));

                        // Style alignment
                        $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }

                    $grandTotal += $group->subtotal;
                }

                $itemEndRow = $currentRow;

                // Apply borders to all items
                $sheet->getStyle("A{$itemStartRow}:F{$itemEndRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]);

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

                // Style yellow cells (E-F)
                $sheet->getStyle("E{$currentRow}:F{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00']
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]);

                $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Terbilang
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $amountInWords = $quotation->amount_in_words ?? ucwords(terbilang($grandTotal)) . ' rupiah';
                $sheet->setCellValue("A{$currentRow}", 'Terbilang : ' . $amountInWords);
                $sheet->getStyle("A{$currentRow}")->getFont()->setItalic(true);

                // Payment Information
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Pembayaran dapat ditransfer melalui rekening');

                // Get selected payment accounts
                $selectedAccountIds = is_string($quotation->selected_payment_accounts)
                    ? json_decode($quotation->selected_payment_accounts, true)
                    : ($quotation->selected_payment_accounts ?? []);

                if (!empty($selectedAccountIds)) {
                    $paymentAccounts = \App\Models\Finance\PaymentAccount::whereIn('id', $selectedAccountIds)
                        ->orderBy('id')
                        ->get();
                } else {
                    $paymentAccounts = \App\Models\Finance\PaymentAccount::active()->get();
                }

                // Helper to sanitize Excel cell values
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
                    $sheet->setCellValue("A{$currentRow}", "Bank {$bankName} / No : {$accountNumber} a/n {$accountHolder}");
                    $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
                }

                // Closing
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Demikian penawaran ini kami sampaikan atas perhatian dan kerjasamanya kami ucapkan terimakasih');
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true);

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Hormat Kami,');

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'PT.AGHITSNA KARYA INDAH');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

                // Signature space
                $currentRow += 4;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $signedBy = $quotation->signed_by ?? 'Akhmad Khaidir';
                $sheet->setCellValue("A{$currentRow}", $signedBy);
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $division = $quotation->division ?? 'Divisi Alumunium';
                $sheet->setCellValue("A{$currentRow}", $division);
            },
        ];
    }
}
