<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Inventory\Items;


class ItemSeeder extends Seeder
{
    /**
     * Jalankan seeder untuk tabel items.
     */
    public function run(): void
    {
        // Bersihkan data lama agar tidak bentrok primary key
        DB::table('items')->delete();
        $items = [
            ['name' => 'Profil Aluminium 5x5x2mm', 'qty' => 150, 'capital' => 185000, 'selling' => 250000],
            ['name' => 'Kaca Tempered 10mm Clear', 'qty' => 85, 'capital' => 450000, 'selling' => 620000],
            ['name' => 'Engsel Pivot Stainless Steel', 'qty' => 200, 'capital' => 95000, 'selling' => 145000],
            ['name' => 'Handle Aluminium Anodized Black', 'qty' => 320, 'capital' => 35000, 'selling' => 65000],
            ['name' => 'Pita Perekat Doble Sided 2cm', 'qty' => 500, 'capital' => 15000, 'selling' => 28000],
            ['name' => 'Sealant Silikon Transparan 500ml', 'qty' => 120, 'capital' => 28000, 'selling' => 52000],
            ['name' => 'Kaca Float Bening 5mm', 'qty' => 200, 'capital' => 350000, 'selling' => 480000],
            ['name' => 'Kaca Patri Warna Biru', 'qty' => 75, 'capital' => 520000, 'selling' => 720000],
            ['name' => 'Cat Duco Warna Putih 1L', 'qty' => 300, 'capital' => 45000, 'selling' => 75000],
            ['name' => 'Serbuk Kayu Mahoni Premium', 'qty' => 250, 'capital' => 25000, 'selling' => 45000],
            ['name' => 'Kaleng Minyak Linseed 2L', 'qty' => 150, 'capital' => 85000, 'selling' => 135000],
            ['name' => 'Paku Stainless Steel 5cm', 'qty' => 1000, 'capital' => 8000, 'selling' => 15000],
            ['name' => 'Baut M8 Galvanis Box', 'qty' => 500, 'capital' => 12000, 'selling' => 22000],
            ['name' => 'Kunci Pengaman Panel Pintu', 'qty' => 100, 'capital' => 55000, 'selling' => 95000],
            ['name' => 'Kaca Cermin Dekorasi 60x80', 'qty' => 45, 'capital' => 280000, 'selling' => 420000],
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
