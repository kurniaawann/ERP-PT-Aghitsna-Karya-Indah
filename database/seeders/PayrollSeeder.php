<?php
namespace Database\Seeders;
use App\Models\Sdm\Payroll;
use App\Models\Sdm\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $employees = Employee::all();
        if ($employees->isEmpty()) return;

        $counter = 1;
        foreach ($employees as $emp) {
            Payroll::updateOrCreate(
                ['employee_id' => $emp->employee_code, 'period_start_date' => '2026-05-01'],
                [
                    'period_month' => 5,
                    'period_year' => 2026,
                    'period_type' => 'weekly',
                    'week_number' => 1,
                    'period_start_date' => '2026-05-01',
                    'period_end_date' => '2026-05-07',
                    'base_salary' => $emp->daily_wage * 26,
                    'total_work_days' => 26,
                    'present_days' => 22,
                    'permission_days' => 1,
                    'sick_days' => 1,
                    'leave_days' => 1,
                    'overtime_days' => 1,
                    'net_salary' => $emp->daily_wage * 22,
                    'status' => $counter % 3 == 0 ? 'paid' : 'draft',
                    'payment_date' => $counter % 3 == 0 ? '2026-05-25' : null,
                    'created_by' => $admin?->id,
                ]
            );
            $counter++;
        }
    }
}
