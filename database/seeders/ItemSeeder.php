<?php
namespace Database\Seeders;
use App\Models\Inventory\Items;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['id_item' => 'ITM-0001', 'name_item' => 'Profil Aluminium', 'quantity' => 150, 'capital_price' => 85000, 'selling_price' => 120000],
            ['id_item' => 'ITM-0002', 'name_item' => 'Kaca Tempered', 'quantity' => 80, 'capital_price' => 250000, 'selling_price' => 350000],
            ['id_item' => 'ITM-0003', 'name_item' => 'Engsel Pivot', 'quantity' => 200, 'capital_price' => 15000, 'selling_price' => 25000],
            ['id_item' => 'ITM-0004', 'name_item' => 'Handle Aluminium', 'quantity' => 120, 'capital_price' => 35000, 'selling_price' => 55000],
            ['id_item' => 'ITM-0005', 'name_item' => 'Pita Perekat', 'quantity' => 300, 'capital_price' => 8000, 'selling_price' => 12000],
            ['id_item' => 'ITM-0006', 'name_item' => 'Sealant Silikon', 'quantity' => 90, 'capital_price' => 45000, 'selling_price' => 70000],
            ['id_item' => 'ITM-0007', 'name_item' => 'Kaca Float', 'quantity' => 60, 'capital_price' => 180000, 'selling_price' => 260000],
            ['id_item' => 'ITM-0008', 'name_item' => 'Kaca Patri', 'quantity' => 40, 'capital_price' => 320000, 'selling_price' => 450000],
            ['id_item' => 'ITM-0009', 'name_item' => 'Cat Duco', 'quantity' => 70, 'capital_price' => 55000, 'selling_price' => 85000],
            ['id_item' => 'ITM-0010', 'name_item' => 'Serbuk Kayu', 'quantity' => 250, 'capital_price' => 12000, 'selling_price' => 20000],
            ['id_item' => 'ITM-0011', 'name_item' => 'Kaleng Minyak Linseed', 'quantity' => 50, 'capital_price' => 65000, 'selling_price' => 95000],
            ['id_item' => 'ITM-0012', 'name_item' => 'Paku Stainless', 'quantity' => 500, 'capital_price' => 5000, 'selling_price' => 8000],
            ['id_item' => 'ITM-0013', 'name_item' => 'Baut Galvanis', 'quantity' => 400, 'capital_price' => 7000, 'selling_price' => 12000],
            ['id_item' => 'ITM-0014', 'name_item' => 'Kunci Pengaman', 'quantity' => 30, 'capital_price' => 120000, 'selling_price' => 180000],
            ['id_item' => 'ITM-0015', 'name_item' => 'Kaca Cermin', 'quantity' => 35, 'capital_price' => 200000, 'selling_price' => 300000],
        ];

        foreach ($items as $item) {
            Items::updateOrCreate(['id_item' => $item['id_item']], $item);
        }
    }
}
