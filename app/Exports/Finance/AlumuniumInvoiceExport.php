<?php

namespace App\Exports\Finance;

use App\Models\Finance\InvoiceAlumunium;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AlumuniumInvoiceExport implements FromCollection, WithEvents, WithTitle, WithColumnWidths
{
    protected $invoice;

    public function __construct($invoiceNumber)
    {
        $this->invoice = InvoiceAlumunium::where('invoice_number', $invoiceNumber)->firstOrFail();
    }

    public function collection()
    {
        return collect([]);
    }

    public function title(): string
    {
        return 'Invoice_Aluminium_' . $this->invoice->invoice_number;
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
                $invoice = $this->invoice;

                // Set row heights
                $sheet->getRowDimension(1)->setRowHeight(60);
                $sheet->getRowDimension(2)->setRowHeight(15);

                // Add logo image
                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Company Logo');
                $drawing->setPath(public_path('images/logo.jpeg'));
                $drawing->setHeight(60);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(10);
                $drawing->setOffsetY(5);
                $drawing->setWorksheet($sheet);

                // Merge cells for header
                $sheet->mergeCells('A1:D1');
                $sheet->mergeCells('E1:F1');

                // Company Name & Info (Row 2-5)
                $sheet->mergeCells('A2:D2');
                $sheet->setCellValue('A2', 'PT. AGHITSNA KARYA INDAH');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF6600'));

                $sheet->mergeCells('A3:D3');
                $sheet->setCellValue('A3', 'AGHITSNA ALUMUNIUM DAN BAJA RINGAN');

                $sheet->mergeCells('A4:D4');
                $sheet->setCellValue('A4', 'JL. CEMARA RT 02 RW 07, KEL, GROGOL');

                $sheet->mergeCells('A5:D5');
                $sheet->setCellValue('A5', 'KEC. LIMO KOTA DEPOK');

                $sheet->mergeCells('A6:D6');
                $sheet->setCellValue('A6', 'Telp. 0882 1303 1263 / 0882 1303 1264 | Email: Design@aghitsna.id');

                // Invoice Title (Right side)
                $sheet->mergeCells('E1:F1');
                $sheet->setCellValue('E1', 'INVOICE');
                $sheet->getStyle('E1')->getFont()->setBold(true)->setSize(24)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('000000'));
                $sheet->getStyle('E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('E1')->getFill()->setFillType(Fill::FILL_NONE);

                // Invoice Information
                $invoiceDate = \Carbon\Carbon::parse($invoice->invoice_date)->isoFormat('DD MMMM YYYY');

                $sheet->setCellValue('E2', 'No');
                $sheet->setCellValue('F2', ': ' . $invoice->invoice_number);

                $sheet->setCellValue('E3', 'Tanggal');
                $sheet->setCellValue('F3', ': ' . $invoiceDate);

                $sheet->setCellValue('E4', 'Hal');
                $sheet->setCellValue('F4', ': ' . $invoice->regarding);

                // Border for header
                $sheet->getStyle('A1:F6')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK);

                // Recipient (Row 8)
                $currentRow = 8;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Kepada Yth :');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

                // Recipient Name (Row 8)
                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", '            ' . $invoice->recipient);

                // Description (Row 10-11)
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Ditempat');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", $invoice->project_description);

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

                // Items data
                $items = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
                $totalAmount = 0;
                $itemStartRow = $currentRow + 1;

                foreach ($items as $index => $item) {
                    $currentRow++;
                    $jumlah = floatval($item['volume']) * floatval($item['harga']);
                    $totalAmount += $jumlah;

                    $sheet->setCellValue("A{$currentRow}", $index + 1);
                    $sheet->setCellValue("B{$currentRow}", $item['keterangan']);
                    $sheet->setCellValue("C{$currentRow}", number_format($item['volume'], 2, ',', '.'));
                    $sheet->setCellValue("D{$currentRow}", $item['satuan']);
                    $sheet->setCellValue("E{$currentRow}", 'Rp ' . number_format($item['harga'], 0, ',', '.'));
                    $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($jumlah, 0, ',', '.'));

                    // Style alignment
                    $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
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

                // Total row
                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Jumlah');
                $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($totalAmount, 0, ',', '.'));

                $sheet->getStyle("A{$currentRow}:F{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00']
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER
                    ]
                ]);
                $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Discount row (if exists)
                if ($invoice->discount_value && $invoice->discount_value > 0) {
                    $discountAmount = 0;
                    if ($invoice->discount_type === 'percentage') {
                        $discountAmount = ($totalAmount * $invoice->discount_value) / 100;
                    } else {
                        $discountAmount = $invoice->discount_value;
                    }
                    $totalAfterDiscount = $totalAmount - $discountAmount;

                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
                    $discountLabel = 'Discount';
                    if ($invoice->discount_type === 'percentage') {
                        $discountLabel .= ' (' . number_format((float) $invoice->discount_value, 0) . '%)';
                    }
                    $sheet->setCellValue("A{$currentRow}", $discountLabel);
                    $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($discountAmount, 0, ',', '.'));

                    $sheet->getStyle("A{$currentRow}:F{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFE6E6']
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER
                        ]
                    ]);
                    $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Total after discount
                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", 'Total Setelah Discount');
                    $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($totalAfterDiscount, 0, ',', '.'));

                    $sheet->getStyle("A{$currentRow}:F{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '90EE90']
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER
                        ]
                    ]);
                    $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } else {
                    $totalAfterDiscount = $totalAmount;
                }

                // DP row (if exists)
                if ($invoice->dp_value && $invoice->dp_value > 0) {
                    $baseForDP = $totalAfterDiscount;
                    $dpAmount = 0;
                    if ($invoice->dp_type === 'percentage') {
                        $dpAmount = ($baseForDP * $invoice->dp_value) / 100;
                    } else {
                        $dpAmount = $invoice->dp_value;
                    }

                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
                    $dpLabel = 'DP';
                    if ($invoice->dp_type === 'percentage') {
                        $dpLabel .= ' (' . number_format((float) $invoice->dp_value, 0) . '%)';
                    }
                    $sheet->setCellValue("A{$currentRow}", $dpLabel);
                    $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($dpAmount, 0, ',', '.'));

                    $sheet->getStyle("A{$currentRow}:F{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'ADD8E6']
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER
                        ]
                    ]);
                    $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // Terbilang
                $terbilangAmount = $totalAfterDiscount;
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Terbilang : ' . ucwords(terbilang($terbilangAmount)) . ' rupiah');
                $sheet->getStyle("A{$currentRow}")->getFont()->setItalic(true);

                // Payment Information
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Pembayaran dapat ditransfer melalui nomor rekening');

                // Get selected payment accounts from invoice
                $selectedAccountIds = is_string($invoice->selected_payment_accounts)
                    ? json_decode($invoice->selected_payment_accounts, true)
                    : ($invoice->selected_payment_accounts ?? []);

                if (!empty($selectedAccountIds)) {
                    $paymentAccounts = \App\Models\Finance\PaymentAccount::whereIn('id', $selectedAccountIds)
                        ->orderBy('id')
                        ->get();
                } else {
                    // Fallback ke semua rekening aktif jika tidak ada yang dipilih
                    $paymentAccounts = \App\Models\Finance\PaymentAccount::active()->get();
                }

                // Helper to sanitize Excel cell values to prevent formula injection
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
                    $sheet->setCellValue("A{$currentRow}", "{$bankName} / No : {$accountNumber} a/n {$accountHolder}");
                    $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
                }

                // Closing
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Demikian Invoice ini kami buat atas perhatian dan kerjasamanya kami ucapkan terima kasih.');
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true);

                // Signature
                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Hormat Kami,');

                $currentRow += 4;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'PT AGHITSNA KARYA INDAH');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
            },
        ];
    }
}
