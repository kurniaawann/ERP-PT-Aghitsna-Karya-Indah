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
 * Export Delivery Order ke format Excel (.xlsx).
 *
 * Menghasilkan file Excel berisi laporan Delivery Order 4 kolom yang dikelompokkan per bulan,
 * dengan styling dan layout yang persis sama seperti dokumen PDF/HTML.
 */
class CementDeliveryOrderExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    protected $groupedDeliveryOrders;

    /**
     * Menampung indeks baris untuk styling dinamis
     */
    protected array $monthHeaderRows = [];
    protected array $emptyRows = [];

    /**
     * Constructor menerima data DO yang sudah digroup per bulan, 
     * atau melakukan query dan grouping otomatis jika null.
     */
    public function __construct($groupedDeliveryOrders = null)
    {
        if ($groupedDeliveryOrders) {
            $this->groupedDeliveryOrders = $groupedDeliveryOrders;
        } else {
            $orders = CementDeliveryOrder::orderBy('tanggal', 'asc')->get();
            $this->groupedDeliveryOrders = $orders->groupBy(function ($do) {
                return $do->tanggal ? $do->tanggal->translatedFormat('F Y') : 'TANPA TANGGAL';
            });
        }
    }

    /**
     * Kumpulan baris (array) yang akan ditulis ke Excel.
     *
     * @return Collection<int, array>
     */
    public function collection(): Collection
    {
        $rows = collect();
        $currentRow = 3; // Baris 1: Judul Laporan, Baris 2: Header Tabel, Baris 3+: Data

        foreach ($this->groupedDeliveryOrders as $month => $orders) {
            // 1. Baris Header Bulan (Merge A-D, Teks Merah)
            $rows->push([
                strtoupper($month), '', '', ''
            ]);
            $this->monthHeaderRows[] = $currentRow;
            $currentRow++;

            // 2. Baris Data DO
            if ($orders->isEmpty()) {
                $rows->push(['Tidak ada data DO.', '', '', '']);
                $this->emptyRows[] = $currentRow;
                $currentRow++;
            } else {
                foreach ($orders as $do) {
                    $rows->push([
                        $do->no_urutan,
                        $do->tanggal ? $do->tanggal->format('d.m.Y') : '-',
                        $do->tanggal_datang ? $do->tanggal_datang->format('d.m.Y') : '-',
                        $do->tanggal_bayar ? $do->tanggal_bayar->format('d.m.Y') : '-',
                    ]);
                    $currentRow++;
                }
            }
        }

        return $rows;
    }

    /**
     * Header kolom Excel.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            ['DELIVERY ORDER', '', '', ''],
            ['No', 'Tanggal DO', 'Tanggal Datang', 'Tanggal Bayar']
        ];
    }

    /**
     * Style header, warna, font, dan border untuk seluruh sheet.
     *
     * @param  Worksheet  $sheet
     * @return array
     */
    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        // 1. Mengatur font global sheet menjadi Times New Roman (sesuai CSS HTML)
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman');

        // 2. Judul Laporan (A1:D1) - Bold 15pt, Rata Tengah
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 15],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // 3. Header Tabel (A2:D2) - Background #8EA9DB, Bold 11pt, Rata Tengah
        $sheet->getStyle('A2:D2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '8EA9DB']],
        ]);

        // 4. Style Dasar Sel Data (Rata Tengah)
        $sheet->getStyle('A3:D' . $highestRow)->applyFromArray([
            'font' => ['size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // 5. Header Bulan (Merge A-D, Teks Merah #FF0000, Bold 12pt)
        foreach ($this->monthHeaderRows as $row) {
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FF0000']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // 6. Baris Kosong (Merge A-D)
        foreach ($this->emptyRows as $row) {
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // 7. Border seluruh tabel dari Header (A2) sampai baris terakhir
        $sheet->getStyle('A2:D' . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
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
        return 'Delivery_Order';
    }

    /**
     * Lebar kolom Excel.
     *
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 12, // No
            'B' => 25, // Tanggal DO
            'C' => 25, // Tanggal Datang
            'D' => 25, // Tanggal Bayar
        ];
    }
}