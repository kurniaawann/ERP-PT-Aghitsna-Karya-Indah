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
            ['code' => 'KSB006', 'emp' => 'EMP006', 'type' => 'personal', 'amount' => 600000, 'date' => now()->subDays(2), 'month' => 5, 'year' => 2026, 'status' => 'pending', 'notes' => 'Kebutuhan mendesak'],
            ['code' => 'KSB007', 'emp' => 'EMP007', 'type' => 'team', 'amount' => 950000, 'date' => now()->subDays(4), 'month' => 5, 'year' => 2026, 'status' => 'deducted', 'notes' => 'Untuk proyek khusus'],
            ['code' => 'KSB008', 'emp' => 'EMP008', 'type' => 'personal', 'amount' => 1500000, 'date' => now()->subDays(6), 'month' => 5, 'year' => 2026, 'status' => 'deducted', 'notes' => 'Cicilan pendidikan'],
            ['code' => 'KSB009', 'emp' => 'EMP009', 'type' => 'personal', 'amount' => 700000, 'date' => now()->subDays(8), 'month' => 5, 'year' => 2026, 'status' => 'pending', 'notes' => null],
            ['code' => 'KSB010', 'emp' => 'EMP010', 'type' => 'team', 'amount' => 1100000, 'date' => now()->subDays(9), 'month' => 4, 'year' => 2026, 'status' => 'deducted', 'notes' => 'Untuk kegiatan team building'],
            ['code' => 'KSB011', 'emp' => 'EMP011', 'type' => 'personal', 'amount' => 550000, 'date' => now()->subDays(11), 'month' => 4, 'year' => 2026, 'status' => 'deducted', 'notes' => 'Cicilan bulanan'],
            ['code' => 'KSB012', 'emp' => 'EMP012', 'type' => 'personal', 'amount' => 850000, 'date' => now()->subDays(12), 'month' => 4, 'year' => 2026, 'status' => 'pending', 'notes' => 'Biaya pengobatan'],
            ['code' => 'KSB013', 'emp' => 'EMP013', 'type' => 'team', 'amount' => 1300000, 'date' => now()->subDays(13), 'month' => 4, 'year' => 2026, 'status' => 'deducted', 'notes' => 'Training peserta'],
            ['code' => 'KSB014', 'emp' => 'EMP014', 'type' => 'personal', 'amount' => 900000, 'date' => now()->subDays(14), 'month' => 4, 'year' => 2026, 'status' => 'deducted', 'notes' => 'Pembiayaan properti'],
            ['code' => 'KSB015', 'emp' => 'EMP001', 'type' => 'personal', 'amount' => 450000, 'date' => now()->subDays(15), 'month' => 4, 'year' => 2026, 'status' => 'pending', 'notes' => 'Kebutuhan bulanan'],
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
