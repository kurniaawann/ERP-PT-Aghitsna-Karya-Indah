<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $invoices = [
            ['date' => now(), 'material' => 'Profil Aluminium', 'npwp' => '12.345.678.9-012.000', 'tax_code' => 'PN21', 'item' => 'Profil ALU 5x5x2mm (100 pcs)', 'price' => 18500000, 'ppn' => 10, 'notes' => 'Pembelian rutin dari supplier utama'],
            ['date' => now()->subDays(3), 'material' => 'Kaca Tempered', 'npwp' => '98.765.432.1-098.000', 'tax_code' => 'PN21', 'item' => 'Kaca Tempered 10mm (50 sheet)', 'price' => 22500000, 'ppn' => 10, 'notes' => 'Order untuk proyek Gedung A'],
            ['date' => now()->subDays(5), 'material' => 'Perlengkapan Instalasi', 'npwp' => '11.111.111.1-111.000', 'tax_code' => 'PN21', 'item' => 'Engsel, Handle, Sealant', 'price' => 9750000, 'ppn' => 10, 'notes' => 'Pembelian aksesori pelengkap'],
            ['date' => now()->subDays(7), 'material' => 'Bahan Baku Logam', 'npwp' => '22.222.222.2-222.000', 'tax_code' => 'PN21', 'item' => 'Baja Ringan (2 ton)', 'price' => 28000000, 'ppn' => 10, 'notes' => 'Pengiriman tepat waktu'],
            ['date' => now()->subDays(10), 'material' => 'Finishing Material', 'npwp' => '33.333.333.3-333.000', 'tax_code' => 'PN21', 'item' => 'Cat Catylac & Thinner', 'price' => 5400000, 'ppn' => 10, 'notes' => 'Stok finishing untuk proyek'],
        ];

        foreach ($invoices as $data) {
            DB::table('purchase_invoices')->insert([
                'date' => $data['date'],
                'material_name' => $data['material'],
                'npwp' => $data['npwp'],
                'tax_number_code' => $data['tax_code'],
                'item_name' => $data['item'],
                'selling_price' => $data['price'],
                'ppn_percentage' => $data['ppn'],
                'ppn_tax' => intval($data['price'] * $data['ppn'] / 100),
                'notes' => $data['notes'],
                'created_at' => $data['date'],
                'updated_at' => now(),
            ]);
        }
    }
}
