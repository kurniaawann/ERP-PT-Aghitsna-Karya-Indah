<?php

namespace App\Exports\Administrasi;

use App\Models\Administrasi\RAB;
use App\Models\Finance\PaymentAccount;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class RABExport implements FromCollection, WithEvents, WithTitle, WithColumnWidths
{
    protected RAB $rab;

    public function __construct(string $rabNumber)
    {
        $this->rab = RAB::with(['categories.subcategories.items', 'miscellaneousCosts'])
            ->where('rab_number', $rabNumber)
            ->firstOrFail();
    }

    public function collection()
    {
        return collect([]);
    }

    public function title(): string
    {
        return 'RAB_' . $this->rab->rab_number;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 38,
            'C' => 10,
            'D' => 12,
            'E' => 14,
            'F' => 16,
            'G' => 16,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $rab = $this->rab;

                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(10);

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

                $sheet->mergeCells('A1:D1');
                $sheet->mergeCells('E1:G1');

                $sheet->mergeCells('A2:D2');
                $sheet->setCellValue('A2', 'PT. AGHITSNA KARYA INDAH');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14)->setColor(new Color('FF6600'));

                $sheet->mergeCells('A3:D3');
                $sheet->setCellValue('A3', 'AGHITSNA ALUMUNIUM DAN BAJA RINGAN');

                $sheet->mergeCells('A4:D4');
                $sheet->setCellValue('A4', 'JL. CEMARA RT 02 RW 07, KEL, GROGOL');

                $sheet->mergeCells('A5:D5');
                $sheet->setCellValue('A5', 'KEC. LIMO KOTA DEPOK');

                $sheet->mergeCells('A6:D6');
                $sheet->setCellValue('A6', 'Telp. 0882 1303 1263 / 0882 1303 1264 | Email: Design@aghitsna.id');

                $sheet->mergeCells('E1:G1');
                $sheet->setCellValue('E1', 'RENCANA ANGGARAN BIAYA');
                $sheet->getStyle('E1')->getFont()->setBold(true)->setSize(18)->setColor(new Color('000000'));
                $sheet->getStyle('E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->setCellValue('E2', 'To');
                $sheet->setCellValue('F2', ': ' . $rab->recipient);

                $sheet->setCellValue('E3', 'No.');
                $sheet->setCellValue('F3', ': ' . $rab->rab_number);

                $sheet->setCellValue('E4', 'Tanggal');
                $sheet->setCellValue('F4', ': ' . Carbon::parse($rab->date)->format('d F Y'));

                $sheet->getStyle('A1:G6')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK);

                $currentRow = 8;
                $sheet->mergeCells("A{$currentRow}:G{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Dengan Hormat,');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:G{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", $rab->intro_text);
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true);

                $currentRow += 2;
                $sheet->setCellValue("A{$currentRow}", 'NO');
                $sheet->setCellValue("B{$currentRow}", 'JENIS PEKERJAAN');
                $sheet->setCellValue("C{$currentRow}", 'VOL');
                $sheet->setCellValue("D{$currentRow}", 'SATUAN');
                $sheet->setCellValue("E{$currentRow}", 'HARGA');
                $sheet->setCellValue("F{$currentRow}", 'SUB HARGA');
                $sheet->setCellValue("G{$currentRow}", 'JUMLAH');

                $headerEndRow = $currentRow + 1;
                $sheet->mergeCells("E{$currentRow}:F{$currentRow}");
                $sheet->mergeCells("A{$currentRow}:A{$headerEndRow}");
                $sheet->mergeCells("B{$currentRow}:B{$headerEndRow}");
                $sheet->mergeCells("C{$currentRow}:C{$headerEndRow}");
                $sheet->mergeCells("D{$currentRow}:D{$headerEndRow}");
                $sheet->mergeCells("G{$currentRow}:G{$headerEndRow}");
                $sheet->setCellValue('E' . $headerEndRow, 'SATUAN');
                $sheet->setCellValue('F' . $headerEndRow, 'SUB HARGA');

                $sheet->getStyle("A{$currentRow}:G{$headerEndRow}")->applyFromArray([
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
                        'wrapText' => true,
                    ],
                ]);

                $currentRow += 2;

                $arabicToRoman = function ($num) {
                    $map = [
                        'M' => 1000,
                        'CM' => 900,
                        'D' => 500,
                        'CD' => 400,
                        'C' => 100,
                        'XC' => 90,
                        'L' => 50,
                        'XL' => 40,
                        'X' => 10,
                        'IX' => 9,
                        'V' => 5,
                        'IV' => 4,
                        'I' => 1,
                    ];

                    $returnValue = '';
                    while ($num > 0) {
                        foreach ($map as $roman => $int) {
                            if ($num >= $int) {
                                $num -= $int;
                                $returnValue .= $roman;
                                break;
                            }
                        }
                    }

                    return $returnValue;
                };

                $grandTotal = 0;

                foreach ($rab->categories as $category) {
                    $categoryRoman = $arabicToRoman((int) $category->roman_order);
                    $categoryTotal = 0;

                    $sheet->mergeCells("A{$currentRow}:G{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", $categoryRoman . '. ' . $category->category_name);
                    $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'D9D9D9'],
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);

                    $currentRow++;

                    foreach ($category->subcategories as $subcategory) {
                        $subcategoryTotal = 0;

                        $sheet->mergeCells("A{$currentRow}:G{$currentRow}");
                        $sheet->setCellValue("A{$currentRow}", $subcategory->number_order . '. ' . $subcategory->subcategory_name);
                        $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F0F0F0'],
                            ],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                            ],
                        ]);

                        $currentRow++;

                        foreach ($subcategory->items as $item) {
                            $itemVolume = $item->volume ?? null;
                            $itemUnit = $item->unit ?? null;
                            $itemPrice = (int) ($item->unit_price ?? 0);
                            $itemSubtotal = (int) ($item->sub_harga ?? (($itemVolume && $itemPrice) ? $itemVolume * $itemPrice : 0));
                            $subcategoryTotal += $itemSubtotal;

                            $sheet->setCellValue("A{$currentRow}", '');
                            $sheet->setCellValue("B{$currentRow}", chr(96 + (int) $item->letter_order) . '. ' . $item->item_description);
                            $sheet->setCellValue("C{$currentRow}", $itemVolume !== null ? $itemVolume : '');
                            $sheet->setCellValue("D{$currentRow}", $itemUnit ?: '');
                            $sheet->setCellValue("E{$currentRow}", $itemPrice > 0 ? 'Rp ' . number_format($itemPrice, 0, ',', '.') : '');
                            $sheet->setCellValue("F{$currentRow}", $itemSubtotal > 0 ? 'Rp ' . number_format($itemSubtotal, 0, ',', '.') : '');
                            $sheet->setCellValue("G{$currentRow}", '');

                            $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                                'borders' => [
                                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                                ],
                            ]);
                            $sheet->getStyle("B{$currentRow}")->getFont()->setItalic(true)->getColor()->setRGB('555555');
                            $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                            $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                            $currentRow++;
                        }

                        if ($subcategoryTotal === 0) {
                            $subcategoryTotal = (int) ($subcategory->sub_harga ?? 0);
                        }

                        $categoryTotal += $subcategoryTotal;

                        $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                        $sheet->setCellValue("A{$currentRow}", 'Subtotal');
                        $sheet->setCellValue("G{$currentRow}", 'Rp ' . number_format($subcategoryTotal, 0, ',', '.'));
                        $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'E8E8E8'],
                            ],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                            ],
                        ]);
                        $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                        $currentRow++;
                    }

                    if ($category->subcategories->count() > 0) {
                        $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                        $sheet->setCellValue("A{$currentRow}", 'Subtotal Kategori');
                        $sheet->setCellValue("G{$currentRow}", 'Rp ' . number_format($categoryTotal, 0, ',', '.'));
                        $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'D9D9D9'],
                            ],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                            ],
                        ]);
                        $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                        $grandTotal += $categoryTotal;
                        $currentRow++;
                    }
                }

                $miscCostsTotal = $rab->miscellaneousCosts->sum('amount');
                $totalAnggaranBiaya = $grandTotal + $miscCostsTotal;

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'I. Jumlah Anggaran Bangunan');
                $sheet->setCellValue("G{$currentRow}", 'Rp ' . number_format($grandTotal, 0, ',', '.'));
                $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);
                $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'II. Biaya Lain-Lain');
                $sheet->setCellValue("G{$currentRow}", 'Rp ' . number_format($miscCostsTotal, 0, ',', '.'));
                $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);
                $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                foreach ($rab->miscellaneousCosts as $miscCost) {
                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", '    ' . $miscCost->item_order . '. ' . $miscCost->item_name);
                    $sheet->setCellValue("G{$currentRow}", 'Rp ' . number_format($miscCost->amount, 0, ',', '.'));
                    $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);
                    $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'TOTAL ANGGARAN BIAYA');
                $sheet->setCellValue("G{$currentRow}", 'Rp ' . number_format($totalAnggaranBiaya, 0, ',', '.'));
                $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);
                $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                if (!auth()->user() || auth()->user()->role !== 'admin') {
                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", 'TERBILANG');
                    $amountInWords = $rab->amount_in_words ?? ucwords(terbilang($totalAnggaranBiaya)) . ' rupiah';
                    $sheet->setCellValue("G{$currentRow}", $amountInWords);
                    $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true, 'italic' => true],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);
                }

                if (auth()->user() && auth()->user()->role === 'superadmin') {
                    $incomingPayment = $rab->incoming_payment ?? 0;
                    $sisaPembayaran = $totalAnggaranBiaya - $incomingPayment;

                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", 'UANG MASUK (DP)');
                    $sheet->setCellValue("G{$currentRow}", 'Rp ' . number_format($incomingPayment, 0, ',', '.'));
                    $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E8F5E9'],
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);
                    $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", 'SISA PEMBAYARAN');
                    $sheet->setCellValue("G{$currentRow}", 'Rp ' . number_format($sisaPembayaran, 0, ',', '.'));
                    $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFE0B2'],
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);
                    $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    $currentRow++;
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", 'TERBILANG');
                    $sheet->setCellValue("G{$currentRow}", ucwords(terbilang($sisaPembayaran)) . ' rupiah');
                    $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true, 'italic' => true],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);
                }

                if (!auth()->user() || auth()->user()->role !== 'admin') {

                    $currentRow += 2;
                    $sheet->mergeCells("A{$currentRow}:G{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", 'Pembayaran dapat ditransfer melalui nomor rekening :');
                    $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);

                    $accountIds = [];
                    $selectedPaymentAccounts = $rab->selected_payment_accounts;

                    if (is_array($selectedPaymentAccounts)) {
                        $accountIds = $selectedPaymentAccounts;
                    } else {
                        $selectedPaymentAccounts = is_string($selectedPaymentAccounts) ? $selectedPaymentAccounts : '';
                        $accountIds = json_decode($selectedPaymentAccounts, true) ?: [];
                    }

                    $accounts = [];
                    if (is_array($accountIds) && count($accountIds) > 0) {
                        $accounts = PaymentAccount::whereIn('id', $accountIds)->orderBy('id')->get();
                    }

                    if (count($accounts) > 0) {
                        foreach ($accounts as $account) {
                            $currentRow++;
                            $sheet->mergeCells("A{$currentRow}:G{$currentRow}");
                            $sheet->setCellValue("A{$currentRow}", "Bank {$account->bank_name} / No : {$account->account_number} a/n {$account->account_holder}");
                            $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                                'borders' => [
                                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                                ],
                            ]);
                        }
                    } else {
                        $currentRow++;
                        $sheet->mergeCells("A{$currentRow}:G{$currentRow}");
                        $sheet->setCellValue("A{$currentRow}", 'Tidak ada rekening pembayaran yang dipilih');
                        $sheet->getStyle("A{$currentRow}")->getFont()->setItalic(true);
                    }
                }

                $currentRow += 2;
                $sheet->mergeCells("A{$currentRow}:G{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'Demikian rencana anggaran biaya ini kami sampaikan, atas perhatian dan kerjasamanya kami ucapkan terima kasih.');
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true);

                $currentRow += 2;
                $sheet->mergeCells("E{$currentRow}:G{$currentRow}");
                $sheet->setCellValue("E{$currentRow}", 'Hormat Kami,');
                $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $currentRow += 3;
                $sheet->mergeCells("E{$currentRow}:G{$currentRow}");
                $sheet->setCellValue("E{$currentRow}", 'PT. AGHITSNA KARYA INDAH');
                $sheet->getStyle("E{$currentRow}")->getFont()->setBold(true);
                $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $currentRow += 1;
                $sheet->mergeCells("E{$currentRow}:G{$currentRow}");
                $sheet->setCellValue("E{$currentRow}", $rab->signed_by);
                $sheet->getStyle("E{$currentRow}")->getFont()->setBold(true)->setUnderline(true);
                $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $currentRow += 1;
                $sheet->mergeCells("E{$currentRow}:G{$currentRow}");
                $sheet->setCellValue("E{$currentRow}", $rab->division);
                $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("A1:G{$currentRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}