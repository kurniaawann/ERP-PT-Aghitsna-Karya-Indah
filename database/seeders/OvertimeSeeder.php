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

        // Data lembur Oktober 2025 (tanggal weekend atau setelah jam kerja)
        $octoberOvertimes = [
            // Employee 1 - Ahmad Kurniawan (Manager) - Weekend overtime
            ['employee_id' => 1, 'date' => '2025-10-04', 'hours' => 3, 'rate' => 100000, 'notes' => 'Meeting dengan klien (Sabtu)'],
            ['employee_id' => 1, 'date' => '2025-10-11', 'hours' => 2.5, 'rate' => 100000, 'notes' => 'Presentasi proyek (Sabtu)'],
            ['employee_id' => 1, 'date' => '2025-10-19', 'hours' => 4, 'rate' => 100000, 'notes' => 'Closing laporan (Minggu)'],

            // Employee 2 - Siti Nurhaliza (Supervisor)
            ['employee_id' => 2, 'date' => '2025-10-05', 'hours' => 2, 'rate' => 80000, 'notes' => 'Koordinasi tim (Minggu)'],
            ['employee_id' => 2, 'date' => '2025-10-12', 'hours' => 3.5, 'rate' => 80000, 'notes' => 'Training karyawan baru (Sabtu)'],
            ['employee_id' => 2, 'date' => '2025-10-26', 'hours' => 2, 'rate' => 80000, 'notes' => 'Evaluasi bulanan (Minggu)'],

            // Employee 3 - Budi Santoso (Staff Admin)
            ['employee_id' => 3, 'date' => '2025-10-11', 'hours' => 1.5, 'rate' => 50000, 'notes' => 'Entry data (Sabtu)'],
            ['employee_id' => 3, 'date' => '2025-10-19', 'hours' => 2, 'rate' => 50000, 'notes' => 'Arsip dokumen (Minggu)'],

            // Employee 5 - Eko Prasetyo (Staff Produksi)
            ['employee_id' => 5, 'date' => '2025-10-04', 'hours' => 3, 'rate' => 45000, 'notes' => 'Kejar target produksi (Sabtu)'],
            ['employee_id' => 5, 'date' => '2025-10-12', 'hours' => 2.5, 'rate' => 45000, 'notes' => 'Order mendesak (Sabtu)'],
            ['employee_id' => 5, 'date' => '2025-10-18', 'hours' => 4, 'rate' => 45000, 'notes' => 'Packing barang (Sabtu)'],

            // Employee 8 - Hendra Gunawan (Staff IT)
            ['employee_id' => 8, 'date' => '2025-10-05', 'hours' => 3, 'rate' => 75000, 'notes' => 'Maintenance server (Minggu)'],
            ['employee_id' => 8, 'date' => '2025-10-18', 'hours' => 5, 'rate' => 75000, 'notes' => 'Update sistem (Sabtu)'],
            ['employee_id' => 8, 'date' => '2025-10-26', 'hours' => 2, 'rate' => 75000, 'notes' => 'Troubleshooting (Minggu)'],
        ];

        // Data lembur November 2025 (tanggal weekend)
        $novemberOvertimes = [
            // Employee 1 - Ahmad Kurniawan
            ['employee_id' => 1, 'date' => '2025-11-01', 'hours' => 3, 'rate' => 100000, 'notes' => 'Rapat evaluasi (Sabtu)'],
            ['employee_id' => 1, 'date' => '2025-11-08', 'hours' => 2, 'rate' => 100000, 'notes' => 'Finalisasi proposal (Sabtu)'],

            // Employee 2 - Siti Nurhaliza
            ['employee_id' => 2, 'date' => '2025-11-02', 'hours' => 2.5, 'rate' => 80000, 'notes' => 'Monitoring produksi (Minggu)'],
            ['employee_id' => 2, 'date' => '2025-11-09', 'hours' => 3, 'rate' => 80000, 'notes' => 'Quality control (Minggu)'],

            // Employee 4 - Dewi Lestari (Marketing)
            ['employee_id' => 4, 'date' => '2025-11-01', 'hours' => 2, 'rate' => 60000, 'notes' => 'Event marketing (Sabtu)'],
            ['employee_id' => 4, 'date' => '2025-11-08', 'hours' => 3, 'rate' => 60000, 'notes' => 'Presentasi produk (Sabtu)'],

            // Employee 5 - Eko Prasetyo
            ['employee_id' => 5, 'date' => '2025-11-02', 'hours' => 2, 'rate' => 45000, 'notes' => 'Produksi tambahan (Minggu)'],
            ['employee_id' => 5, 'date' => '2025-11-09', 'hours' => 3.5, 'rate' => 45000, 'notes' => 'Deadline order (Minggu)'],

            // Employee 8 - Hendra Gunawan
            ['employee_id' => 8, 'date' => '2025-11-01', 'hours' => 4, 'rate' => 75000, 'notes' => 'Implementasi fitur baru (Sabtu)'],
            ['employee_id' => 8, 'date' => '2025-11-08', 'hours' => 2.5, 'rate' => 75000, 'notes' => 'Bug fixing (Sabtu)'],
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
