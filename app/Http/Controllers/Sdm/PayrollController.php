<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sdm\UpdatePayrollRequest;
use App\Models\Sdm\Payroll;
use App\Services\Sdm\PayrollService;
use App\Exports\Sdm\PayrollExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Controller for managing employee payroll.
 *
 * Handles HTTP requests for payroll listing, attendance validation,
 * generation, bulk payment, deletion, and export (Excel/PDF).
 *
 * All business logic is delegated to PayrollService.
 * Validation for update is handled by UpdatePayrollRequest.
 */
class PayrollController extends Controller
{
    protected PayrollService $payrollService;

    protected array $monthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * Display paginated payroll list with search and filter.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');

        $payrolls = $this->payrollService->getPaginatedPayrolls(
            $search,
            $month ? (int) $month : null,
            $year ? (int) $year : null,
        );

        return view('pages.sdm.payroll', compact('payrolls', 'search', 'month', 'year'));
    }

    /**
     * Get week options for a given month and year.
     *
     * Returns JSON array of week objects with week_number, label, start, end,
     * start_date, end_date. Used by frontend to populate the week dropdown.
     */
    public function getWeeks(Request $request)
    {
        $month = (int) $request->input('month');
        $year = (int) $request->input('year');

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            return response()->json(['weeks' => []]);
        }

        $weeks = PayrollService::getWeeksInMonth($year, $month);

        $result = array_map(function ($week) {
            $start = $week['start'];
            $end = $week['end'];

            if ($start->month === $end->month) {
                $label = 'Minggu ' . $week['week_number'] .
                    ' (' . $start->format('d') . '-' . $end->format('d M') . ')';
            } else {
                $label = 'Minggu ' . $week['week_number'] .
                    ' (' . $start->format('d M') . ' - ' . $end->format('d M') . ')';
            }

            return [
                'week_number' => $week['week_number'],
                'label' => $label,
                'start' => $start->format('d/m/Y'),
                'end' => $end->format('d/m/Y'),
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
            ];
        }, $weeks);

        return response()->json(['weeks' => $result]);
    }

    public function create()
    {
        return view('pages.sdm.payroll');
    }

    /**
     * Update a draft payroll record.
     */
    public function update(UpdatePayrollRequest $request, Payroll $payroll)
    {
        if ($payroll->status !== 'draft') {
            return redirect()->route('payroll.index')
                ->with('error', 'Payroll yang sudah dibayar tidak dapat diubah.');
        }

        $validated = $request->validated();

        if ((int) $validated['additional_expenses'] > 0 && empty(trim((string) ($validated['additional_expenses_notes'] ?? '')))) {
            return redirect()->back()
                ->withErrors([
                    'additional_expenses_notes' => 'Keterangan pengeluaran tambahan wajib diisi jika nominal lebih dari 0.',
                ])
                ->withInput();
        }

        $payroll->update([
            'project_name' => $validated['project_name'],
            'additional_expenses' => (int) $validated['additional_expenses'],
            'additional_expenses_notes' => $validated['additional_expenses_notes'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ]);

        return redirect()->route('payroll.index')
            ->with('success', 'Payroll draft berhasil diperbarui!');
    }

    /**
     * Validate attendance completeness for a given period.
     *
     * Accepts period_start_date and period_end_date from frontend.
     */
    public function checkAttendanceCompleteness(Request $request)
    {
        $startDate = Carbon::parse($request->period_start_date);
        $endDate = Carbon::parse($request->period_end_date);

        $result = $this->payrollService->validateAttendanceCompleteness($startDate, $endDate);

        return response()->json($result);
    }

    /**
     * Generate weekly payroll for daily workers.
     *
     * Accepts period_start_date and period_end_date from frontend.
     */
    public function generate(Request $request)
    {
        $startDate = Carbon::parse($request->period_start_date);
        $endDate = Carbon::parse($request->period_end_date);

        $expenses = $this->payrollService->validateAdditionalExpenses(
            $request->input('additional_expenses'),
            $request->input('additional_expenses_notes')
        );

        $result = $this->payrollService->generatePayroll(
            $startDate,
            $endDate,
            $expenses['total'],
            $expenses['notes'],
            $request->input('project_name')
        );

        if ($result['success']) {
            return redirect()->route('payroll.index')->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Bulk pay multiple selected payroll records.
     */
    public function bulkPay(Request $request)
    {
        $ids = $request->input('ids');
        $paymentDate = $request->input('payment_date', now()->toDateString());

        $result = $this->payrollService->bulkPayPayrolls($ids, $paymentDate);

        if ($result['success']) {
            return redirect()->route('payroll.index')->with('success', $result['message']);
        }

        return redirect()->route('payroll.index')->with('error', $result['message']);
    }

    /**
     * Delete selected draft payroll records in bulk.
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids');

        $result = $this->payrollService->deleteDraftPayrolls($ids);

        if ($result['success']) {
            return redirect()->route('payroll.index')->with('success', $result['message']);
        }

        return redirect()->route('payroll.index')->with('error', $result['message']);
    }

    /**
     * Export payroll data to Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        $payrolls = $this->payrollService->getPayrollsForExport(
            $month ? (int) $month : null,
            $year ? (int) $year : null,
        );

        if ($payrolls === null) {
            return redirect()->route('payroll.index')
                ->with('error', 'Tidak ada data payroll untuk diexport!');
        }

        $fileName = 'Laporan_Payroll_' . ($month ? $month . '_' : '') . ($year ? $year : 'Semua') . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new PayrollExport($payrolls, $month, $year), $fileName);
    }

    /**
     * Export payroll data to PDF (landscape A4).
     *
     * Uses period_start_date from the first payroll record to determine
     * the date range for attendance details.
     */
    public function exportPdf(Request $request)
    {
        $month = $request->input('month') ? (int) $request->input('month') : null;
        $year = $request->input('year') ? (int) $request->input('year') : null;

        $payrolls = $this->payrollService->getPayrollsForExport($month, $year);

        if ($payrolls === null) {
            return redirect()->route('payroll.index')
                ->with('error', 'Tidak ada data payroll untuk diexport!');
        }

        // Format period text
        if ($month && $year) {
            $periodText = $this->monthNames[$month] . ' ' . $year;
        } elseif ($year) {
            $periodText = 'Tahun ' . $year;
        } else {
            $periodText = 'Semua Periode';
        }

        $projectName = $payrolls->first()->project_name ?? null;

        // Load attendance data using period_start_date from payroll records
        $dateRange = '';
        $weekDays = [];

        $firstPayroll = $payrolls->first();
        if ($firstPayroll && $firstPayroll->period_start_date && $firstPayroll->period_end_date) {
            $startDate = Carbon::parse($firstPayroll->period_start_date);
            $endDate = Carbon::parse($firstPayroll->period_end_date);
            $dateRange = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');

            // Build week days array for column headers (Mon-Sat only)
            $current = $startDate->copy();
            while ($current->lte($endDate)) {
                if ($current->dayOfWeek !== Carbon::SUNDAY) {
                    $weekDays[] = $current->format('Y-m-d');
                }
                $current->addDay();
            }

            foreach ($payrolls as $payroll) {
                $payroll->attendances = \App\Models\Sdm\Attendance::where('employee_id', $payroll->employee_id)
                    ->whereBetween('attendance_date', [
                        $startDate->format('Y-m-d'),
                        $endDate->format('Y-m-d'),
                    ])
                    ->get();
            }
        } else {
            foreach ($payrolls as $payroll) {
                $payroll->attendances = collect();
            }
        }

        $totalBaseSalary = $payrolls->sum('base_salary');
        $totalDeduction = $payrolls->sum('deduction_amount');
        $totalOvertime = $payrolls->sum('overtime_total');
        $totalNetSalary = $payrolls->sum('net_salary');

        $data = [
            'payrolls' => $payrolls,
            'periodText' => $periodText,
            'projectName' => $projectName,
            'dateRange' => $dateRange,
            'weekDays' => $weekDays,
            'totalBaseSalary' => $totalBaseSalary,
            'totalDeduction' => $totalDeduction,
            'totalOvertime' => $totalOvertime,
            'totalNetSalary' => $totalNetSalary,
        ];

        $pdf = Pdf::loadView('exports.sdm.payroll-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        $filenameParts = ['Payroll'];
        if ($month) {
            $filenameParts[] = $this->monthNames[$month];
        }
        if ($year) {
            $filenameParts[] = $year;
        }
        if ($firstPayroll && $firstPayroll->period_start_date) {
            $filenameParts[] = Carbon::parse($firstPayroll->period_start_date)->format('d_M');
            if ($firstPayroll->period_end_date) {
                $filenameParts[] = 'sd';
                $filenameParts[] = Carbon::parse($firstPayroll->period_end_date)->format('d_M_Y');
            }
        }
        if (!$month && !$year) {
            $filenameParts[] = 'Semua_Periode';
        }
        $fileName = implode('_', $filenameParts) . '.pdf';

        return $pdf->download($fileName);
    }
}
