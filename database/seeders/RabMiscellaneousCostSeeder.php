<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RabMiscellaneousCostSeeder extends Seeder
{
    public function run(): void
    {
        $rabs = DB::table('rabs')->select('rab_number')->get();

        if ($rabs->isEmpty()) {
            // Jika rabs belum ada, seeder tidak bisa mengisi FK rab_number.
            return;
        }

        $data = [
            ['description' => 'Biaya tak terduga proyek Gedung A - perbaikan fondasi', 'amount' => 5000000, 'date' => now()],
            ['description' => 'Biaya izin dan perizinan proyek Pabrik B', 'amount' => 2500000, 'date' => now()->subDays(2)],
            ['description' => 'Biaya tambahan logistik dan handling material', 'amount' => 3200000, 'date' => now()->subDays(4)],
            ['description' => 'Biaya konsultasi teknis dengan arsitek', 'amount' => 1500000, 'date' => now()->subDays(6)],
            ['description' => 'Biaya safety dan asuransi pekerja proyek', 'amount' => 4000000, 'date' => now()->subDays(8)],
            ['description' => 'Biaya survey dan staking out proyek Renovasi C', 'amount' => 1800000, 'date' => now()->subDays(1)],
            ['description' => 'Biaya dewatering dan pengurasan lokasi proyek', 'amount' => 2200000, 'date' => now()->subDays(3)],
            ['description' => 'Biaya pengurusan surat-surat administrasi BPHTB', 'amount' => 950000, 'date' => now()->subDays(5)],
            ['description' => 'Biaya perbaikan akibat kesalahan dalam pelaksanaan', 'amount' => 3500000, 'date' => now()->subDays(7)],
            ['description' => 'Biaya koordinasi dengan pihak ketiga dan permit', 'amount' => 1200000, 'date' => now()->subDays(9)],
            ['description' => 'Biaya pengujian material di laboratorium independen', 'amount' => 2800000, 'date' => now()->subDays(10)],
            ['description' => 'Biaya pengurusan SKPI dan sertifikasi usaha', 'amount' => 1500000, 'date' => now()->subDays(11)],
            ['description' => 'Biaya pembersihan site dan disposal limbah', 'amount' => 2100000, 'date' => now()->subDays(12)],
            ['description' => 'Biaya contingency untuk pekerjaan tambahan', 'amount' => 5500000, 'date' => now()->subDays(13)],
            ['description' => 'Biaya rental crane dan alat berat insidentil', 'amount' => 4300000, 'date' => now()->subDays(14)],
        ];

        $now = now();
        $rabCount = $rabs->count();

        // Menentukan item_order per rab (mulai 1)
        $itemOrderByRab = [];

        foreach ($data as $index => $item) {
            $rab = $rabs[$index % $rabCount];
            $rabNumber = $rab->rab_number;

            if (!isset($itemOrderByRab[$rabNumber])) {
                $itemOrderByRab[$rabNumber] = 1;
            }

            $itemOrder = $itemOrderByRab[$rabNumber];
            $itemOrderByRab[$rabNumber]++;

            DB::table('rab_miscellaneous_costs')->insert([
                'rab_number' => $rabNumber,
                'item_order' => $itemOrder,
                'item_name' => $item['description'],
                'amount' => $item['amount'],
                'order' => 0,
                'created_at' => $item['date'],
                'updated_at' => $now,
            ]);
        }
    }
}

