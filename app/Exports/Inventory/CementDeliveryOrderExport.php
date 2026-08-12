<?php

namespace App\Exports\Inventory;

use App\Models\Inventory\CementDeliveryOrder;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithStyles,
    WithTitle,
    WithColumnWidths
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use Illuminate\Support\Collection;

/**
 * Export DO Semen ke format Excel (.xlsx).
 *
 * Menghasilkan file Excel berisi laporan DO Semen (master-detail):
 * header DO (no, tanggal, tanggal datang, tanggal bayar, harga modal)
 * diikuti baris-baris Data Semen (proyek, volume, satuan, harga, jumlah,
 * tanggal lunas, harga modal, profit) dan baris subtotal per DO.
 */
class CementDeliveryOrderExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    /**
     * Kumpulan baris (array) yang akan ditulis ke Excel.
     *
     * @return Collection<int, array>
     */
    public function collection(): Collection
    {
        $rows = collect();

        CementDeliveryOrder::with('cements')
            ->orderBy('tanggal', 'asc')
            ->orderBy('no', 'asc')
            ->get()
            ->each(function (CementDeliveryOrder $do) use ($rows) {
                // Baris info header DO
                $rows->push([
                    $do->no_urutan,
                    $do->tanggal ? $do->tanggal->format('d M Y') : '-',
                    'Datang: ' . ($do->tanggal_datang?->format('d M Y') ?? '-') . ' | Bayar: ' . ($do->tanggal_bayar?->format('d M Y') ?? '-'),
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Rp' . number_format($do->harga_modal, 0, ',', '.'),
                    'Rp' . number_format($do->profit, 0, ',', '.'),
                ]);

                if ($do->cements->isEmpty()) {
                    $rows->push($this->emptyDataRow());
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
                        $cement->tanggal_lunas ? $cement->tanggal_lunas->format('d M Y') : '-',
                        '',
                        '',
                    ]);
                }

                // Baris subtotal DO
                $rows->push([
                    'SUBTOTAL', '', '', '', '', '', 'Rp' . number_format($do->subtotal, 0, ',', '.'),
                    '', 'Rp' . number_format($do->harga_modal, 0, ',', '.'), 'Rp' . number_format($do->profit, 0, ',', '.'),
                ]);
                $rows->push([]); // baris pemisah kosong
            });

        return $rows;
    }

    /**
     * Baris penanda DO tanpa data semen.
     *
     * @return array
     */
    private function emptyDataRow(): array
    {
        return ['', '', 'Tidak ada data semen', '', '', '', '', '', '', ''];
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
     * Style header dan border untuk seluruh sheet.
     *
     * @param  Worksheet  $sheet
     * @return array
     */
    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
        ]);
        $sheet->mergeCells('A1:J1');

        $sheet->getStyle('A2:J2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
        ]);

        for ($row = 3; $row <= $sheet->getHighestRow(); $row++) {
            $proyek = (string) $sheet->getCell('C' . $row)->getValue();
            $isDoHeader = $proyek === '' && (string) $sheet->getCell('A' . $row)->getValue() !== '';
            $isSubtotal = strtoupper((string) $sheet->getCell('A' . $row)->getValue()) === 'SUBTOTAL';
            if ($isDoHeader || $isSubtotal) {
                $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isSubtotal ? 'FFFF99' : 'DDDDDD']],
                ]);
            }
        }

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:J' . $lastRow)->applyFromArray([
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
