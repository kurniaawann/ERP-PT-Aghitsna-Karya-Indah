<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory\Items;

class ItemSeeder extends Seeder
{
    /**
     * Jalankan seeder untuk tabel items.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'Profil Aluminium 5x5x2mm', 'qty' => 150, 'capital' => 185000, 'selling' => 250000],
            ['name' => 'Kaca Tempered 10mm Clear', 'qty' => 85, 'capital' => 450000, 'selling' => 620000],
            ['name' => 'Engsel Pivot Stainless Steel', 'qty' => 200, 'capital' => 95000, 'selling' => 145000],
            ['name' => 'Handle Aluminium Anodized Black', 'qty' => 320, 'capital' => 35000, 'selling' => 65000],
            ['name' => 'Pita Perekat Doble Sided 2cm', 'qty' => 500, 'capital' => 15000, 'selling' => 28000],
            ['name' => 'Sealant Silikon Transparan 500ml', 'qty' => 120, 'capital' => 28000, 'selling' => 52000],
        ];

        foreach ($items as $index => $item) {
            Items::create([
                'id_item' => 'ITM-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'name_item' => $item['name'],
                'quantity' => $item['qty'],
                'capital_price' => $item['capital'],
                'selling_price' => $item['selling'],
            ]);
        }
    }
}
