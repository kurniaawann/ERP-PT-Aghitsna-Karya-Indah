<?php

namespace Database\Seeders;

use App\Models\Finance\InvoiceAlumunium;
use Illuminate\Database\Seeder;

class AlumuniumInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Bersihkan data lama agar tidak bentrok invoice_number (primary key)
        InvoiceAlumunium::truncate();

        $invoices = [
            [
                'invoice_number' => '1/1/ALU/25',
                'invoice_date' => '2025-10-09',
                'recipient' => 'Proyek Karbela 3 / Pak Sis',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Karbela 3 / Pak Sis',
                'items' => json_encode([['keterangan' => 'Kusen', 'volume' => 242.2, 'satuan' => 'm3', 'harga' => 23000]]),
                'total_amount' => 5570600,
            ],
            [
                'invoice_number' => '2/2/ALU/25',
                'invoice_date' => '2025-09-20',
                'recipient' => 'Klien A',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Gedung Perkantoran',
                'items' => json_encode([['keterangan' => 'Kusen Aluminium', 'volume' => 100, 'satuan' => 'm3', 'harga' => 25000]]),
                'total_amount' => 2500000,
            ],
            [
                'invoice_number' => '3/3/ALU/25',
                'invoice_date' => '2025-10-15',
                'recipient' => 'Proyek Gedung B',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Gedung B',
                'items' => json_encode([['keterangan' => 'Daun Jendela', 'volume' => 28, 'satuan' => 'unit', 'harga' => 150000]]),
                'total_amount' => 4200000,
            ],
            [
                'invoice_number' => '4/4/ALU/25',
                'invoice_date' => '2025-10-20',
                'recipient' => 'Proyek Pabrik C',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Pabrik C',
                'items' => json_encode([['keterangan' => 'Daun Pintu HPL', 'volume' => 22, 'satuan' => 'unit', 'harga' => 375000]]),
                'total_amount' => 8250000,
            ],
            [
                'invoice_number' => '5/5/ALU/25',
                'invoice_date' => '2025-10-25',
                'recipient' => 'Proyek Renovasi D',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Renovasi D',
                'items' => json_encode([['keterangan' => 'Jasa Pasang Pintu', 'volume' => 22, 'satuan' => 'unit', 'harga' => 100000]]),
                'total_amount' => 2200000,
            ],
            [
                'invoice_number' => '6/6/ALU/25',
                'invoice_date' => '2025-09-25',
                'recipient' => 'Klien E',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Kantor E',
                'items' => json_encode([['keterangan' => 'Bouvelli', 'volume' => 3, 'satuan' => 'unit', 'harga' => 150000]]),
                'total_amount' => 450000,
            ],
            [
                'invoice_number' => '7/7/ALU/25',
                'invoice_date' => '2025-09-30',
                'recipient' => 'Proyek Mall F',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Mall F',
                'items' => json_encode([['keterangan' => 'Kisi - Kisi', 'volume' => 3, 'satuan' => 'unit', 'harga' => 120000]]),
                'total_amount' => 360000,
            ],
            [
                'invoice_number' => '8/8/ALU/25',
                'invoice_date' => '2025-10-05',
                'recipient' => 'Proyek Hotel G',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Hotel G',
                'items' => json_encode([['keterangan' => 'Daun Pintu Kaca', 'volume' => 2, 'satuan' => 'unit', 'harga' => 350000]]),
                'total_amount' => 700000,
            ],
            [
                'invoice_number' => '9/9/ALU/25',
                'invoice_date' => '2025-10-10',
                'recipient' => 'Proyek Rumah Sakit H',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Rumah Sakit H',
                'items' => json_encode([['keterangan' => 'Jasa Sealant', 'volume' => 23, 'satuan' => 'unit', 'harga' => 30000]]),
                'total_amount' => 690000,
            ],
            [
                'invoice_number' => '10/10/ALU/25',
                'invoice_date' => '2025-10-12',
                'recipient' => 'Proyek Sekolah I',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Sekolah I',
                'items' => json_encode([['keterangan' => 'Kusen Aluminium Putih', 'volume' => 80, 'satuan' => 'm3', 'harga' => 28000]]),
                'total_amount' => 2240000,
            ],
            [
                'invoice_number' => '11/11/ALU/25',
                'invoice_date' => '2025-10-14',
                'recipient' => 'Klien J',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Café J',
                'items' => json_encode([['keterangan' => 'Pintu Geser Aluminium', 'volume' => 5, 'satuan' => 'unit', 'harga' => 400000]]),
                'total_amount' => 2000000,
            ],
            [
                'invoice_number' => '12/12/ALU/25',
                'invoice_date' => '2025-10-18',
                'recipient' => 'Proyek Showroom K',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Showroom K',
                'items' => json_encode([['keterangan' => 'Jendela Lipat Aluminium', 'volume' => 10, 'satuan' => 'unit', 'harga' => 180000]]),
                'total_amount' => 1800000,
            ],
            [
                'invoice_number' => '13/13/ALU/25',
                'invoice_date' => '2025-09-15',
                'recipient' => 'Proyek Gudang L',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Gudang L',
                'items' => json_encode([['keterangan' => 'Pintu Gudang Besar', 'volume' => 4, 'satuan' => 'unit', 'harga' => 500000]]),
                'total_amount' => 2000000,
            ],
            [
                'invoice_number' => '14/14/ALU/25',
                'invoice_date' => '2025-09-18',
                'recipient' => 'Proyek Cabang M',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Kantor Cabang M',
                'items' => json_encode([['keterangan' => 'Partisi Aluminium', 'volume' => 120, 'satuan' => 'm2', 'harga' => 22000]]),
                'total_amount' => 2640000,
            ],
            [
                'invoice_number' => '15/15/ALU/25',
                'invoice_date' => '2025-09-22',
                'recipient' => 'Proyek Perumahan N',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Perumahan N',
                'items' => json_encode([['keterangan' => 'Kusen & Jasa Pasang', 'volume' => 150, 'satuan' => 'm3', 'harga' => 20000]]),
                'total_amount' => 3000000,
            ],
        ];

        foreach ($invoices as $invoice) {
            InvoiceAlumunium::create($invoice);
        }
    }
}
