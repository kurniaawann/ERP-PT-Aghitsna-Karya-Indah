<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Payroll;
use App\Models\Sdm\Employee;
use App\Models\Sdm\Attendance;
use App\Exports\PayrollExport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');

        $payrolls = Payroll::with('employee')
            ->when($search, function ($query, $search) {
                return $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->when($month, function ($query, $month) {
                return $query->where('period_month', $month);
            })
            ->when($year, function ($query, $year) {
                return $query->where('period_year', $year);
            })
            ->latest('period_year')
            ->latest('period_month')
            ->latest('created_at')
            ->paginate(10);

        return view('pages.sdm.payroll', compact('payrolls', 'search', 'month', 'year'));
    }

    /**
     * Show the form for generating payroll.
     */
    public function create()
    {
        $employees = Employee::all();
        return view('pages.sdm.payroll', compact('employees'));
    }

    /**
     * Check attendance completeness for all employees in a period.
     */
    public function checkAttendanceCompleteness(Request $request)
    {
        $month = $request->period_month;
        $year = $request->period_year;

        // Get all employees
        $employees = Employee::all();

        // Calculate working days in the period (Senin-Sabtu, exclude Minggu saja)
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $workingDays = 0;
        $allDates = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            // Exclude hanya Minggu (0) - Senin-Sabtu adalah hari kerja
            if ($currentDate->dayOfWeek !== 0) {
                $workingDays++;
                $allDates[] = $currentDate->format('Y-m-d');
            }
            $currentDate->addDay();
        }

        $incompleteEmployees = [];
        $alreadyGenerated = [];
        $newEmployees = [];

        foreach ($employees as $employee) {
            // Check if payroll already exists
            $existingPayroll = Payroll::where('employee_id', $employee->employee_code)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->first();

            if ($existingPayroll) {
                $alreadyGenerated[] = [
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                ];
                continue;
            }

            // Check if employee joined before or during this period
            $employeeJoinDate = Carbon::parse($employee->join_date);
            if ($employeeJoinDate->lessThanOrEqualTo($endDate)) {
                // This is a new employee (no payroll yet for this period)
                $newEmployees[] = [
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'join_date' => $employeeJoinDate->format('Y-m-d'),
                ];
            }

            // Get attendance data for the period
            $attendances = Attendance::where('employee_id', $employee->employee_code)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get();

            $attendanceDates = $attendances->pluck('attendance_date')->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })->toArray();

            // Calculate working dates for this employee (only count days after join date)
            $employeeWorkingDates = [];
            if ($employeeJoinDate->lessThanOrEqualTo($endDate)) {
                $employeeStartDate = $employeeJoinDate->greaterThan($startDate) ? $employeeJoinDate : $startDate;
                $currentCheckDate = $employeeStartDate->copy();

                while ($currentCheckDate->lte($endDate)) {
                    // Exclude hanya Minggu (0) - Senin-Sabtu adalah hari kerja
                    if ($currentCheckDate->dayOfWeek !== 0) {
                        $employeeWorkingDates[] = $currentCheckDate->format('Y-m-d');
                    }
                    $currentCheckDate->addDay();
                }
            }

            // Find missing dates (only weekdays after employee join date)
            $missingDates = array_diff($employeeWorkingDates, $attendanceDates);

            if (count($missingDates) > 0) {
                $incompleteEmployees[] = [
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'total_days' => count($employeeWorkingDates),
                    'filled_days' => count($attendanceDates),
                    'missing_days' => count($missingDates),
                    'missing_dates' => array_values($missingDates),
                ];
            }
        }

        // Check if there are new employees (employees without payroll for this period)
        $hasNewEmployees = count($newEmployees) > 0;

        return response()->json([
            'working_days' => $workingDays,
            'incomplete_employees' => $incompleteEmployees,
            'already_generated' => $alreadyGenerated,
            'has_new_employees' => $hasNewEmployees,
            'new_employees' => $newEmployees,
            'total_employees' => count($employees),
            'can_generate' => count($incompleteEmployees) === 0, // Can only generate if no incomplete attendance
        ]);
    }

    /**
     * Generate payroll for a specific period.
     */
    public function generate(Request $request)
    {
        $month = $request->period_month;
        $year = $request->period_year;

        // Get all employees
        $employees = Employee::all();

        foreach ($employees as $employee) {
            // Check if payroll already exists
            $existingPayroll = Payroll::where('employee_id', $employee->employee_code)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->first();

            if ($existingPayroll) {
                continue; // Skip if already generated
            }

            // Get attendance data for the period
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            $attendances = Attendance::where('employee_id', $employee->employee_code)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get();

            // Calculate attendance breakdown
            $presentDays = $attendances->where('status', 'hadir')->count();
            $permissionDays = $attendances->where('status', 'izin')->count();
            $sickDays = $attendances->where('status', 'sakit')->count();
            $leaveDays = $attendances->where('status', 'cuti')->count();
            $overtimeDays = $attendances->where('status', 'lembur')->count();

            // Calculate deduction (Rp 30,000 per day for izin/sakit only, NOT cuti)
            $deductionDays = $permissionDays + $sickDays;
            $deductionAmount = $deductionDays * 30000;

            // Calculate overtime total
            $overtimeTotal = $attendances->where('status', 'lembur')->sum('overtime_total');

            // Calculate net salary
            $netSalary = $employee->base_salary - $deductionAmount + $overtimeTotal;

            // Create payroll record
            Payroll::create([
                'employee_id' => $employee->employee_code,
                'period_month' => $month,
                'period_year' => $year,
                'base_salary' => $employee->base_salary,
                'present_days' => $presentDays,
                'permission_days' => $permissionDays,
                'sick_days' => $sickDays,
                'leave_days' => $leaveDays,
                'overtime_days' => $overtimeDays,
                'deduction_amount' => $deductionAmount,
                'overtime_total' => $overtimeTotal,
                'net_salary' => $netSalary,
                'status' => 'draft',
            ]);
        }

        return redirect()->route('payroll.index')->with('success', 'Payroll berhasil digenerate!');
    }

    /**
     * Bulk pay selected payrolls.
     */
    public function bulkPay(Request $request)
    {
        $ids = $request->input('ids');
        $paymentDate = $request->input('payment_date', now()->toDateString());

        if (empty($ids)) {
            return redirect()->route('payroll.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Only pay draft payrolls
        $updated = Payroll::whereIn('id', $ids)
            ->where('status', 'draft')
            ->update([
                'payment_date' => $paymentDate,
                'status' => 'paid',
            ]);

        if ($updated > 0) {
            return redirect()->route('payroll.index')->with('success', "Berhasil membayar {$updated} payroll!");
        }

        return redirect()->route('payroll.index')->with('error', 'Tidak ada payroll yang dapat dibayar!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('payroll.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Only delete draft payrolls
        Payroll::whereIn('id', $ids)->where('status', 'draft')->delete();

        return redirect()->route('payroll.index')->with('success', 'Data payroll berhasil dihapus!');
    }

    /**
     * Export payroll to Excel.
     */
    public function exportExcel(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        // Get payrolls with filters
        $payrolls = Payroll::with('employee')
            ->when($month, function ($query, $month) {
                return $query->where('period_month', $month);
            })
            ->when($year, function ($query, $year) {
                return $query->where('period_year', $year);
            })
            ->latest('period_year')
            ->latest('period_month')
            ->latest('created_at')
            ->get();

        if ($payrolls->isEmpty()) {
            return redirect()->route('payroll.index')->with('error', 'Tidak ada data payroll untuk diexport!');
        }

        $fileName = 'Laporan_Payroll_' . ($month ? $month . '_' : '') . ($year ? $year : 'Semua') . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new PayrollExport($payrolls, $month, $year), $fileName);
    }

    /**
     * Export payroll to PDF.
     */
    public function exportPdf(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        // Get payrolls with filters
        $payrolls = Payroll::with('employee')
            ->when($month, function ($query, $month) {
                return $query->where('period_month', $month);
            })
            ->when($year, function ($query, $year) {
                return $query->where('period_year', $year);
            })
            ->latest('period_year')
            ->latest('period_month')
            ->latest('created_at')
            ->get();

        if ($payrolls->isEmpty()) {
            return redirect()->route('payroll.index')->with('error', 'Tidak ada data payroll untuk diexport!');
        }

        // Format periode untuk header
        if ($month && $year) {
            $monthNames = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember'
            ];
            $periodText = $monthNames[$month] . ' ' . $year;
        } elseif ($year) {
            $periodText = 'Tahun ' . $year;
        } else {
            $periodText = 'Semua Periode';
        }

        // Calculate totals
        $totalBaseSalary = $payrolls->sum('base_salary');
        $totalDeduction = $payrolls->sum('deduction_amount');
        $totalOvertime = $payrolls->sum('overtime_total');
        $totalNetSalary = $payrolls->sum('net_salary');

        $data = [
            'payrolls' => $payrolls,
            'periodText' => $periodText,
            'totalBaseSalary' => $totalBaseSalary,
            'totalDeduction' => $totalDeduction,
            'totalOvertime' => $totalOvertime,
            'totalNetSalary' => $totalNetSalary,
        ];

        $pdf = Pdf::loadView('exports.payroll_pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        $fileName = 'Laporan_Payroll_' . ($month ? $month . '_' : '') . ($year ? $year : 'Semua') . '_' . date('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
    }
}
