<?php

namespace App\Http\Controllers;

use App\Models\Sdm\Employee;
use App\Models\Sdm\Payroll;
use App\Models\Inventory\Items;
use App\Models\Finance\Reimburse;
use App\Services\Sdm\PayrollService;
use Carbon\Carbon;

class PageController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $currentUserIsAdmin = $user->role === 'admin';

        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // Default data (dipakai ketika role admin: reminder gaji & stok
        // tidak dirender, jadi diasumsikan kosong).
        $employeesWithoutSalary = [];
        $lowStockItems = collect();
        $isPayrollPeriod = true;
        $currentWeek = 1;
        $weekRange = ['start' => $now->startOfWeek(), 'end' => $now->endOfWeek()];

        // ─── Reminder Gaji & Stok Menipis (non-admin) ───
        if (!$currentUserIsAdmin) {
            // Get all weeks for the current month using PayrollService
            $allWeeks = PayrollService::getWeeksInMonth($currentYear, $currentMonth);

            // Find which week contains today
            $currentWeekData = null;
            foreach ($allWeeks as $week) {
                if ($now->gte($week['start']) && $now->lte($week['end'])) {
                    $currentWeekData = $week;
                    break;
                }
            }
            // If today doesn't fall in any week (e.g. Sunday), use the last week
            if (!$currentWeekData && !empty($allWeeks)) {
                $currentWeekData = end($allWeeks);
            }

            $currentWeek = $currentWeekData ? $currentWeekData['week_number'] : 1;
            $weekRange = $currentWeekData
                ? ['start' => $currentWeekData['start'], 'end' => $currentWeekData['end']]
                : ['start' => $now->startOfWeek(), 'end' => $now->endOfWeek()];

            // Build a map of paid payroll period_start_dates for quick lookup
            $paidPeriodStarts = collect();
            $paidPayrolls = Payroll::where('period_month', $currentMonth)
                ->where('period_year', $currentYear)
                ->where('status', 'paid')
                ->where('created_by', $user->id)
                ->select('employee_id', 'period_start_date', 'week_number')
                ->get();

            $paidMap = [];
            foreach ($paidPayrolls as $p) {
                $paidMap[$p->employee_id][$p->period_start_date->format('Y-m-d')] = true;
            }

            $allEmployees = Employee::all();

            foreach ($allEmployees as $employee) {
                $unpaidWeeks = [];

                foreach ($allWeeks as $week) {
                    $startStr = $week['start']->format('Y-m-d');

                    if (!isset($paidMap[$employee->employee_code][$startStr])) {
                        $unpaidWeeks[] = [
                            'week_number' => $week['week_number'],
                            'start_date' => $week['start']->format('d M'),
                            'end_date' => $week['end']->format('d M'),
                        ];
                    }
                }

                if (count($unpaidWeeks) > 0) {
                    $employeesWithoutSalary[] = [
                        'employee' => $employee,
                        'unpaid_weeks' => $unpaidWeeks,
                        'total_unpaid_weeks' => count($unpaidWeeks),
                    ];
                }
            }

            // Get items with low stock (quantity <= 5)
            $lowStockItems = Items::where('quantity', '<=', 5)->get();
        }

        // ─── Ringkasan Reimbursement (admin & super admin) ───
        // Alur: superadmin mengajukan, admin yang menyetujui/menolak.
        // Keduanya melihat ringkasan status yang sama di dashboard.
        $reimburseSummary = [
            'draft_count' => 0,
            'draft_total' => 0,
            'approved_count' => 0,
            'approved_total' => 0,
            'rejected_count' => 0,
            'rejected_total' => 0,
        ];

        if ($currentUserIsAdmin || $user->role === 'superadmin') {
            $reimburses = Reimburse::all();

            $reimburseSummary = [
                'draft_count' => $reimburses->where('status', 'draft')->count(),
                'draft_total' => (int) $reimburses->where('status', 'draft')->sum('total_amount'),
                'approved_count' => $reimburses->where('status', 'approved')->count(),
                'approved_total' => (int) $reimburses->where('status', 'approved')->sum('total_amount'),
                'rejected_count' => $reimburses->where('status', 'rejected')->count(),
                'rejected_total' => (int) $reimburses->where('status', 'rejected')->sum('total_amount'),
            ];
        }

        return view('pages.dashboard', compact(
            'employeesWithoutSalary',
            'lowStockItems',
            'isPayrollPeriod',
            'currentWeek',
            'weekRange',
            'reimburseSummary'
        ));
    }

    public function item()
    {
        return view('pages.item');
    }
}
