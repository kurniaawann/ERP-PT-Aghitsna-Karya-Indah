<?php
namespace Database\Seeders;
use App\Models\Sdm\Kasbon;
use App\Models\Sdm\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class KasbonSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $employees = Employee::all();
        if ($employees->isEmpty()) return;

        $counter = 1;
        foreach ($employees as $emp) {
            $amount = ($counter % 3 == 0) ? 3000000 : 1500000;
            $isTeam = $counter % 5 == 0;

            Kasbon::updateOrCreate(
                ['kasbon_code' => 'KSB' . str_pad($counter, 3, '0', STR_PAD_LEFT)],
                [
                    'employee_id' => $emp->employee_code,
                    'kasbon_type' => $isTeam ? 'team' : 'personal',
                    'division' => $emp->division,
                    'amount' => $amount,
                    'paid_amount' => 0,
                    'remaining_amount' => $amount,
                    'kasbon_date' => '2026-05-01',
                    'week_number' => 1,
                    'period_month' => 5,
                    'period_year' => 2026,
                    'period_start_date' => '2026-05-01',
                    'period_end_date' => '2026-05-07',
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'created_by' => $admin?->id,
                ]
            );
            $counter++;
        }
    }
}
