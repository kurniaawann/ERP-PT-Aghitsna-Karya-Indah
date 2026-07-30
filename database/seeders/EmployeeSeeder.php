<?php
namespace Database\Seeders;
use App\Models\Sdm\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $divisions = ['Produksi', 'Keuangan', 'SDM', 'Operasional', 'Marketing'];

        $employees = [
            ['employee_code' => 'EMP001', 'name' => 'Ahmad Kurniawan', 'division' => 'Produksi', 'daily_wage' => 100000],
            ['employee_code' => 'EMP002', 'name' => 'Siti Nurhaliza', 'division' => 'Keuangan', 'daily_wage' => 120000],
            ['employee_code' => 'EMP003', 'name' => 'Budi Santoso', 'division' => 'SDM', 'daily_wage' => 130000],
            ['employee_code' => 'EMP004', 'name' => 'Hendra Wijaya', 'division' => 'Operasional', 'daily_wage' => 140000],
            ['employee_code' => 'EMP005', 'name' => 'Rini Pratiwi', 'division' => 'Marketing', 'daily_wage' => 150000],
            ['employee_code' => 'EMP006', 'name' => 'Denny Hermawan', 'division' => 'Produksi', 'daily_wage' => 160000],
            ['employee_code' => 'EMP007', 'name' => 'Eka Putri', 'division' => 'Keuangan', 'daily_wage' => 170000],
            ['employee_code' => 'EMP008', 'name' => 'Fajar Ramadhan', 'division' => 'SDM', 'daily_wage' => 180000],
            ['employee_code' => 'EMP009', 'name' => 'Ghina Salsa', 'division' => 'Operasional', 'daily_wage' => 190000],
            ['employee_code' => 'EMP010', 'name' => 'Hartono Sulistyo', 'division' => 'Marketing', 'daily_wage' => 200000],
            ['employee_code' => 'EMP011', 'name' => 'Indra Kusuma', 'division' => 'Produksi', 'daily_wage' => 210000],
            ['employee_code' => 'EMP012', 'name' => 'Joko Wahono', 'division' => 'Keuangan', 'daily_wage' => 220000],
            ['employee_code' => 'EMP013', 'name' => 'Karina Sephira', 'division' => 'SDM', 'daily_wage' => 230000],
            ['employee_code' => 'EMP014', 'name' => 'Luthfi Rahman', 'division' => 'Operasional', 'daily_wage' => 240000],
        ];

        foreach ($employees as $emp) {
            Employee::updateOrCreate(
                ['employee_code' => $emp['employee_code']],
                [
                    'name' => $emp['name'],
                    'phone' => fake()->phoneNumber(),
                    'address' => fake()->address(),
                    'division' => $emp['division'],
                    'daily_wage' => $emp['daily_wage'],
                    'join_date' => '2025-09-01',
                    'created_by' => $admin?->id,
                ]
            );
        }
    }
}
