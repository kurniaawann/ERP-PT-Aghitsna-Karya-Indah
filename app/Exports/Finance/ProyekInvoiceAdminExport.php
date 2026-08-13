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
                $invoice = $this->invoice;

                // Helper: trim trailing zero pada angka persentase (format PDF).
                $trimNumber = function ($value) {
                    return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
                };

                // Gaya baris ringkasan keuangan (Jumlah/Discount/PPN/DP/Sisa) — sama dengan PDF admin.
                $applySummaryRow = function ($row, $label, $amount, $amountRaw = null) use ($sheet, $trimNumber) {
                    $sheet->mergeCells("A{$row}:D{$row}");
                    $sheet->setCellValue("A{$row}", '');
                    $sheet->setCellValue("E{$row}", $label);
                    $sheet->setCellValue("F{$row}", $amountRaw ?? 'Rp ' . number_format((float) $amount, 0, ',', '.'));

                    $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                        ],
                    ]);
                    $sheet->getStyle("E{$row}:F{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'A6A6A6'],
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);
                    $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                };

                // ═══ HEADER (logo + judul INVOICE) ═══════════════════════════════════
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
                $sheet->setCellValue('B1', 'INVOICE');
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(22)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('000000'));
                $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                // Garis pemisah header (hitam tebal)
                $sheet->getStyle('A2:F2')->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_MEDIUM)
                    ->getColor()->setARGB('FF000000');

                // ═══ INFO PERUSAHAAN & META SURAT ════════════════════════════════════
                $invoiceDate = Carbon::parse($invoice->invoice_date)->isoFormat('D MMMM YYYY');

                $sheet->mergeCells('A3:D3');
                $sheet->setCellValue('A3', 'PT. AGHITSNA KARYA INDAH');
                $sheet->getStyle('A3')->getFont()->setBold(true);
                $sheet->getStyle('A3')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->setCellValue('E3', 'No');
                $sheet->setCellValue('F3', ': ' . $invoice->invoice_number);
                $sheet->getStyle('E3')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle('F3')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->mergeCells('A4:D4');
                $sheet->setCellValue('A4', 'JL. TANAH BARU RAYA PERTIWI RT.01/05');
                $sheet->getStyle('A4')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->setCellValue('E4', 'Tanggal');
                $sheet->setCellValue('F4', ': ' . $invoiceDate);
                $sheet->getStyle('E4')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle('F4')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->mergeCells('A5:D5');
                $sheet->setCellValue('A5', 'BEJI. DEPOK.JAWA BARAT');
                $sheet->getStyle('A5')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->setCellValue('E5', 'Hal');
                $sheet->setCellValue('F5', ': ' . ($invoice->regarding ?? 'Penagihan Pembayaran'));
                $sheet->getStyle('E5')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle('F5')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->mergeCells('A6:D6');
                $sheet->setCellValue('A6', 'Telp. 021-29034923 – 0812.9596.552');
                $sheet->getStyle('A6')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $sheet->mergeCells('A7:D7');
                $sheet->setCellValue('A7', 'Email : Design@aghitsna.id');
                $sheet->getStyle('A7')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                // ═══ PENERIMA SURAT ═════════════════════════════════════════════════
                $currentRow = 9;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Kepada Yth :');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

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
                $sheet->setCellValue("A{$currentRow}",
                    $invoice->project_description
                        ? 'Dengan ini kami sampaikan Invoice untuk pekerjaan ' . $invoice->project_description . ', ' . $location . ', sebagai berikut :'
                        : (($invoice->location ?? $invoice->quotation?->location)
                            ? 'Dengan ini kami sampaikan invoice sebagai berikut : Lokasi ' . ($invoice->location ?? $invoice->quotation?->location)
                            : 'Dengan ini kami sampaikan invoice sebagai berikut :'));
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true);
                $sheet->getRowDimension($currentRow)->setRowHeight(30);

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
                    $jumlah = (float) ($item['volume'] ?? 0) * (float) ($item['harga'] ?? 0);
                    $totalAmount += $jumlah;

                    $sheet->setCellValueExplicit("A{$currentRow}", ($index + 1) . '.', DataType::TYPE_STRING);
                    $sheet->setCellValue("B{$currentRow}", '   ' . ($item['keterangan'] ?? ''));
                    $volume = $item['volume'] ?? 0;
                    $sheet->setCellValue("C{$currentRow}", ($volume !== null && $volume !== '') ? number_format((float) $volume, 2, ',', '.') : '-');
                    $sheet->setCellValue("D{$currentRow}", $item['satuan'] ?? '-');
                    $sheet->setCellValue("E{$currentRow}", 'Rp ' . number_format($item['harga'] ?? 0, 0, ',', '.'));
                    $sheet->setCellValue("F{$currentRow}", 'Rp ' . number_format($jumlah, 0, ',', '.'));

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

                // ═══ RINGKASAN KEUANGAN ══════════════════════════════════════════════
                $discountAmount = ($invoice->discount_value && (float) $invoice->discount_value > 0)
                    ? $invoice->getDiscountAmount($totalAmount)
                    : 0;
                $dpAmount = ($invoice->dp_value && (float) $invoice->dp_value > 0)
                    ? $invoice->getDpAmount()
                    : 0;
                $ppnAmount = $invoice->getPpnAmount();
                $hasAdjustments = $discountAmount > 0 || $dpAmount > 0 || $ppnAmount > 0;
                $finalAmount = $totalAmount - $discountAmount + $ppnAmount - $dpAmount;

                // Jumlah
                $currentRow++;
                $applySummaryRow($currentRow, 'Jumlah', $totalAmount);

                // Discount
                if ($discountAmount > 0) {
                    $currentRow++;
                    $discountLabel = 'Discount' . ($invoice->discount_type === 'percentage' ? ' (' . $trimNumber($invoice->discount_value) . '%)' : '');
                    $applySummaryRow($currentRow, $discountLabel, -$discountAmount, 'Rp -' . number_format($discountAmount, 0, ',', '.'));
                }

                // PPN
                if ($ppnAmount > 0) {
                    $currentRow++;
                    $ppnLabel = 'PPN (' . $trimNumber($invoice->ppn) . '%)';
                    $applySummaryRow($currentRow, $ppnLabel, $ppnAmount);
                }

                // DP
                if ($dpAmount > 0) {
                    $currentRow++;
                    $dpLabel = 'DP' . ($invoice->dp_type === 'percentage' ? ' (' . $trimNumber($invoice->dp_value) . '%)' : '');
                    $applySummaryRow($currentRow, $dpLabel, -$dpAmount, 'Rp -' . number_format($dpAmount, 0, ',', '.'));
                }

                // Total / Sisa Pembayaran
                $currentRow++;
                $applySummaryRow($currentRow, $hasAdjustments ? 'Sisa Pembayaran' : 'Total', $finalAmount);

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
                $sheet->setCellValue("A{$currentRow}", 'Terbilang : ' . ucwords(terbilang($finalAmount)) . ' Rupiah');
                $sheet->getStyle("A{$currentRow}")->getFont()->setItalic(true);

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
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

                foreach ($paymentAccounts as $account) {
                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $bankName = $sanitizeForExcel($account->bank_name);
                    $accountNumber = $sanitizeForExcel($account->account_number);
                    $accountHolder = $sanitizeForExcel($account->account_holder);
                    $sheet->setCellValue("A{$currentRow}", "{$bankName} / No : {$accountNumber} a/n {$accountHolder}");
                    $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
                }

                // ═══ TANDA TANGAN ════════════════════════════════════════════════════
                $currentRow += 2;
                $sheet->mergeCells("B{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("B{$currentRow}", 'Hormat Kami,');

                $currentRow++;
                $sheet->mergeCells("B{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("B{$currentRow}", 'PT. AGHITSNA KARYA INDAH');
                $sheet->getStyle("B{$currentRow}")->getFont()->setBold(true);

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
                        $currentRow += 2;
                    }
                } else {
                    $currentRow += 2;
                }

                $sheet->mergeCells("B{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("B{$currentRow}", $invoice->signedBy?->name ?? '');
                $sheet->getStyle("B{$currentRow}")->getFont()->setBold(true)->setUnderline(Font::UNDERLINE_SINGLE);

                $currentRow++;
                $sheet->mergeCells("B{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("B{$currentRow}", $invoice->signedBy?->position ?? '');

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
