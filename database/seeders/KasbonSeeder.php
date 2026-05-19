<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sdm\Kasbon;

class KasbonSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['code' => 'KSB001', 'emp' => 'EMP001', 'type' => 'personal', 'amount' => 500000, 'date' => now(), 'month' => 5, 'year' => 2026, 'status' => 'pending', 'notes' => 'Untuk pembelian perlengkapan kerja'],
            ['code' => 'KSB002', 'emp' => 'EMP002', 'type' => 'personal', 'amount' => 750000, 'date' => now()->subDays(3), 'month' => 5, 'year' => 2026, 'status' => 'deducted', 'notes' => 'Sudah dipotong di slip gaji'],
            ['code' => 'KSB003', 'emp' => 'EMP003', 'type' => 'personal', 'amount' => 1200000, 'date' => now()->subDays(5), 'month' => 5, 'year' => 2026, 'status' => 'deducted', 'notes' => 'Biaya kesehatan karyawan'],
            ['code' => 'KSB004', 'emp' => 'EMP004', 'type' => 'personal', 'amount' => 2000000, 'date' => now()->subDays(7), 'month' => 5, 'year' => 2026, 'status' => 'pending', 'notes' => 'Kebutuhan mendesak keluarga'],
            ['code' => 'KSB005', 'emp' => 'EMP005', 'type' => 'team', 'amount' => 800000, 'date' => now()->subDays(10), 'month' => 4, 'year' => 2026, 'status' => 'deducted', 'notes' => null],
        ];

        foreach ($data as $item) {
            Kasbon::create([
                'kasbon_code' => $item['code'],
                'employee_id' => $item['emp'],
                'kasbon_type' => $item['type'],
                'amount' => $item['amount'],
                'kasbon_date' => $item['date'],
                'period_month' => $item['month'],
                'period_year' => $item['year'],
                'status' => $item['status'],
                'notes' => $item['notes'],
            ]);
        }
    }
}
