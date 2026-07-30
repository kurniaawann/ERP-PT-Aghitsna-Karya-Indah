<?php
namespace Database\Seeders;
use App\Models\Finance\PurchaseInvoice;
use Illuminate\Database\Seeder;

class PurchaseInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [
            'Profil Aluminium', 'Kaca Tempered', 'Perlengkapan Instalasi', 'Bahan Baku Logam',
            'Finishing Material', 'Kaca Float', 'Rubber Sealant', 'Stainless Steel',
            'Hardware Fixtures', 'Coating Paint', 'Insulasi Bangunan', 'LED Fixtures',
            'Fastener & Anchor', 'Adhesive & Sealant', 'Waterproofing',
        ];

        foreach ($materials as $i => $material) {
            $price = rand(50000, 2000000);
            PurchaseInvoice::create([
                'date' => fake()->dateTimeBetween('-6 months', 'now'),
                'material_name' => $material,
                'npwp' => fake()->numerify('##.###.###.#-###.###'),
                'tax_number_code' => 'PN-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'item_name' => $material,
                'selling_price' => $price,
                'ppn_percentage' => 11.00,
                'ppn_tax' => (int) ($price * 0.11),
            ]);
        }
    }
}
