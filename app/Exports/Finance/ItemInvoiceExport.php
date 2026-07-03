<?php

namespace App\Exports\Finance;

use App\Models\Finance\InvoiceBarang;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ItemInvoiceExport implements FromCollection, WithEvents, WithTitle, WithColumnWidths
{
    protected $invoice;

    public function __construct($invoiceNumber)
    {
        $this->invoice = InvoiceBarang::where('invoice_number', $invoiceNumber)->firstOrFail();
    }

    public function collection()
    {
        return collect([]);
    }

    public function title(): string
    {
        return 'Invoice_Barang_' . $this->invoice->invoice_number;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 38,
            'C' => 12,
            'D' => 18,
            'E' => 20,
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
                $drawing->setHeight(60);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(10);
                $drawing->setOffsetY(5);
                $drawing->setWorksheet($sheet);

                $sheet->mergeCells('A1:C1');
                $sheet->mergeCells('D1:E1');

                $sheet->mergeCells('A2:C2');
                $sheet->setCellValue('A2', 'PT. AGHITSNA KARYA INDAH');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF6600'));

                $sheet->mergeCells('A3:C3');
                $sheet->setCellValue('A3', 'AGHITSNA ALUMUNIUM DAN BAJA RINGAN');

                $sheet->mergeCells('A4:C4');
                $sheet->setCellValue('A4', 'JL. CEMARA RT 02 RW 07, KEL, GROGOL');

                $sheet->mergeCells('A5:C5');
                $sheet->setCellValue('A5', 'KEC. LIMO KOTA DEPOK');

                $sheet->mergeCells('A6:C6');
                $sheet->setCellValue('A6', 'Telp. 0882 1303 1263 / 0882 1303 1264 | Email: Design@aghitsna.id');

                $sheet->mergeCells('D1:E1');
                $sheet->setCellValue('D1', 'INVOICE ITEM');
                $sheet->getStyle('D1')->getFont()->setBold(true)->setSize(22)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('000000'));
                $sheet->getStyle('D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->setCellValue('D2', 'No');
                $sheet->setCellValue('E2', ': ' . $invoice->invoice_number);
                $sheet->setCellValue('D3', 'Tanggal');
                $sheet->setCellValue('E3', ': ' . \Carbon\Carbon::parse($invoice->invoice_date)->isoFormat('DD MMMM YYYY'));
                $sheet->setCellValue('D4', 'Kepada');
                $sheet->setCellValue('E4', ': ' . $invoice->recipient);
                $sheet->setCellValue('D5', 'Hal');
                $sheet->setCellValue('E5', ': ' . $invoice->regarding);

                $sheet->getStyle('A1:E5')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK);

                $currentRow = 8;
                $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Kepada Yth :');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", '            ' . $invoice->recipient);

                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Ditempat');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", $invoice->project_description);

                $currentRow += 2;
                $sheet->setCellValue("A{$currentRow}", 'No');
                $sheet->setCellValue("B{$currentRow}", 'Nama Item');
                $sheet->setCellValue("C{$currentRow}", 'Qty');
                $sheet->setCellValue("D{$currentRow}", 'Harga');
                $sheet->setCellValue("E{$currentRow}", 'Jumlah');

                $sheet->getStyle("A{$currentRow}:E{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0F0F0'],
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
                    $quantity = (int) ($item['quantity'] ?? 0);
                    $sellingPrice = (int) ($item['selling_price'] ?? 0);
                    $jumlah = $sellingPrice * $quantity;
                    $totalAmount += $jumlah;

                    $sheet->setCellValue("A{$currentRow}", $index + 1);
                    $sheet->setCellValue("B{$currentRow}", $item['name_item'] ?? '-');
                    $sheet->setCellValue("C{$currentRow}", $quantity);
                    $sheet->setCellValue("D{$currentRow}", 'Rp ' . number_format($sellingPrice, 0, ',', '.'));
                    $sheet->setCellValue("E{$currentRow}", 'Rp ' . number_format($jumlah, 0, ',', '.'));

                    $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $itemEndRow = $currentRow;

                $sheet->getStyle("A{$itemStartRow}:E{$itemEndRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:D{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Jumlah');
                $sheet->setCellValue("E{$currentRow}", 'Rp ' . number_format($totalAmount, 0, ',', '.'));

                $sheet->getStyle("A{$currentRow}:E{$currentRow}")->applyFromArray([
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
                    ],
                ]);
                $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Terbilang : ' . ucwords(terbilang($totalAmount)) . ' rupiah');
                $sheet->getStyle("A{$currentRow}")->getFont()->setItalic(true);

                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Demikian invoice item ini kami buat atas perhatian dan kerjasamanya kami ucapkan terima kasih.');
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true);

                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Hormat Kami,');

                $currentRow += 4;
                $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'PT AGHITSNA KARYA INDAH');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
            },
        ];
    }
}