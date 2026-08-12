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

class CementReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $deliveryOrders;
    protected $periodTitle;

    // Menampung indeks baris untuk styling dinamis
    protected array $doHeaderRows = [];
    protected array $subtotalRows = [];
    protected array $yellowRows = [];
    protected array $customMerges = [];

    public function __construct($deliveryOrders, $periodTitle = '2026')
    {
        $this->deliveryOrders = $deliveryOrders;
        $this->periodTitle = $periodTitle;
    }

    public function collection(): Collection
    {
        $rows = collect();
        $currentRow = 3; // Baris 1: Judul, Baris 2: Header Tabel, Baris 3: Mulai Data

        $doIndex = 1;
        foreach ($this->deliveryOrders as $do) {
            if (!$do->cements || $do->cements->isEmpty()) {
                continue;
            }

            // 1. Baris Header DO (Teks Merah di Tengah)
            $rows->push([
                'DO KE ' . $doIndex,
                '', '', '', '', '', '', '', '', ''
            ]);
            $this->doHeaderRows[] = $currentRow;
            $currentRow++;

            // 2. Baris Detail Items Semen
            $startItemRow = $currentRow;
            $tanggalDatang = $do->tanggal_datang ? \Carbon\Carbon::parse($do->tanggal_datang)->format('d.m.y') : '-';

            foreach ($do->cements as $index => $cement) {
                $tglLunas = $cement->tanggal_lunas ?? $cement->tgl_lunas ?? null;
                $tglLunasFormatted = $tglLunas ? \Carbon\Carbon::parse($tglLunas)->format('d/m/Y') : '-';

                $rows->push([
                    $index + 1,
                    $tanggalDatang,
                    strtoupper($cement->nama_proyek),
                    $cement->jumlah,
                    'ZAK',
                    $cement->harga,
                    $cement->total,
                    $tglLunasFormatted,
                    '', // Dikosongkan di baris detail
                    '', // Dikosongkan di baris detail
                ]);

                // Highlight warna kuning jika nama proyek GENTENG IJO
                if (strtolower($cement->nama_proyek) === 'genteng ijo') {
                    $this->yellowRows[] = $currentRow;
                }

                $currentRow++;
            }
            $endItemRow = $currentRow - 1;

            // Merge Vertikal untuk Kolom Tanggal per DO
            if ($endItemRow >= $startItemRow) {
                $this->customMerges[] = "B{$startItemRow}:B{$endItemRow}";
                // Merge Kolom HARGA MODAL & PROFIT (I & J) pada baris item detail
                $this->customMerges[] = "I{$startItemRow}:J{$endItemRow}";
            }

            // 3. Baris Subtotal DO
            $subtotalVolume = $do->cements->sum('jumlah');
            $subtotalJumlah = $do->subtotal ?? $do->cements->sum('total');

            $rows->push([
                '',
                '',
                '',
                $subtotalVolume,
                '',
                '',
                $subtotalJumlah,
                '',
                $do->harga_modal,
                $do->profit
            ]);

            $this->subtotalRows[] = $currentRow;

            // Merge Kolom SATUAN & HARGA (E & F) serta JUMLAH & TGL LUNAS (G & H) pada baris subtotal
            $this->customMerges[] = "E{$currentRow}:F{$currentRow}";
            $this->customMerges[] = "G{$currentRow}:H{$currentRow}";

            $currentRow++;
            $doIndex++;
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['LAPORAN SEMEN ' . $this->periodTitle],
            [
                'NO',
                'TANGGAL',
                'PROYEK',
                'VOLUME',
                'SATUAN',
                'HARGA',
                'JUMLAH',
                'TGL LUNAS',
                'HARGA MODAL',
                'PROFIT',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        // 1. Judul Laporan (A1:J1)
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // 2. Header Tabel (A2:J2)
        $sheet->getStyle('A2:J2')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
            'font' => ['bold' => true, 'size' => 8.5, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);

        // 3. Style Dasar Sel Data
        $sheet->getStyle('A3:J' . $highestRow)->applyFromArray([
            'font' => ['size' => 8.5, 'name' => 'Arial'],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Format Rata Tengah Kolom Standar
        $sheet->getStyle('A3:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B3:B' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D3:D' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E3:E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H3:H' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Format Angka & Mata Uang Rp (Menggunakan format bawaan Excel)
        $currencyFormat = '"Rp"#,##0';
        $numberFormat = '#,##0';
        $sheet->getStyle('D3:D' . $highestRow)->getNumberFormat()->setFormatCode($numberFormat);
        $sheet->getStyle('F3:G' . $highestRow)->getNumberFormat()->setFormatCode($currencyFormat);
        $sheet->getStyle('I3:J' . $highestRow)->getNumberFormat()->setFormatCode($currencyFormat);

        // 4. Jalankan Merge Cells Custom
        foreach ($this->customMerges as $range) {
            $sheet->mergeCells($range);
        }

        // 5. Header DO (Teks Merah di Tengah)
        foreach ($this->doHeaderRows as $row) {
            $sheet->mergeCells("A{$row}:J{$row}");
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FF0000']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // 6. Highlight Proyek GENTENG IJO (Latar Kuning)
        foreach ($this->yellowRows as $row) {
            $sheet->getStyle("A{$row}:J{$row}")->getFill()->applyFromArray([
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'],
            ]);
        }

        // 7. Baris Subtotal DO (Background Abu-abu & Teks Volume Merah)
        foreach ($this->subtotalRows as $row) {
            $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
                'font' => ['bold' => true],
            ]);

            // Teks Angka Volume Subtotal Merah & Bold
            $sheet->getStyle("D{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FF0000']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // NO
            'B' => 14,  // TANGGAL
            'C' => 28,  // PROYEK
            'D' => 10,  // VOLUME
            'E' => 10,  // SATUAN
            'F' => 16,  // HARGA
            'G' => 18,  // JUMLAH
            'H' => 14,  // TGL LUNAS
            'I' => 16,  // HARGA MODAL
            'J' => 16,  // PROFIT
        ];
    }

    public function title(): string
    {
        return 'Laporan_Semen';
    }
}