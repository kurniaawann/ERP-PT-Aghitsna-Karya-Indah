<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProyekInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('proyek_invoices')->insert([
            [
                'invoice_number' => 'P-2026-001',
                'invoice_date' => now()->format('Y-m-d'),
                'recipient' => 'PT. Cahaya Sentosa',
                'regarding' => 'Pembelian material dan jasa proyek',
                'project_description' => 'Pemasangan kusen dan pintu aluminium untuk proyek Gedung A',
                'items' => json_encode([
                    ['description' => 'Pintu Aluminium 2x4 m', 'volume' => 2, 'unit' => 'unit', 'unit_price' => 7500000, 'total_price' => 15000000]
                ]),
                'total_amount' => 15000000,
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
            ],
        ]);
    }
}
