<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification\SalaryReminder;
use App\Models\Sdm\Employee;
use App\Models\Sdm\Payroll;
use Illuminate\Http\Request;

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

        // Calculate summary statistics
        $totalReminders = SalaryReminder::forPeriod($request->month ?: date('m'), $request->year ?: date('Y'))->count();
        $totalNotified = SalaryReminder::forPeriod($request->month ?: date('m'), $request->year ?: date('Y'))
            ->byStatus('notified')->count();
        $totalPaid = SalaryReminder::forPeriod($request->month ?: date('m'), $request->year ?: date('Y'))
            ->byStatus('paid')->count();

        return view('pages.notification.salary-reminder', compact('reminders', 'totalReminders', 'totalNotified', 'totalPaid'));
    }
}

