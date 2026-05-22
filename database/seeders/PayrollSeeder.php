<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sdm\Payroll;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['emp' => 'EMP001', 'month' => 5, 'year' => 2026, 'base' => 5000000, 'net' => 4500000, 'status' => 'draft', 'days' => 20],
            ['emp' => 'EMP002', 'month' => 5, 'year' => 2026, 'base' => 4500000, 'net' => 4050000, 'status' => 'draft', 'days' => 20],
            ['emp' => 'EMP003', 'month' => 5, 'year' => 2026, 'base' => 6000000, 'net' => 5400000, 'status' => 'draft', 'days' => 20],
            ['emp' => 'EMP004', 'month' => 5, 'year' => 2026, 'base' => 3500000, 'net' => 3150000, 'status' => 'paid', 'days' => 20],
            ['emp' => 'EMP005', 'month' => 5, 'year' => 2026, 'base' => 4000000, 'net' => 3600000, 'status' => 'draft', 'days' => 20],
            ['emp' => 'EMP006', 'month' => 5, 'year' => 2026, 'base' => 3800000, 'net' => 3420000, 'status' => 'paid', 'days' => 20],
            ['emp' => 'EMP007', 'month' => 5, 'year' => 2026, 'base' => 4200000, 'net' => 3780000, 'status' => 'draft', 'days' => 19],
            ['emp' => 'EMP008', 'month' => 5, 'year' => 2026, 'base' => 3900000, 'net' => 3510000, 'status' => 'paid', 'days' => 20],
            ['emp' => 'EMP009', 'month' => 5, 'year' => 2026, 'base' => 3600000, 'net' => 3240000, 'status' => 'draft', 'days' => 20],
            ['emp' => 'EMP010', 'month' => 5, 'year' => 2026, 'base' => 4100000, 'net' => 3690000, 'status' => 'draft', 'days' => 20],
            ['emp' => 'EMP011', 'month' => 5, 'year' => 2026, 'base' => 3700000, 'net' => 3330000, 'status' => 'paid', 'days' => 20],
            ['emp' => 'EMP012', 'month' => 5, 'year' => 2026, 'base' => 3500000, 'net' => 3150000, 'status' => 'paid', 'days' => 18],
            ['emp' => 'EMP013', 'month' => 5, 'year' => 2026, 'base' => 4300000, 'net' => 3870000, 'status' => 'draft', 'days' => 20],
            ['emp' => 'EMP014', 'month' => 5, 'year' => 2026, 'base' => 4400000, 'net' => 3960000, 'status' => 'paid', 'days' => 20],
            ['emp' => 'EMP001', 'month' => 4, 'year' => 2026, 'base' => 5000000, 'net' => 4500000, 'status' => 'paid', 'days' => 20],
        ];

        foreach ($data as $item) {
            Payroll::create([
                'employee_id' => $item['emp'],
                'period_month' => $item['month'],
                'period_year' => $item['year'],
                'base_salary' => $item['base'],
                'net_salary' => $item['net'],
                'status' => $item['status'],
                'present_days' => $item['days'],
                'total_work_days' => 20,
            ]);
        }
    }
}
