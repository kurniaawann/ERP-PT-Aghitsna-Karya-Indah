<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Payroll;
use App\Models\Sdm\Employee;
use App\Models\Sdm\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

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
            ->paginate(10);

        return view('pages.sdm.payroll', compact('payrolls', 'search', 'month', 'year'));
    }

    /**
     * Show the form for generating payroll.
     */
    public function create()
    {
        $employees = Employee::active()->get();
        return view('pages.sdm.payroll', compact('employees'));
    }

    /**
     * Generate payroll for a specific period.
     */
    public function generate(Request $request)
    {
        $month = $request->period_month;
        $year = $request->period_year;

        // Get all active employees
        $employees = Employee::active()->get();

        foreach ($employees as $employee) {
            // Check if payroll already exists
            $existingPayroll = Payroll::where('employee_id', $employee->id)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->first();

            if ($existingPayroll) {
                continue; // Skip if already generated
            }

            // Get attendance data for the period
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get();

            // Calculate attendance breakdown
            $presentDays = $attendances->where('status', 'hadir')->count();
            $permissionDays = $attendances->where('status', 'izin')->count();
            $sickDays = $attendances->where('status', 'sakit')->count();
            $leaveDays = $attendances->where('status', 'cuti')->count();
            $overtimeDays = $attendances->where('status', 'lembur')->count();

            // Calculate deduction (Rp 30,000 per day for izin/sakit/cuti)
            $deductionDays = $permissionDays + $sickDays + $leaveDays;
            $deductionAmount = $deductionDays * 30000;

            // Calculate overtime total
            $overtimeTotal = $attendances->where('status', 'lembur')->sum('overtime_total');

            // Calculate net salary
            $netSalary = $employee->base_salary - $deductionAmount + $overtimeTotal;

            // Create payroll record
            Payroll::create([
                'employee_id' => $employee->id,
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
     * Update payroll status to paid.
     */
    public function pay(Request $request, Payroll $payroll)
    {
        $payroll->update([
            'payment_date' => $request->payment_date,
            'status' => 'paid',
        ]);

        return redirect()->route('payroll.index')->with('success', 'Payroll berhasil dibayar!');
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
    public function exportExcel()
    {
        // TODO: Implement Excel export
        return redirect()->route('payroll.index')->with('info', 'Export Excel will be implemented soon!');
    }

    /**
     * Export payroll to PDF.
     */
    public function exportPdf()
    {
        // TODO: Implement PDF export
        return redirect()->route('payroll.index')->with('info', 'Export PDF will be implemented soon!');
    }
}
