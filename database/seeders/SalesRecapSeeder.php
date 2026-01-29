<?php

namespace Database\Seeders;

use App\Models\Report\SalesRecap;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SalesRecapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salesData = [
            [
                'id_sales_recap' => 'SR-00001',
                'date' => Carbon::create(2025, 1, 30),
                'name_proyek' => 'PROYEK KAHFI',
                'items' => json_encode([
                    [
                        'name_item' => 'LIST SHADOWLINE',
                        'quantity' => 30,
                        'capital_price' => 10500,
                        'selling_price' => 13650,
                        'from_stock' => false,
                        'id_item' => null,
                    ],
                    [
                        'name_item' => 'GYPSUM',
                        'quantity' => 30,
                        'capital_price' => 46000,
                        'selling_price' => 55000,
                        'from_stock' => false,
                        'id_item' => null,
                    ],
                    [
                        'name_item' => 'HOLLOW UK. 2 x 4',
                        'quantity' => 100,
                        'capital_price' => 11000,
                        'selling_price' => 13200,
                        'from_stock' => false,
                        'id_item' => null,
                    ],
                    [
                        'name_item' => 'SKRUP GYPSUM',
                        'quantity' => 3,
                        'capital_price' => 40000,
                        'selling_price' => 52500,
                        'from_stock' => false,
                        'id_item' => null,
                    ],
                    [
                        'name_item' => 'PAKU BETON',
                        'quantity' => 1,
                        'capital_price' => 48000,
                        'selling_price' => 52000,
                        'from_stock' => false,
                        'id_item' => null,
                    ],
                ]),
                'total_capital' => 2963000,
                'total_selling' => 3589000,
                'total_profit' => 626000,
                'status' => 'Lunas',
            ],
            [
                'id_sales_recap' => 'SR-00002',
                'date' => Carbon::create(2025, 2, 12),
                'name_proyek' => 'PROYEK KALIPASIR TAHAP 3',
                'items' => json_encode([
                    [
                        'name_item' => 'SKRUP BAJA',
                        'quantity' => 4,
                        'capital_price' => 165000,
                        'selling_price' => 181500,
                        'from_stock' => false,
                        'id_item' => null,
                    ],
                    [
                        'name_item' => 'ROOFING 5 CM',
                        'quantity' => 500,
                        'capital_price' => 720,
                        'selling_price' => 1400,
                        'from_stock' => false,
                        'id_item' => null,
                    ],
                ]),
                'total_capital' => 1020000,
                'total_selling' => 1426000,
                'total_profit' => 406000,
                'status' => 'Lunas',
            ],
            [
                'id_sales_recap' => 'SR-00003',
                'date' => Carbon::create(2025, 2, 13),
                'name_proyek' => 'PROYEK KULINER SETIABUDI',
                'items' => json_encode([
                    [
                        'name_item' => 'CANAL C',
                        'quantity' => 92,
                        'capital_price' => 65000,
                        'selling_price' => 70000,
                        'from_stock' => false,
                        'id_item' => null,
                    ],
                    [
                        'name_item' => 'RENG',
                        'quantity' => 100,
                        'capital_price' => 35000,
                        'selling_price' => 40000,
                        'from_stock' => false,
                        'id_item' => null,
                    ],
                    [
                        'name_item' => 'SKRUP BAJA',
                        'quantity' => 4,
                        'capital_price' => 165000,
                        'selling_price' => 181500,
                        'from_stock' => false,
                        'id_item' => null,
                    ],
                ]),
                'total_capital' => 10140000,
                'total_selling' => 11166000,
                'total_profit' => 1026000,
                'status' => 'Lunas',
            ],
            [
                'id_sales_recap' => 'SR-00004',
                'date' => Carbon::create(2025, 2, 22),
                'name_proyek' => 'PROYEK CIMEYWAH',
                'items' => json_encode([
                    [
                        'name_item' => 'HOLLOW UK. 2 x 4',
                        'quantity' => 300,
                        'capital_price' => 11000,
                        'selling_price' => 13200,
                        'from_stock' => false,
                        'id_item' => null,
                    ],
                ]),
                'total_capital' => 3300000,
                'total_selling' => 3960000,
                'total_profit' => 660000,
                'status' => 'Belum Lunas',
            ],
        ];

        foreach ($salesData as $data) {
            SalesRecap::create($data);
        }

        $this->command->info('Sales Recap seeder completed successfully!');
    }
}

