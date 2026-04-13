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

        $employees = [];
        $names = [
            'Ahmad Kurniawan',
            'Siti Nurhaliza',
            'Budi Santoso',
            'Hendra Wijaya',
            'Rini Pratiwi',
            'Denny Hermawan',
            'Eka Putri',
            'Fajar Ramadhan',
            'Ghina Salsa',
            'Hartono Sulistyo',
            'Indra Kusuma',
            'Joko Wahono',
            'Karina Sephira',
            'Luthfi Rahman'
        ];

        $divisions = ['Produksi', 'Keuangan', 'SDM', 'Operasional', 'Marketing'];

        // Generate 14 employees (EMP001-EMP014) untuk kebutuhan AttendanceSeeder
        for ($i = 1; $i <= 14; $i++) {
            $empCode = 'EMP' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $name = $names[$i - 1] ?? "Karyawan $i";
            $phone = '0812345678' . str_pad($i, 2, '0', STR_PAD_LEFT);
            $division = $divisions[($i - 1) % count($divisions)];

            $employees[] = [
                'employee_code' => $empCode,
                'name' => $name,
                'phone' => $phone,
                'address' => "Jl. Merdeka No. $i, Jakarta",
                'division' => $division,
                'daily_wage' => 100000 + ($i * 10000),
                'position' => null,
                'email' => null,
                'base_salary' => null,
                'join_date' => '2025-09-01',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('employees')->insert($employees);
    }
}
