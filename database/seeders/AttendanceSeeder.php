<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attendances = [];

        // Generate attendance data untuk Oktober 2025 (karyawan EMP001-EMP010)
        $startDate = Carbon::create(2025, 10, 1);
        $endDate = Carbon::create(2025, 10, 31);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // Skip hanya Minggu (Sabtu adalah hari kerja)
            if ($date->isSunday()) {
                continue;
            }

            // Karyawan EMP001-EMP010 (aktif) - Oktober
            for ($empNum = 1; $empNum <= 10; $empNum++) {
                $empCode = 'EMP' . str_pad($empNum, 3, '0', STR_PAD_LEFT);
                $rand = rand(1, 100);

                if ($rand <= 85) {
                    // 85% hadir
                    $status = 'hadir';
                    $notes = null;
                } elseif ($rand <= 92) {
                    // 7% izin
                    $status = 'izin';
                    $notes = 'Keperluan keluarga';
                } elseif ($rand <= 97) {
                    // 5% sakit
                    $status = 'sakit';
                    $notes = 'Sakit flu';
                } else {
                    // 3% cuti
                    $status = 'cuti';
                    $notes = 'Cuti tahunan';
                }

                $attendances[] = [
                    'employee_id' => $empCode,
                    'attendance_date' => $date->format('Y-m-d'),
                    'status' => $status,
                    'overtime_hours' => null,
                    'overtime_rate' => null,
                    'overtime_total' => null,
                    'notes' => $notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Generate attendance data untuk November 2025 (karyawan EMP001-EMP014, 1-14 hari)
        $startDate = Carbon::create(2025, 11, 1);
        $endDate = Carbon::create(2025, 11, 14);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // Skip hanya Minggu (Sabtu adalah hari kerja)
            if ($date->isSunday()) {
                continue;
            }

            // Karyawan EMP001-EMP014 (aktif) - November
            for ($empNum = 1; $empNum <= 14; $empNum++) {
                $empCode = 'EMP' . str_pad($empNum, 3, '0', STR_PAD_LEFT);
                $rand = rand(1, 100);

                if ($rand <= 90) {
                    // 90% hadir
                    $status = 'hadir';
                    $notes = null;
                } elseif ($rand <= 95) {
                    // 5% izin
                    $status = 'izin';
                    $notes = 'Urusan pribadi';
                } elseif ($rand <= 98) {
                    // 3% sakit
                    $status = 'sakit';
                    $notes = 'Tidak enak badan';
                } else {
                    // 2% cuti
                    $status = 'cuti';
                    $notes = 'Cuti bersama';
                }

                $attendances[] = [
                    'employee_id' => $empCode,
                    'attendance_date' => $date->format('Y-m-d'),
                    'status' => $status,
                    'overtime_hours' => null,
                    'overtime_rate' => null,
                    'overtime_total' => null,
                    'notes' => $notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('attendances')->insert($attendances);
    }
}
