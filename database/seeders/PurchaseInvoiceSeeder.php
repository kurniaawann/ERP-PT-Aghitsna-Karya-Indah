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
            ['date' => now()->subDays(2), 'material' => 'Kaca Float', 'npwp' => '44.444.444.4-444.000', 'tax_code' => 'PN21', 'item' => 'Kaca Float Bening 6mm (60 sheet)', 'price' => 15000000, 'ppn' => 10, 'notes' => 'Untuk proyek Pabrik B'],
            ['date' => now()->subDays(4), 'material' => 'Rubber Sealant', 'npwp' => '55.555.555.5-555.000', 'tax_code' => 'PN21', 'item' => 'Sikaflex Pro (20 tubes)', 'price' => 3200000, 'ppn' => 10, 'notes' => 'Material penutup celah'],
            ['date' => now()->subDays(6), 'material' => 'Stainless Steel', 'npwp' => '66.666.666.6-666.000', 'tax_code' => 'PN21', 'item' => 'Stainless Tube & Fittings', 'price' => 31500000, 'ppn' => 10, 'notes' => 'Untuk proyek Renovasi C'],
            ['date' => now()->subDays(8), 'material' => 'Hardware Fixtures', 'npwp' => '77.777.777.7-777.000', 'tax_code' => 'PN21', 'item' => 'Handle Door & Hinges', 'price' => 4800000, 'ppn' => 10, 'notes' => 'Aksesori pintu dan jendela'],
            ['date' => now()->subDays(9), 'material' => 'Coating Paint', 'npwp' => '88.888.888.8-888.000', 'tax_code' => 'PN21', 'item' => 'Epoxy & Polyurethane (50L)', 'price' => 12600000, 'ppn' => 10, 'notes' => 'Cat protective coating'],
            ['date' => now()->subDays(11), 'material' => 'Insulasi Bangunan', 'npwp' => '99.999.999.9-999.000', 'tax_code' => 'PN21', 'item' => 'Glasswool & Rockwool', 'price' => 8400000, 'ppn' => 10, 'notes' => 'Material insulasi thermal'],
            ['date' => now()->subDays(12), 'material' => 'LED Fixtures', 'npwp' => '10.101.010.1-010.000', 'tax_code' => 'PN21', 'item' => 'LED Panel & Driver (50 set)', 'price' => 7200000, 'ppn' => 10, 'notes' => 'Perlengkapan pencahayaan'],
            ['date' => now()->subDays(13), 'material' => 'Fastener & Anchor', 'npwp' => '20.202.020.2-020.000', 'tax_code' => 'PN21', 'item' => 'Baut, Mur, Sekrup (Qty)', 'price' => 2100000, 'ppn' => 10, 'notes' => 'Perlengkapan perakitan'],
            ['date' => now()->subDays(14), 'material' => 'Adhesive & Sealant', 'npwp' => '30.303.030.3-030.000', 'tax_code' => 'PN21', 'item' => 'Perekat Struktural Berat', 'price' => 6500000, 'ppn' => 10, 'notes' => 'Untuk perekat konstruksi'],
            ['date' => now()->subDays(15), 'material' => 'Waterproofing', 'npwp' => '40.404.040.4-040.000', 'tax_code' => 'PN21', 'item' => 'Membrane Waterproof (500m)', 'price' => 11800000, 'ppn' => 10, 'notes' => 'Material kedap air'],
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
