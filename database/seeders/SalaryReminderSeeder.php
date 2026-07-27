<?php
namespace Database\Seeders;
use App\Models\Notification\SalaryReminder;
use App\Models\Sdm\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class SalaryReminderSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $employees = Employee::all();
        if ($employees->isEmpty()) return;

        foreach ($employees as $emp) {
            SalaryReminder::create([
                'employee_id' => $emp->employee_code,
                'period_month' => 5,
                'period_year' => 2026,
                'reminder_date' => '2026-05-25',
                'status' => 'draft',
                'created_by' => $admin?->id,
            ]);
        }
    }
}
