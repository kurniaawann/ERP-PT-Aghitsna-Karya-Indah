<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalaryReminderSeeder extends Seeder
{
    public function run(): void
    {
        $reminders = [];
        $employees = ['EMP001', 'EMP002', 'EMP003', 'EMP004', 'EMP005', 'EMP006', 'EMP007', 'EMP008', 'EMP009', 'EMP010', 'EMP011', 'EMP012', 'EMP013', 'EMP014', 'EMP001'];

        for ($i = 0; $i < 15; $i++) {
            $reminders[] = [
                'payroll_id' => null,
                'employee_id' => $employees[$i],
                'period_month' => 5,
                'period_year' => 2026,
                'reminder_date' => now()->addDays(rand(1, 10))->format('Y-m-d'),
                // enum status salary_reminders (lihat migration 2026_05_01_000000_update_salary_reminders_status):
                // ['draft', 'paid'] DEFAULT 'draft'
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('salary_reminders')->insert($reminders);
    }
}
