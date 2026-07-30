<?php

namespace App\Http\Controllers;

use App\Models\Sdm\Employee;
use App\Models\Sdm\Payroll;
use App\Models\Inventory\Items;
use App\Services\Sdm\PayrollService;
use Carbon\Carbon;

class PageController extends Controller
{
    public function dashboard()
    {
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

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

        $isPayrollPeriod = true;

        // Build a map of paid payroll period_start_dates for quick lookup
        $paidPeriodStarts = collect();
        $paidPayrolls = Payroll::where('period_month', $currentMonth)
            ->where('period_year', $currentYear)
            ->where('status', 'paid')
            ->where('created_by', auth()->id())
            ->select('employee_id', 'period_start_date', 'week_number')
            ->get();

        $paidMap = [];
        foreach ($paidPayrolls as $p) {
            $paidMap[$p->employee_id][$p->period_start_date] = true;
        }

        $employeesWithoutSalary = [];
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

        return view('pages.dashboard', compact(
            'employeesWithoutSalary',
            'lowStockItems',
            'isPayrollPeriod',
            'currentWeek',
            'weekRange'
        ));
    }

    public function item()
    {
        return view('pages.item');
    }
}
