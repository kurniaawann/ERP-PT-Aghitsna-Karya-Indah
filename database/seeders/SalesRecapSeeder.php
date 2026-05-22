<?php

namespace Database\Seeders;

use App\Models\Report\SalesRecap;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesRecapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan id sales recap tidak duplicate saat seed diulang
        // (sales_recaps punya PRIMARY pada id_sales_recap/SR-xxxxx)
        DB::table('sales_recaps')->delete();

        $salesData = [];

        // Strategically selected data (15 records total)
        $projects = [
            ['name' => 'PROYEK KAHFI', 'year' => 2025, 'month' => 1, 'day' => 15, 'capital' => 2963000, 'selling' => 3589000, 'status' => 'Lunas'],
            ['name' => 'PROYEK KULINER SETIABUDI', 'year' => 2025, 'month' => 2, 'day' => 13, 'capital' => 5480000, 'selling' => 7150000, 'status' => 'Lunas'],
            ['name' => 'RENOVASI KANTOR BANDUNG', 'year' => 2025, 'month' => 3, 'day' => 8, 'capital' => 8500000, 'selling' => 11200000, 'status' => 'Lunas'],
            ['name' => 'PROYEK RUKO CIUMBULEUIT', 'year' => 2025, 'month' => 3, 'day' => 22, 'capital' => 15200000, 'selling' => 18900000, 'status' => 'Belum Lunas'],
            ['name' => 'PROYEK GEDUNG SEKOLAH', 'year' => 2025, 'month' => 4, 'day' => 18, 'capital' => 25000000, 'selling' => 32500000, 'status' => 'Lunas'],
            ['name' => 'PROYEK VILLA LEMBANG', 'year' => 2025, 'month' => 5, 'day' => 25, 'capital' => 18500000, 'selling' => 24200000, 'status' => 'Lunas'],
            ['name' => 'PROYEK APARTEMEN PASTEUR', 'year' => 2025, 'month' => 6, 'day' => 20, 'capital' => 28000000, 'selling' => 36400000, 'status' => 'Belum Lunas'],
            ['name' => 'RENOVASI HOTEL HERITAGE', 'year' => 2025, 'month' => 7, 'day' => 12, 'capital' => 45000000, 'selling' => 58500000, 'status' => 'Lunas'],
            ['name' => 'PROYEK PABRIK CIMAHI', 'year' => 2025, 'month' => 8, 'day' => 28, 'capital' => 38000000, 'selling' => 49400000, 'status' => 'Lunas'],
            ['name' => 'GUDANG LOGISTICS GEDEBAGE', 'year' => 2025, 'month' => 8, 'day' => 19, 'capital' => 35000000, 'selling' => 45500000, 'status' => 'Lunas'],
            ['name' => 'PROYEK MASJID AL-IKHLAS', 'year' => 2025, 'month' => 9, 'day' => 4, 'capital' => 15000000, 'selling' => 19500000, 'status' => 'Lunas'],
            ['name' => 'PROYEK MALL BARU', 'year' => 2025, 'month' => 10, 'day' => 11, 'capital' => 55000000, 'selling' => 71500000, 'status' => 'Lunas'],
            ['name' => 'BANK BRANCH OFFICE', 'year' => 2025, 'month' => 10, 'day' => 26, 'capital' => 42000000, 'selling' => 54600000, 'status' => 'Belum Lunas'],
            ['name' => 'FITNESS CENTER DAGO', 'year' => 2025, 'month' => 11, 'day' => 23, 'capital' => 24000000, 'selling' => 31200000, 'status' => 'Lunas'],
            ['name' => 'HOTEL RESORT CIATER', 'year' => 2026, 'month' => 1, 'day' => 29, 'capital' => 58000000, 'selling' => 75400000, 'status' => 'Belum Lunas'],
        ];

        $counter = 1;

        foreach ($projects as $project) {
            $salesData[] = [
                'id_sales_recap' => 'SR-' . str_pad($counter, 5, '0', STR_PAD_LEFT),
                'date' => Carbon::create($project['year'], $project['month'], $project['day']),
                'name_proyek' => $project['name'],
                'items' => json_encode($this->generateItems($project['capital'])),
                'total_capital' => $project['capital'],
                'total_selling' => $project['selling'],
                'total_profit' => $project['selling'] - $project['capital'],
                'status' => $project['status'],
            ];
            $counter++;
        }

        foreach ($salesData as $data) {
            SalesRecap::create($data);
        }

        $this->command->info('Sales Recap seeder completed successfully with ' . count($salesData) . ' records!');
    }

    /**
     * Generate random items based on total capital
     */
    private function generateItems($totalCapital)
    {
        $items = [];
        $itemNames = [
            'LIST SHADOWLINE',
            'GYPSUM',
            'HOLLOW UK. 2 x 4',
            'SKRUP GYPSUM',
            'PAKU BETON',
            'SKRUP BAJA',
            'ROOFING 5 CM',
            'BESI BETON',
            'SEMEN PORTLAND',
            'PASIR BETON',
            'KERAMIK LANTAI',
            'CAT TEMBOK',
            'RANGKA BAJA RINGAN',
            'ATAP METAL',
            'PLAFON PVC',
            'CANAL C',
            'RENG',
        ];

        $numItems = rand(3, 6);
        $remainingCapital = $totalCapital;

        for ($i = 0; $i < $numItems; $i++) {
            $isLastItem = ($i === $numItems - 1);

            if ($isLastItem) {
                $itemCapital = $remainingCapital;
            } else {
                $itemCapital = rand((int) ($remainingCapital * 0.1), (int) ($remainingCapital * 0.4));
                $remainingCapital -= $itemCapital;
            }

            $quantity = rand(10, 200);
            $capitalPrice = (int) ($itemCapital / $quantity);
            $sellingPrice = (int) ($capitalPrice * (1 + rand(20, 40) / 100)); // Markup 20-40%

            $items[] = [
                'name_item' => $itemNames[array_rand($itemNames)],
                'quantity' => $quantity,
                'capital_price' => $capitalPrice,
                'selling_price' => $sellingPrice,
                'from_stock' => false,
                'id_item' => null,
            ];
        }

        return $items;
    }
}

