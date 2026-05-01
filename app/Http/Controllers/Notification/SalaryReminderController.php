<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification\SalaryReminder;
use App\Models\Sdm\Employee;
use App\Models\Sdm\Payroll;
use App\Models\Sdm\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SalaryReminderController extends Controller
{
    /**
     * Tampilkan halaman laporan reminder gaji
     */
    public function index(Request $request)
    {
        $query = SalaryReminder::with(['employee', 'payroll']);

        // Filter berdasarkan bulan
        if ($request->filled('month')) {
            $query->where('period_month', $request->month);
        }

        // Filter berdasarkan tahun
        if ($request->filled('year')) {
            $query->where('period_year', $request->year);
        } else {
            // Default tahun saat ini
            $query->where('period_year', date('Y'));
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search berdasarkan nama karyawan atau employee_id
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('employee_id', 'like', '%' . $request->search . '%')
                    ->orWhereHas('employee', function ($subq) use ($request) {
                        $subq->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Sorting berdasarkan gaji (base_salary dari employee)
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'salary') {
            // Join dengan employee table untuk sort berdasarkan salary
            $query->join('employees', 'salary_reminders.employee_id', '=', 'employees.employee_code')
                ->select('salary_reminders.*')
                ->orderBy('employees.base_salary', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $reminders = $query->paginate(10)->appends($request->all());

        // Calculate summary statistics berdasarkan data yang sudah di-filter (sebelum paginate)
        $totalReminders = $query->count();
        $totalDraft = $query->clone()->byStatus('draft')->count();
        $totalPaid = $query->clone()->byStatus('paid')->count();

        /**
         * Attendance Reminders - Untuk karyawan yang sudah absen minggu 1-4 tapi payroll belum dibuat
         * 
         * Queries:
         * 1. Ambil attendance untuk minggu 1-4 yang sudah ada
         * 2. Group by employee, bulan, tahun
         * 3. Filter yang belum ada payroll
         */
        $attendanceReminders = [];

        // Ambil bulan dan tahun yang sedang difilter (atau current month/year)
        $filterMonth = $request->filled('month') ? (int) $request->month : (int) date('m');
        $filterYear = $request->filled('year') ? (int) $request->year : (int) date('Y');

        // Query untuk attendance yang sudah ada di minggu 1-4
        $attendanceData = Attendance::with('employee')
            ->selectRaw('employee_id, MONTH(attendance_date) as month, YEAR(attendance_date) as year, MIN(attendance_date) as first_date, MAX(attendance_date) as last_date, COUNT(*) as total_attendance')
            ->whereRaw('DAY(attendance_date) BETWEEN 1 AND 28') // Minggu 1-4
            ->whereRaw('YEAR(attendance_date) = ?', [$filterYear])
            ->when($filterMonth > 0, function ($q) use ($filterMonth) {
                $q->whereRaw('MONTH(attendance_date) = ?', [$filterMonth]);
            })
            ->groupByRaw('employee_id, MONTH(attendance_date), YEAR(attendance_date)')
            ->get();

        // Filter attendance yang belum ada payroll
        foreach ($attendanceData as $attendance) {
            $payrollExists = Payroll::where('employee_id', $attendance->employee_id)
                ->where('period_month', $attendance->month)
                ->where('period_year', $attendance->year)
                ->exists();

            // Jika attendance ada tapi payroll belum dibuat
            if (!$payrollExists) {
                $employee = Employee::where('employee_code', $attendance->employee_id)->first();

                if ($employee) {
                    // Hitung minggu berdasarkan tanggal
                    $dayOfMonth = $attendance->first_date->day;
                    $week = ceil($dayOfMonth / 7);

                    $attendanceReminders[] = (object) [
                        'employee_id' => $attendance->employee_id,
                        'employee_name' => $employee->name,
                        'period_month' => $attendance->month,
                        'period_year' => $attendance->year,
                        'week_number' => $week,
                        'first_attendance_date' => $attendance->first_date,
                        'last_attendance_date' => $attendance->last_date,
                        'employee' => $employee,
                    ];
                }
            }
        }

        return view('pages.notification.salary-reminder', compact('reminders', 'totalReminders', 'totalDraft', 'totalPaid', 'attendanceReminders'));
    }
}

