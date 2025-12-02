<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OvertimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $overtimes = [];

        // Data lembur Oktober 2025 (hanya tanggal MINGGU - lembur di hari libur)
        // Catatan: Sabtu adalah hari kerja normal, jadi lembur hanya di Minggu
        $octoberOvertimes = [
            // EMP001 - Ahmad Kurniawan (Manager) - Minggu overtime
            ['employee_id' => 'EMP001', 'date' => '2025-10-19', 'hours' => 4, 'rate' => 100000, 'notes' => 'Closing laporan (Minggu)'],

            // EMP002 - Siti Nurhaliza (Supervisor)
            ['employee_id' => 'EMP002', 'date' => '2025-10-05', 'hours' => 2, 'rate' => 80000, 'notes' => 'Koordinasi tim (Minggu)'],
            ['employee_id' => 'EMP002', 'date' => '2025-10-26', 'hours' => 2, 'rate' => 80000, 'notes' => 'Evaluasi bulanan (Minggu)'],

            // EMP003 - Budi Santoso (Staff Admin)
            ['employee_id' => 'EMP003', 'date' => '2025-10-19', 'hours' => 2, 'rate' => 50000, 'notes' => 'Arsip dokumen (Minggu)'],

            // EMP005 - Eko Prasetyo (Staff Produksi) - no overtime di Oktober

            // EMP008 - Hendra Gunawan (Staff IT)
            ['employee_id' => 'EMP008', 'date' => '2025-10-05', 'hours' => 3, 'rate' => 75000, 'notes' => 'Maintenance server (Minggu)'],
            ['employee_id' => 'EMP008', 'date' => '2025-10-26', 'hours' => 2, 'rate' => 75000, 'notes' => 'Troubleshooting (Minggu)'],
        ];

        // Data lembur November 2025 (hanya tanggal MINGGU)
        $novemberOvertimes = [
            // EMP001 - Ahmad Kurniawan - no overtime di November

            // EMP002 - Siti Nurhaliza
            ['employee_id' => 'EMP002', 'date' => '2025-11-02', 'hours' => 2.5, 'rate' => 80000, 'notes' => 'Monitoring produksi (Minggu)'],
            ['employee_id' => 'EMP002', 'date' => '2025-11-09', 'hours' => 3, 'rate' => 80000, 'notes' => 'Quality control (Minggu)'],

            // EMP004 - Dewi Lestari (Marketing) - no overtime di November

            // EMP005 - Eko Prasetyo
            ['employee_id' => 'EMP005', 'date' => '2025-11-02', 'hours' => 2, 'rate' => 45000, 'notes' => 'Produksi tambahan (Minggu)'],
            ['employee_id' => 'EMP005', 'date' => '2025-11-09', 'hours' => 3.5, 'rate' => 45000, 'notes' => 'Deadline order (Minggu)'],

            // EMP008 - Hendra Gunawan - no overtime di November
        ];

        // Merge dan format data
        $allOvertimes = array_merge($octoberOvertimes, $novemberOvertimes);

        foreach ($allOvertimes as $overtime) {
            $overtimes[] = [
                'employee_id' => $overtime['employee_id'],
                'attendance_date' => $overtime['date'],
                'status' => 'lembur',
                'overtime_hours' => $overtime['hours'],
                'overtime_rate' => $overtime['rate'],
                'overtime_total' => $overtime['hours'] * $overtime['rate'],
                'notes' => $overtime['notes'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('attendances')->insert($overtimes);
    }
}
