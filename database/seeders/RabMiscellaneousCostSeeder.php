<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RabMiscellaneousCostSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['description' => 'Biaya tak terduga proyek Gedung A - perbaikan fondasi', 'amount' => 5000000, 'date' => now()],
            ['description' => 'Biaya izin dan perizinan proyek Pabrik B', 'amount' => 2500000, 'date' => now()->subDays(2)],
            ['description' => 'Biaya tambahan logistik dan handling material', 'amount' => 3200000, 'date' => now()->subDays(4)],
            ['description' => 'Biaya konsultasi teknis dengan arsitek', 'amount' => 1500000, 'date' => now()->subDays(6)],
            ['description' => 'Biaya safety dan asuransi pekerja proyek', 'amount' => 4000000, 'date' => now()->subDays(8)],
        ];

        foreach ($data as $item) {
            DB::table('rab_miscellaneous_costs')->insert([
                'description' => $item['description'],
                'amount' => $item['amount'],
                'created_at' => $item['date'],
                'updated_at' => now(),
            ]);
        }
    }
}
