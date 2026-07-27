<?php
namespace Database\Seeders;
use App\Models\Sdm\Attendance;
use App\Models\Sdm\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $employees = Employee::all();
        if ($employees->isEmpty()) return;

        $statuses = ['hadir', 'hadir', 'hadir', 'hadir', 'hadir', 'hadir', 'hadir', 'hadir', 'izin', 'sakit', 'cuti'];
        $startDate = Carbon::create(2025, 10, 1);
        $endDate = Carbon::create(2025, 12, 31);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->isSunday()) continue;
            foreach ($employees as $emp) {
                Attendance::updateOrCreate(
                    ['employee_id' => $emp->employee_code, 'attendance_date' => $date->toDateString()],
                    [
                        'status' => $statuses[array_rand($statuses)],
                        'created_by' => $admin?->id,
                    ]
                );
            }
        }
    }
}
