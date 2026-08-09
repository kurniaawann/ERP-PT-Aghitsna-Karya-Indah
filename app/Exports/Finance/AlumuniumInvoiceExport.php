<?php

namespace App\Exports\Finance;

use App\Models\Finance\InvoiceAlumunium;
use App\Models\Finance\PaymentAccount;
use Carbon\Carbon;
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
            'A' => 5,
            'B' => 35,
            'C' => 12,
            'D' => 10,
            'E' => 18,
            'F' => 20,
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
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(22)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('000000'));
                $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->mergeCells('A2:D2');
                $sheet->setCellValue('A2', 'AGHITSNA ALUMUNIUM DAN BAJA RINGAN');
                $sheet->getStyle('A2')->getFont()->setBold(true);
                $sheet->getStyle('A2')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->mergeCells('A3:D3');
                $sheet->setCellValue('A3', 'JL. TANAH BARU RAYA PERTIWI RT. 01/05 BEJI, DEPOK, JAWA BARAT');
                $sheet->getStyle('A3')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->mergeCells('A4:D4');
                $sheet->setCellValue('A4', 'Telp. 021 - 29034923 - 0812 9596 552');
                $sheet->getStyle('A4')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->mergeCells('A5:D5');
                $sheet->setCellValue('A5', 'Email : Design@aghitsna.id');
                $sheet->getStyle('A5')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $invoiceDate = Carbon::parse($invoice->invoice_date)->isoFormat('DD MMMM YYYY');

                $sheet->setCellValue('E2', 'No');
                $sheet->setCellValue('F2', ': ' . $invoice->invoice_number);
                $sheet->getStyle('E2')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle('F2')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->setCellValue('E3', 'Tanggal');
                $sheet->setCellValue('F3', ': ' . $invoiceDate);
                $sheet->getStyle('E3')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle('F3')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->setCellValue('E4', 'Hal');
                $sheet->setCellValue('F4', ': ' . ($invoice->regarding ?? '-'));
                $sheet->getStyle('E4')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle('F4')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $currentRow = 7;
                $sheet->setCellValue("A{$currentRow}", 'Kepada Yth :');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

                $sheet->setCellValue("B{$currentRow}", $invoice->recipient);
                $sheet->mergeCells("B{$currentRow}:F{$currentRow}");

                if (!empty($invoice->proyek)) {
                    $currentRow++;
                    $sheet->setCellValue("B{$currentRow}", $invoice->proyek);
                    $sheet->mergeCells("B{$currentRow}:F{$currentRow}");
                }

                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Dengan ini kami sampaikan invoice untuk proyek ' . ($invoice->project_description ?? '') . ' sebagai berikut :');

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

                    $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $itemEndRow = $currentRow;

                $sheet->getStyle("A{$itemStartRow}:F{$itemEndRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]);

                $currentRow++;
                $sheet->setCellValue("A{$currentRow}", '');
                $sheet->setCellValue("B{$currentRow}", '');
                $sheet->setCellValue("C{$currentRow}", '');
                $sheet->setCellValue("D{$currentRow}", '');
                $sheet->setCellValue("E{$currentRow}", 'Jumlah');
                $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($totalAmount, 0, ',', '.'));

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
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT
                    ]
                ]);

                if ($invoice->discount_value && $invoice->discount_value > 0) {
                    $discountAmount = $invoice->getDiscountAmount($totalAmount);

                    $currentRow++;
                    $discountLabel = 'Discount';
                    if ($invoice->discount_type === 'percentage') {
                        $discountLabel .= ' (' . number_format((float) $invoice->discount_value, 0) . '%)';
                    }
                    $sheet->setCellValue("A{$currentRow}", '');
                    $sheet->setCellValue("B{$currentRow}", '');
                    $sheet->setCellValue("C{$currentRow}", '');
                    $sheet->setCellValue("D{$currentRow}", '');
                    $sheet->setCellValue("E{$currentRow}", $discountLabel);
                    $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($discountAmount, 0, ',', '.'));

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
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_RIGHT
                        ]
                    ]);
                }

                if ($invoice->dp_value && $invoice->dp_value > 0) {
                    $dpAmount = $invoice->getDpAmount();

                    $currentRow++;
                    $dpLabel = 'DP';
                    if ($invoice->dp_type === 'percentage') {
                        $dpLabel .= ' (' . number_format((float) $invoice->dp_value, 0) . '%)';
                    }
                    $sheet->setCellValue("A{$currentRow}", '');
                    $sheet->setCellValue("B{$currentRow}", '');
                    $sheet->setCellValue("C{$currentRow}", '');
                    $sheet->setCellValue("D{$currentRow}", '');
                    $sheet->setCellValue("E{$currentRow}", $dpLabel);
                    $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($dpAmount, 0, ',', '.'));

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
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_RIGHT
                        ]
                    ]);
                }

                $discountAmountVal = 0;
                $dpAmountVal = 0;
                if ($invoice->discount_value && $invoice->discount_value > 0) {
                    $discountAmountVal = $invoice->getDiscountAmount($totalAmount);
                }
                if ($invoice->dp_value && $invoice->dp_value > 0) {
                    $dpAmountVal = $invoice->getDpAmount();
                }
                $hasDiscountOrDp = $discountAmountVal > 0 || $dpAmountVal > 0;
                $remainingAmount = $totalAmount - $discountAmountVal - $dpAmountVal;

                if ($hasDiscountOrDp) {
                    $currentRow++;
                    $sheet->setCellValue("A{$currentRow}", '');
                    $sheet->setCellValue("B{$currentRow}", '');
                    $sheet->setCellValue("C{$currentRow}", '');
                    $sheet->setCellValue("D{$currentRow}", '');
                    $sheet->setCellValue("E{$currentRow}", 'Sisa Pembayaran');
                    $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($remainingAmount, 0, ',', '.'));

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
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_RIGHT
                        ]
                    ]);
                }

                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Terbilang : ' . ucwords(terbilang($totalAmount)) . ' rupiah');
                $sheet->getStyle("A{$currentRow}")->getFont()->setItalic(true);

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
                    $sheet->setCellValue("A{$currentRow}", "{$bankName} / No : {$accountNumber} a/n {$accountHolder}");
                    $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
                }

                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Demikian Invoice ini kami buat atas perhatian dan kerjasamanya kami ucapkan terima kasih.');
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true);

                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Hormat Kami,');

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'PT. AGHITSNA KARYA INDAH');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

                if ($invoice->signedBy?->signature_image) {
                    $signaturePath = storage_path('app/public/' . $invoice->signedBy->signature_image);
                    if (is_file($signaturePath)) {
                        $currentRow += 2;
                        $signatureDrawing = new Drawing();
                        $signatureDrawing->setName('Tanda Tangan');
                        $signatureDrawing->setDescription('Tanda Tangan ' . $invoice->signedBy->name);
                        $signatureDrawing->setPath($signaturePath);
                        $signatureDrawing->setHeight(55);
                        $signatureDrawing->setCoordinates("A{$currentRow}");
                        $signatureDrawing->setOffsetX(10);
                        $signatureDrawing->setOffsetY(2);
                        $signatureDrawing->setWorksheet($sheet);

                        $sheet->getRowDimension($currentRow)->setRowHeight(45);
                        $currentRow += 3;
                    } else {
                        $currentRow += 4;
                    }
                } else {
                    $currentRow += 4;
                }

                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $signedBy = $invoice->signedBy?->name ?? 'Akhmad Khaidir';
                $sheet->setCellValue("A{$currentRow}", $signedBy);

                if ($invoice->division) {
                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", $invoice->division->name);
                }
            },
        ];
    }
}
