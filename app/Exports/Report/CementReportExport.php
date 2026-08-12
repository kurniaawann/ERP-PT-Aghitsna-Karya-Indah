<?php

namespace App\Exports\Report;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;

/**
 * Export Laporan Semen ke format Excel (.xlsx).
 *
 * Menghasilkan file Excel berisi laporan lengkap gabungan DO Semen (header)
 * dan baris-baris Data Semen (detail) beserta subtotal dan total keseluruhan.
 */
class CementReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $deliveryOrders;
    protected $periodTitle;

    public function __construct($deliveryOrders, $periodTitle)
    {
        $this->deliveryOrders = $deliveryOrders;
        $this->periodTitle = $periodTitle;
    }

    public function collection(): Collection
    {
        $rows = collect();

        $grandVolume = 0;
        $grandSubtotal = 0;
        $grandModal = 0;
        $grandProfit = 0;

        foreach ($this->deliveryOrders as $do) {
            // Baris header DO
            $rows->push([
                $do->no,
                $do->tanggal ? $do->tanggal->format('d M Y') : '-',
                'Datang: ' . ($do->tanggal_datang?->format('d M Y') ?? '-') . ' | Bayar: ' . ($do->tanggal_bayar?->format('d M Y') ?? '-'),
                '',
                '',
                '',
                '',
                'Rp' . number_format($do->subtotal, 0, ',', '.'),
                '-',
                'Rp' . number_format($do->harga_modal, 0, ',', '.'),
                'Rp' . number_format($do->profit, 0, ',', '.'),
            ]);

            if ($do->cements->isEmpty()) {
                $rows->push(['', '', 'Tidak ada data semen', '', '', '', '', '', '', '', '']);
            }

            foreach ($do->cements as $cement) {
                $rows->push([
                    $cement->no,
                    $cement->tanggal ? $cement->tanggal->format('d M Y') : '-',
                    $cement->nama_proyek,
                    $cement->jumlah,
                    $cement->satuan ?: 'zak',
                    'Rp' . number_format($cement->harga, 0, ',', '.'),
                    'Rp' . number_format($cement->total, 0, ',', '.'),
                    '',
                    $cement->tanggal_lunas ? $cement->tanggal_lunas->format('d M Y') : '-',
                    '',
                    'Rp' . number_format($cement->profit, 0, ',', '.'),
                ]);
            }

            // Baris subtotal DO
            $rows->push([
                'SUBTOTAL', '', '', '', 'Rp' . number_format($do->total_volume, 0, ',', '.'), '', '',
                'Rp' . number_format($do->subtotal, 0, ',', '.'),
                '', 'Rp' . number_format($do->harga_modal, 0, ',', '.'), 'Rp' . number_format($do->profit, 0, ',', '.'),
            ]);
            $rows->push([]); // baris pemisah kosong

            $grandVolume += $do->total_volume;
            $grandSubtotal += $do->subtotal;
            $grandModal += $do->harga_modal;
            $grandProfit += $do->profit;
        }

        // Baris total keseluruhan
        $rows->push([
            'TOTAL', '', '', '', number_format($grandVolume, 0, ',', '.') . ' zak', '', '',
            'Rp' . number_format($grandSubtotal, 0, ',', '.'),
            '', 'Rp' . number_format($grandModal, 0, ',', '.'), 'Rp' . number_format($grandProfit, 0, ',', '.'),
        ]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['LAPORAN SEMEN'],
            [$this->periodTitle],
            [
                'No DO / Baris',
                'Tanggal',
                'Nama Proyek',
                'Volume',
                'Satuan',
                'Harga',
                'Jumlah',
                'Total',
                'Tgl Lunas',
                'Harga Modal',
                'Profit',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->mergeCells('A2:K2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle('A3:K3')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '9EA974']],
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);

        $sheet->getStyle('A4:K' . $highestRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        for ($row = 4; $row <= $highestRow; $row++) {
            $cellA = $sheet->getCell('A' . $row)->getValue();
            $cellC = $sheet->getCell('C' . $row)->getValue();
            $isDoHeader = $cellC === '' && (string) $cellA !== '';
            $isSubtotal = strtoupper((string) $cellA) === 'SUBTOTAL';
            $isGrandTotal = strtoupper((string) $cellA) === 'TOTAL';

            if ($isGrandTotal) {
                $sheet->mergeCells('A' . $row . ':G' . $row);
                $sheet->setCellValue('A' . $row, 'TOTAL');
                $sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5C327']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            } elseif ($isDoHeader || $isSubtotal) {
                $sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isSubtotal ? 'FFFF99' : 'DDDDDD']],
                ]);
            }
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 15,
            'C' => 30,
            'D' => 12,
            'E' => 12,
            'F' => 18,
            'G' => 18,
            'H' => 18,
            'I' => 15,
            'J' => 18,
            'K' => 18,
        ];
    }

    public function title(): string
    {
        return 'Laporan_Semen';
    }
}
