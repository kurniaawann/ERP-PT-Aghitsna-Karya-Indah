<?php

namespace App\Exports\Administrasi;

use App\Models\Administrasi\ProjectQuotation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProjectQuotationExport implements FromCollection, WithEvents, WithTitle, WithColumnWidths
{
    protected $quotation;

    public function __construct($quotationNumber)
    {
        $this->quotation = ProjectQuotation::with(['items'])
            ->where('quotation_number', $quotationNumber)
            ->firstOrFail();
    }

    public function collection()
    {
        return collect([]);
    }

    public function title(): string
    {
        return 'Penawaran ' . $this->quotation->quotation_number;
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
                $sheet->getRowDimension(1)->setRowHeight(20);
                $sheet->getRowDimension(2)->setRowHeight(15);
                $sheet->getRowDimension(3)->setRowHeight(15);
                $sheet->getRowDimension(4)->setRowHeight(15);
                $sheet->getRowDimension(5)->setRowHeight(15);

                // Add logo image (Row 1-5)
                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Company Logo');
                $drawing->setPath(public_path('images/logo.jpeg'));
                $drawing->setHeight(70);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(10);
                $drawing->setOffsetY(5);
                $drawing->setWorksheet($sheet);

                // PENAWARAN Title (Center - Row 1)
                $sheet->mergeCells('C1:D1');
                $sheet->setCellValue('C1', 'PENAWARAN');
                $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(24);
                $sheet->getStyle('C1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                // Company Info (Left side - Row 2-5 under logo)
                $sheet->mergeCells('B2:D2');
                $sheet->setCellValue('B2', 'PT.AGHITSNA KARYA INDAH');
                $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(12);

                $sheet->mergeCells('B3:D3');
                $sheet->setCellValue('B3', 'JL. TANAH BARU RAYA PERTIWI RT.01/05');
                $sheet->getStyle('B3')->getFont()->setSize(9);

                $sheet->mergeCells('B4:D4');
                $sheet->setCellValue('B4', 'BEJI, DEPOK, JAWA BARAT');
                $sheet->getStyle('B4')->getFont()->setSize(9);

                $sheet->mergeCells('B5:D5');
                $sheet->setCellValue('B5', 'Telp. 021-29034923 - 0812.9596.552');
                $sheet->getStyle('B5')->getFont()->setSize(9);

                $sheet->mergeCells('B6:D6');
                $sheet->setCellValue('B6', 'Email: Design@aghitsna.id');
                $sheet->getStyle('B6')->getFont()->setSize(9);

                // Document Info (Right side - Row 1-3)
                $quotationDate = \Carbon\Carbon::parse($quotation->date)->isoFormat('DD MMMM YYYY');

                $sheet->setCellValue('E1', 'No');
                $sheet->setCellValue('F1', ': ' . $quotation->quotation_number);
                $sheet->getStyle('E1:F1')->getFont()->setSize(10);

                $sheet->setCellValue('E2', 'Tanggal');
                $sheet->setCellValue('F2', ': ' . $quotationDate);
                $sheet->getStyle('E2:F2')->getFont()->setSize(10);

                $sheet->setCellValue('E3', 'Hal');
                $sheet->setCellValue('F3', ': ' . $quotation->subject);
                $sheet->getStyle('E3:F3')->getFont()->setSize(10);

                // Recipient (Row 8)
                $currentRow = 8;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Kepada Yth :');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

                // Recipient Name (Row 9)
                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", '            ' . $quotation->recipient);

                // Ditempat (Row 11)
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Ditempat');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

                // Opening text (Row 13)
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Dengan ini kami sampaikan Penawaran Harga, sebagai berikut :');

                // Table Header (Row 15)
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

                // Items (Flat structure)
                $items = $quotation->items()->orderBy('order_number')->get();
                $itemStartRow = $currentRow + 1;

                foreach ($items as $index => $item) {
                    $currentRow++;
                    $sheet->setCellValue("A{$currentRow}", ($index + 1) . '.');
                    $sheet->setCellValue("B{$currentRow}", $item->description);
                    $sheet->setCellValue("C{$currentRow}", $item->volume ?? '-');
                    $sheet->setCellValue("D{$currentRow}", $item->unit ?? '-');
                    $sheet->setCellValue("E{$currentRow}", 'Rp ' . number_format($item->unit_price, 0, ',', '.'));
                    $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($item->total_price, 0, ',', '.'));

                    // Style alignment
                    $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $itemEndRow = $currentRow;

                // Apply borders to all items
                $sheet->getStyle("A{$itemStartRow}:F{$itemEndRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]);

                // Grand Total row
                $currentRow++;

                // Empty cells for No, Keterangan, Volume (no background)
                $sheet->mergeCells("A{$currentRow}:C{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", '');

                // Jumlah spanning Satuan + Harga
                $sheet->mergeCells("D{$currentRow}:E{$currentRow}");
                $sheet->setCellValue("D{$currentRow}", 'Jumlah');

                // Amount
                $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($quotation->total_amount, 0, ',', '.'));

                // Style only the yellow cells (D-F)
                $sheet->getStyle("D{$currentRow}:F{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00']
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]);

                // Remove borders from empty cells (A-C)
                $sheet->getStyle("A{$currentRow}:C{$currentRow}")->applyFromArray([
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_NONE]
                    ]
                ]);

                $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Terbilang
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $amountInWords = $quotation->amount_in_words ?? ucwords(terbilang($quotation->total_amount)) . ' rupiah';
                $sheet->setCellValue("A{$currentRow}", 'Terbilang : ' . $amountInWords);
                $sheet->getStyle("A{$currentRow}")->getFont()->setItalic(true);

                // Payment Information
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Pembayaran dapat di transfer melalui rekening');

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

                foreach ($paymentAccounts as $account) {
                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", "Bank {$account->bank_name} / No : {$account->account_number} a/n {$account->account_holder}");
                }

                // Closing
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Demikian penawaran ini kami sampaikan atas perhatian dan kerjasamanya kami ucapkan terimakasih');

                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Hormat Kami,');

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'PT.AGHITSNA KARYA INDAH');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

                // Signature space
                $currentRow += 5;
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
