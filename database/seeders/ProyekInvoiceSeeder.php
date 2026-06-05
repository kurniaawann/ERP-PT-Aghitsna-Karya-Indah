<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProyekInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $invoices = [
            [
                'invoice_number' => 'P-2026-001',
                'invoice_date' => now()->format('Y-m-d'),
                'recipient' => 'PT. Cahaya Sentosa',
                'regarding' => 'Pembelian material dan jasa proyek',
                'project_description' => 'Pemasangan kusen dan pintu aluminium untuk proyek Gedung A',
                'items' => json_encode([['description' => 'Pintu Aluminium 2x4 m', 'volume' => 2, 'unit' => 'unit', 'unit_price' => 7500000, 'total_price' => 15000000]]),
                'total_amount' => 15000000,
            ],
            [
                'invoice_number' => 'P-2026-002',
                'invoice_date' => now()->subDays(2)->format('Y-m-d'),
                'recipient' => 'CV. Mitra Jaya',
                'regarding' => 'Jasa tenaga kerja proyek',
                'project_description' => 'Pekerjaan finishing proyek Pabrik B',
                'items' => json_encode([['description' => 'Upah kerja tenaga terampil', 'volume' => 50, 'unit' => 'HK', 'unit_price' => 200000, 'total_price' => 10000000]]),
                'total_amount' => 10000000,
            ],
            [
                'invoice_number' => 'P-2026-003',
                'invoice_date' => now()->subDays(4)->format('Y-m-d'),
                'recipient' => 'PT. Baja Konstruksi',
                'regarding' => 'Pembayaran material baja',
                'project_description' => 'Supply baja untuk proyek Renovasi C',
                'items' => json_encode([['description' => 'Baja WF 300x200', 'volume' => 10, 'unit' => 'ton', 'unit_price' => 8000000, 'total_price' => 80000000]]),
                'total_amount' => 80000000,
            ],
            [
                'invoice_number' => 'P-2026-004',
                'invoice_date' => now()->subDays(6)->format('Y-m-d'),
                'recipient' => 'CV. Catylac Pro',
                'regarding' => 'Supply cat dan finishing',
                'project_description' => 'Material finishing untuk proyek Kantor D',
                'items' => json_encode([['description' => 'Cat Catylac Grade A', 'volume' => 100, 'unit' => 'Liter', 'unit_price' => 150000, 'total_price' => 15000000]]),
                'total_amount' => 15000000,
            ],
            [
                'invoice_number' => 'P-2026-005',
                'invoice_date' => now()->subDays(8)->format('Y-m-d'),
                'recipient' => 'PT. Kaca Bersinar',
                'regarding' => 'Supply kaca temperatur',
                'project_description' => 'Kaca untuk proyek Gedung E',
                'items' => json_encode([['description' => 'Kaca Tempered 10mm', 'volume' => 50, 'unit' => 'Sheet', 'unit_price' => 600000, 'total_price' => 30000000]]),
                'total_amount' => 30000000,
            ],
            [
                'invoice_number' => 'P-2026-006',
                'invoice_date' => now()->subDays(1)->format('Y-m-d'),
                'recipient' => 'CV. Utilitas Teknik',
                'regarding' => 'Sewa alat berat',
                'project_description' => 'Sewa crane untuk proyek Mall F',
                'items' => json_encode([['description' => 'Sewa Crane 30 ton', 'volume' => 15, 'unit' => 'hari', 'unit_price' => 3000000, 'total_price' => 45000000]]),
                'total_amount' => 45000000,
            ],
            [
                'invoice_number' => 'P-2026-007',
                'invoice_date' => now()->subDays(3)->format('Y-m-d'),
                'recipient' => 'PT. Logistik Cepat',
                'regarding' => 'Jasa pengiriman material',
                'project_description' => 'Pengiriman material ke proyek Hotel G',
                'items' => json_encode([['description' => 'Pengiriman 10 truck', 'volume' => 10, 'unit' => 'truck', 'unit_price' => 1500000, 'total_price' => 15000000]]),
                'total_amount' => 15000000,
            ],
            [
                'invoice_number' => 'P-2026-008',
                'invoice_date' => now()->subDays(5)->format('Y-m-d'),
                'recipient' => 'CV. Konsultan Teknis',
                'regarding' => 'Jasa konsultasi proyek',
                'project_description' => 'Konsultasi teknis proyek Rumah Sakit H',
                'items' => json_encode([['description' => 'Konsultasi engineering', 'volume' => 100, 'unit' => 'jam', 'unit_price' => 250000, 'total_price' => 25000000]]),
                'total_amount' => 25000000,
            ],
            [
                'invoice_number' => 'P-2026-009',
                'invoice_date' => now()->subDays(7)->format('Y-m-d'),
                'recipient' => 'PT. Instalasi Terpadu',
                'regarding' => 'Jasa instalasi sistem',
                'project_description' => 'Instalasi MEP proyek Sekolah I',
                'items' => json_encode([['description' => 'Instalasi lengkap MEP', 'volume' => 1, 'unit' => 'paket', 'unit_price' => 50000000, 'total_price' => 50000000]]),
                'total_amount' => 50000000,
            ],
            [
                'invoice_number' => 'P-2026-010',
                'invoice_date' => now()->subDays(9)->format('Y-m-d'),
                'recipient' => 'CV. Batu Alam',
                'regarding' => 'Supply material batu alam',
                'project_description' => 'Batu alam untuk proyek Café J',
                'items' => json_encode([['description' => 'Batu Alam Travertine', 'volume' => 30, 'unit' => 'ton', 'unit_price' => 500000, 'total_price' => 15000000]]),
                'total_amount' => 15000000,
            ],
            [
                'invoice_number' => 'P-2026-011',
                'invoice_date' => now()->subDays(10)->format('Y-m-d'),
                'recipient' => 'PT. Signage Indonesia',
                'regarding' => 'Pembuatan signage',
                'project_description' => 'Pembuatan signage proyek Showroom K',
                'items' => json_encode([['description' => 'Signage box letter', 'volume' => 5, 'unit' => 'set', 'unit_price' => 8000000, 'total_price' => 40000000]]),
                'total_amount' => 40000000,
            ],
            [
                'invoice_number' => 'P-2026-012',
                'invoice_date' => now()->subDays(11)->format('Y-m-d'),
                'recipient' => 'CV. Kayu Jati',
                'regarding' => 'Supply material kayu',
                'project_description' => 'Material kayu untuk proyek Gudang L',
                'items' => json_encode([['description' => 'Kayu jati grade A', 'volume' => 50, 'unit' => 'kubik', 'unit_price' => 400000, 'total_price' => 20000000]]),
                'total_amount' => 20000000,
            ],
            [
                'invoice_number' => 'P-2026-013',
                'invoice_date' => now()->subDays(12)->format('Y-m-d'),
                'recipient' => 'PT. Keamanan Proyek',
                'regarding' => 'Jasa keamanan proyek',
                'project_description' => 'Layanan keamanan proyek Cabang M',
                'items' => json_encode([['description' => 'Keamanan 24 jam', 'volume' => 30, 'unit' => 'hari', 'unit_price' => 500000, 'total_price' => 15000000]]),
                'total_amount' => 15000000,
            ],
            [
                'invoice_number' => 'P-2026-014',
                'invoice_date' => now()->subDays(13)->format('Y-m-d'),
                'recipient' => 'CV. Pabrik Precast',
                'regarding' => 'Supply beton precast',
                'project_description' => 'Beton precast untuk proyek Perumahan N',
                'items' => json_encode([['description' => 'Pil precast P-200', 'volume' => 100, 'unit' => 'unit', 'unit_price' => 500000, 'total_price' => 50000000]]),
                'total_amount' => 50000000,
            ],
            [
                'invoice_number' => 'P-2026-015',
                'invoice_date' => now()->subDays(14)->format('Y-m-d'),
                'recipient' => 'PT. Utilitas Listrik',
                'regarding' => 'Supply material listrik',
                'project_description' => 'Material listrik untuk proyek Apartment O',
                'items' => json_encode([['description' => 'Kabel NYM & Panel distribusi', 'volume' => 1, 'unit' => 'lot', 'unit_price' => 25000000, 'total_price' => 25000000]]),
                'total_amount' => 25000000,
            ],
        ];

        DB::table('proyek_invoices')->insert(
            array_map(function ($invoice) {
                return array_merge($invoice, [
                    'discount_type' => null,
                    'discount_value' => null,
                    'total_after_discount' => null,
                    'dp_type' => null,
                    'dp_value' => null,
                    'dp_amount' => null,
                    'payment_installments' => null,
                    'selected_payment_accounts' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }, $invoices)
        );
    }
}
