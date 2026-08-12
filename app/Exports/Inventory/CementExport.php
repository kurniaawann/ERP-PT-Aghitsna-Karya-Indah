<?php

namespace App\Exports\Inventory;

use App\Models\Inventory\Cement;
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
 * Export Data Semen ke format Excel (.xlsx).
 *
 * Menghasilkan file Excel dengan header "DATA SEMEN" berisi daftar
 * pembelian semen beserta tanggal, nama proyek, jumlah sak, harga per sak,
 * total (harga per sak x jumlah sak), dan tanggal lunas.
 */
class CementExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths
{
    /**
     * Query data yang akan di-export.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return Cement::orderBy('tanggal', 'asc')->orderBy('no', 'asc');
    }

    /**
     * Header kolom Excel.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            ['DATA SEMEN', '', '', '', '', '', ''],
            ['No', 'Tanggal', 'Nama Proyek', 'Jumlah', 'Harga', 'Total', 'Tanggal Lunas']
        ];
    }

    /**
     * Mapping data setiap baris semen.
     *
     * @param  Cement  $cement
     * @return array
     */
    public function map($cement): array
    {
        $jumlah = (int) $cement->jumlah;
        $harga = (int) $cement->harga;
        $total = $jumlah * $harga;

        return [
            $cement->no,
            $cement->tanggal ? $cement->tanggal->format('d M Y') : '-',
            $cement->nama_proyek,
            $jumlah,
            'Rp' . number_format($harga, 0, ',', '.'),
            'Rp' . number_format($total, 0, ',', '.'),
            $cement->tanggal_lunas ? $cement->tanggal_lunas->format('d M Y') : '-',
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
        $sheet->getStyle('A1:G1')->applyFromArray([
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

        $sheet->mergeCells('A1:G1');

        // Header kolom style (baris 2)
        $sheet->getStyle('A2:G2')->applyFromArray([
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
        $sheet->getStyle('A1:G' . $lastRow)->applyFromArray([
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
        return 'Data_Semen';
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
            'E' => 18,
            'F' => 18,
            'G' => 15,
        ];
    }
}
