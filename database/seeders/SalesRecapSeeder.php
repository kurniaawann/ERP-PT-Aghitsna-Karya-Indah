<?php
namespace Database\Seeders;
use App\Models\Report\SalesRecap;
use Illuminate\Database\Seeder;

class SalesRecapSeeder extends Seeder
{
    public function run(): void
    {
        $projects = [
            'PROYEK KAHFI', 'KULINER SETIABUDI', 'RENOVASI KANTOR BANDUNG',
            'RUKO CIUMBULEUIT', 'GEDUNG SEKOLAH', 'VILLA LEMBANG',
            'APARTEMEN PASTEUR', 'HOTEL HERITAGE', 'PABRIK CIMAHI',
            'GUDANG GEDEBAGE', 'MASJID AL-IKHLAS', 'MALL BARU',
            'BANK BRANCH OFFICE', 'FITNESS CENTER DAGO', 'HOTEL RESORT CIATER',
        ];

        foreach ($projects as $i => $project) {
            $numItems = rand(2, 4);
            $items = [];
            $totalCapital = 0;
            $totalSelling = 0;
            for ($j = 0; $j < $numItems; $j++) {
                $cap = rand(100000, 1000000);
                $sell = $cap + rand(50000, 500000);
                $qty = rand(1, 20);
                $items[] = [
                    'id_item' => 'ITM-' . str_pad(rand(1, 15), 4, '0', STR_PAD_LEFT),
                    'name_item' => fake()->words(2, true),
                    'quantity' => $qty,
                    'capital_price' => $cap,
                    'selling_price' => $sell,
                ];
                $totalCapital += $cap * $qty;
                $totalSelling += $sell * $qty;
            }

            SalesRecap::updateOrCreate(
                ['id_sales_recap' => 'SR-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT)],
                [
                    'date' => fake()->dateTimeBetween('-6 months', 'now'),
                    'name_proyek' => $project,
                    'items' => $items,
                    'total_capital' => $totalCapital,
                    'total_selling' => $totalSelling,
                    'total_profit' => $totalSelling - $totalCapital,
                    'status' => $i < 11 ? 'Lunas' : 'Belum Lunas',
                ]
            );
        }
    }
}
