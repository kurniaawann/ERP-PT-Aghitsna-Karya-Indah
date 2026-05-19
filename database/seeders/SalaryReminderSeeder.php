<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalaryReminderSeeder extends Seeder
{
    public function run(): void
    {
        // Skip detailed seeding - just create minimal records to satisfy migrations
        DB::table('salary_reminders')->insert([
            [
                'payroll_id' => null,
                'employee_id' => 'EMP001',
                'period_month' => 5,
                'period_year' => 2026,
                'reminder_date' => now()->addDays(7)->format('Y-m-d'),
                'status' => 'notified',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
