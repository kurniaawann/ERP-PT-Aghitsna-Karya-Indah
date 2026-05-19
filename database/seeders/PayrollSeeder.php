<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sdm\Payroll;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['emp' => 'EMP001', 'month' => 5, 'year' => 2026, 'base' => 5000000, 'net' => 4500000, 'status' => 'draft'],
            ['emp' => 'EMP002', 'month' => 5, 'year' => 2026, 'base' => 4500000, 'net' => 4050000, 'status' => 'draft'],
            ['emp' => 'EMP003', 'month' => 5, 'year' => 2026, 'base' => 6000000, 'net' => 5400000, 'status' => 'draft'],
            ['emp' => 'EMP004', 'month' => 5, 'year' => 2026, 'base' => 3500000, 'net' => 3150000, 'status' => 'draft'],
            ['emp' => 'EMP005', 'month' => 5, 'year' => 2026, 'base' => 4000000, 'net' => 3600000, 'status' => 'draft'],
        ];

        foreach ($data as $item) {
            Payroll::create([
                'employee_id' => $item['emp'],
                'period_month' => $item['month'],
                'period_year' => $item['year'],
                'base_salary' => $item['base'],
                'net_salary' => $item['net'],
                'status' => $item['status'],
                'present_days' => 20,
                'total_work_days' => 20,
            ]);
        }
    }
}
