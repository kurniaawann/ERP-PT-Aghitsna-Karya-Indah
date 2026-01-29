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
        $invoices = [
            [
                'invoice_number' => '1/1/ALU/25',
                'invoice_date' => '2025-10-09',
                'recipient' => 'Proyek Karbela 3 / Pak Sis',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Karbela 3 / Pak Sis',
                'items' => json_encode([
                    [
                        'keterangan' => 'Kusen',
                        'volume' => 242.2,
                        'satuan' => 'm3',
                        'harga' => 23000,
                    ],
                    [
                        'keterangan' => 'Bouvelli',
                        'volume' => 3,
                        'satuan' => 'unit',
                        'harga' => 150000,
                    ],
                    [
                        'keterangan' => 'Kisi - Kisi',
                        'volume' => 3,
                        'satuan' => 'unit',
                        'harga' => 120000,
                    ],
                    [
                        'keterangan' => 'Daun Pintu Kaca',
                        'volume' => 2,
                        'satuan' => 'unit',
                        'harga' => 350000,
                    ],
                    [
                        'keterangan' => 'Daun Jendela',
                        'volume' => 28,
                        'satuan' => 'unit',
                        'harga' => 150000,
                    ],
                    [
                        'keterangan' => 'Daun Pintu HPL',
                        'volume' => 22,
                        'satuan' => 'unit',
                        'harga' => 375000,
                    ],
                    [
                        'keterangan' => 'Jasa Pasang Pintu HPL',
                        'volume' => 22,
                        'satuan' => 'unit',
                        'harga' => 100000,
                    ],
                    [
                        'keterangan' => 'Jasa Sealant Pintu Kamar Mandi',
                        'volume' => 23,
                        'satuan' => 'unit',
                        'harga' => 30000,
                    ],
                ]),
                'total_amount' => 22420600,
            ],
            [
                'invoice_number' => '2/2/ALU/25',
                'invoice_date' => '2025-09-20',
                'recipient' => 'Klien A',
                'regarding' => 'Penagihan Pembayaran',
                'project_description' => 'Proyek Gedung Perkantoran',
                'items' => json_encode([
                    [
                        'keterangan' => 'Kusen Aluminium',
                        'volume' => 100,
                        'satuan' => 'm3',
                        'harga' => 25000,
                    ],
                    [
                        'keterangan' => 'Jasa Pemasangan',
                        'volume' => 100,
                        'satuan' => 'm3',
                        'harga' => 15000,
                    ],
                ]),
                'total_amount' => 4000000,
            ],
        ];

        foreach ($invoices as $invoice) {
            InvoiceAlumunium::create($invoice);
        }
    }
}
