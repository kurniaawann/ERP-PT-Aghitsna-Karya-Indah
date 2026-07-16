<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OvertimeSeeder extends Seeder
{
    /**
     * Jalankan seed basis data.
     */
    public function run(): void
    {
        $overtimes = [];

        $overtimeData = [
            ['employee_id' => 'EMP001', 'date' => '2025-10-19', 'hours' => 4, 'rate' => 100000, 'notes' => 'Closing laporan (Minggu)'],
            ['employee_id' => 'EMP002', 'date' => '2025-10-05', 'hours' => 2, 'rate' => 80000, 'notes' => 'Koordinasi tim (Minggu)'],
            ['employee_id' => 'EMP002', 'date' => '2025-10-26', 'hours' => 2, 'rate' => 80000, 'notes' => 'Evaluasi bulanan (Minggu)'],
            ['employee_id' => 'EMP003', 'date' => '2025-10-19', 'hours' => 2, 'rate' => 50000, 'notes' => 'Arsip dokumen (Minggu)'],
            ['employee_id' => 'EMP008', 'date' => '2025-10-05', 'hours' => 3, 'rate' => 75000, 'notes' => 'Maintenance server (Minggu)'],
            ['employee_id' => 'EMP008', 'date' => '2025-10-26', 'hours' => 2, 'rate' => 75000, 'notes' => 'Troubleshooting (Minggu)'],
            ['employee_id' => 'EMP002', 'date' => '2025-11-02', 'hours' => 2.5, 'rate' => 80000, 'notes' => 'Monitoring produksi (Minggu)'],
            ['employee_id' => 'EMP002', 'date' => '2025-11-09', 'hours' => 3, 'rate' => 80000, 'notes' => 'Quality control (Minggu)'],
            ['employee_id' => 'EMP005', 'date' => '2025-11-02', 'hours' => 2, 'rate' => 45000, 'notes' => 'Produksi tambahan (Minggu)'],
            ['employee_id' => 'EMP005', 'date' => '2025-11-09', 'hours' => 3.5, 'rate' => 45000, 'notes' => 'Deadline order (Minggu)'],
            ['employee_id' => 'EMP001', 'date' => '2025-11-16', 'hours' => 3, 'rate' => 100000, 'notes' => 'Meeting penting (Minggu)'],
            ['employee_id' => 'EMP004', 'date' => '2025-11-23', 'hours' => 2, 'rate' => 60000, 'notes' => 'Presentasi klien (Minggu)'],
            ['employee_id' => 'EMP006', 'date' => '2025-11-30', 'hours' => 2.5, 'rate' => 55000, 'notes' => 'Setup display (Minggu)'],
            ['employee_id' => 'EMP007', 'date' => '2025-10-12', 'hours' => 3, 'rate' => 70000, 'notes' => 'Perbaikan sistem (Minggu)'],
            ['employee_id' => 'EMP003', 'date' => '2025-11-16', 'hours' => 2, 'rate' => 50000, 'notes' => 'Data entry urgent (Minggu)'],
        ];

        foreach ($overtimeData as $overtime) {
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
