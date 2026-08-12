<?php

namespace App\Exports\Inventory;

use App\Models\Inventory\CementDeliveryOrder;
use Maatwebsite\Excel\Concerns\{
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    WithColumnWidths
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};

/**
 * Export DO Semen ke format Excel (.xlsx).
 *
 * Menghasilkan file Excel dengan header "DO SEMEN" berisi daftar
 * delivery order semen beserta nomor, tanggal, proyek, volume, satuan,
 * harga, jumlah, tanggal lunas, harga modal, dan profit.
 */
class CementDeliveryOrderExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths
{
    /**
     * Query data yang akan di-export.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return CementDeliveryOrder::orderBy('tanggal', 'asc')->orderBy('no', 'asc');
    }

    /**
     * Header kolom Excel.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            ['DO SEMEN', '', '', '', '', '', '', '', '', ''],
            ['No', 'Tanggal', 'Proyek', 'Volume', 'Satuan', 'Harga', 'Jumlah', 'Tgl Lunas', 'Harga Modal', 'Profit']
        ];
    }

    /**
     * Mapping data setiap baris DO Semen.
     *
     * @param  CementDeliveryOrder  $do
     * @return array
     */
    public function map($do): array
    {
        $harga = (int) $do->harga;
        $hargaModal = (int) $do->harga_modal;

        return [
            $do->no,
            $do->tanggal ? $do->tanggal->format('d M Y') : '-',
            $do->proyek,
            $do->volume,
            $do->satuan ?: '-',
            'Rp' . number_format($harga, 0, ',', '.'),
            'Rp' . number_format($do->jumlah, 0, ',', '.'),
            $do->tanggal_lunas ? $do->tanggal_lunas->format('d M Y') : '-',
            'Rp' . number_format($hargaModal, 0, ',', '.'),
            'Rp' . number_format($do->profit, 0, ',', '.'),
        ];
    }

    /**
     * Style header dan border untuk seluruh sheet.
     *
     * @param  Worksheet  $sheet
     * @return array
     */
    public function styles(Worksheet $sheet): array
    {
        // Header utama style (baris 1)
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'FFFF00',
                ],
            ],
        ]);

        $sheet->mergeCells('A1:J1');

        // Header kolom style (baris 2)
        $sheet->getStyle('A2:J2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E0E0E0',
                ],
            ],
        ]);

        // Border style untuk seluruh data
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:J' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        return [];
    }

    /**
     * Judul sheet Excel.
     *
     * @return string
     */
    public function title(): string
    {
        return 'DO_Semen';
    }

    /**
     * Lebar kolom Excel.
     *
     * @return array<string, int>
     */
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
            'H' => 15,
            'I' => 18,
            'J' => 18,
        ];
    }
}
