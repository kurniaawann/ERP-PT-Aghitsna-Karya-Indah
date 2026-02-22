<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus data lama terlebih dahulu (gunakan delete karena ada foreign key)
        DB::table('employees')->delete();

        $employees = [
            // Karyawan 1: Join Januari (sebelum 2 Feb) - Harus ada 6 absensi (2-7 Feb)
            [
                'employee_code' => 'EMP001',
                'name' => 'Ahmad Kurniawan',
                'phone' => '081234567890',
                'address' => 'Jl. Merdeka No. 123, Jakarta',
                'division' => 'Produksi',
                'daily_wage' => 150000,
                'position' => null,
                'email' => null,
                'base_salary' => null,
                'join_date' => '2026-01-15', // Join sebelum 2 Feb
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Karyawan 2: Join 5 Februari - Harus ada 3 absensi (5,6,7 Feb)
            [
                'employee_code' => 'EMP002',
                'name' => 'Siti Nurhaliza',
                'phone' => '081234567891',
                'address' => 'Jl. Sudirman No. 45, Jakarta',
                'division' => 'Produksi',
                'daily_wage' => 140000,
                'position' => null,
                'email' => null,
                'base_salary' => null,
                'join_date' => '2026-02-05', // Join tanggal 5 Feb
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Karyawan 3: Join Januari (sebelum 2 Feb) - Harus ada 6 absensi (2-7 Feb)
            [
                'employee_code' => 'EMP003',
                'name' => 'Budi Santoso',
                'phone' => '081234567892',
                'address' => 'Jl. Gatot Subroto No. 78, Jakarta',
                'division' => 'Produksi',
                'daily_wage' => 130000,
                'position' => null,
                'email' => null,
                'base_salary' => null,
                'join_date' => '2026-01-20', // Join sebelum 2 Feb
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('employees')->insert($employees);
    }
}
