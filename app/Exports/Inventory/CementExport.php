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
 * pembelian semen beserta DO, tanggal, nama proyek, jumlah sak, satuan,
 * harga per sak, total, dan tanggal lunas.
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
            ['DATA SEMEN', '', '', '', '', '', '', ''],
            ['No', 'DO', 'Tanggal', 'Nama Proyek', 'Jumlah', 'Satuan', 'Harga', 'Total', 'Tanggal Lunas']
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
            $cement->do_no ?: '-',
            $cement->tanggal ? $cement->tanggal->format('d M Y') : '-',
            $cement->nama_proyek,
            $jumlah,
            $cement->satuan ?: 'zak',
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
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
        ]);
        $sheet->mergeCells('A1:I1');

        $sheet->getStyle('A2:I2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
        ]);

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:I' . $lastRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
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
            'B' => 12,
            'C' => 15,
            'D' => 30,
            'E' => 12,
            'F' => 12,
            'G' => 18,
            'H' => 18,
            'I' => 15,
        ];
    }
}
