<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Items;

class ItemSeeder extends Seeder
{
    /**
     * Jalankan seeder untuk tabel items.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Items::create([
                'id_item' => 'ITM-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'name_item' => 'Barang ' . $i,
                'quantity' => rand(1, 100), // stok acak antara 1–100
                'capital_price' => rand(50000, 200000), // harga modal acak
                'selling_price' => rand(210000, 400000), // harga jual acak
            ]);
        }
    }
}
