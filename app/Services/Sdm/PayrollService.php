<?php

namespace App\Services\Sdm;

use App\Models\Sdm\Payroll;
use App\Models\Sdm\Employee;
use App\Models\Sdm\Attendance;
use App\Models\Sdm\Kasbon;
use App\Models\Notification\SalaryReminder;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Service for managing payroll business logic.
 *
 * Handles payroll listing, attendance validation, generation,
 * bulk payment, deletion, and export data preparation.
 * All salary calculation logic is centralized here.
 *
 * Period identification uses period_start_date as the primary key.
 * Weeks run Monday-Saturday and are NOT cut at month boundaries.
 */
class PayrollService
{
    /**
     * Get paginated list of payrolls with employee relation.
     *
     * Supports filtering by search (name/code), month, year.
     * Results are sorted by newest period first.
     *
     * @param  string|null  $search
     * @param  int|null     $month
     * @param  int|null     $year
     * @param  int          $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedPayrolls(
        ?string $search,
        ?int $month,
        ?int $year,
        int $perPage = 15
    ): LengthAwarePaginator {
        return Payroll::with('employee')
            ->when($search, function ($query, $search) {
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->when($month, fn($query) => $query->whereMonth('period_start_date', $month))
            ->when($year, fn($query) => $query->whereYear('period_start_date', $year))
            ->latest('period_start_date')
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * Validate attendance completeness for all employees in a given week period.
     *
     * Checks:
     * 1. Which employees already have payroll for this period
     * 2. Which employees have incomplete attendance (missing days)
     * 3. Which employees have personal kasbon exceeding max salary
     * 4. Which divisions have team kasbon exceeding total division salary
     *
     * Returns a comprehensive status report used by the frontend to
     * determine whether generation is allowed.
     *
     * Query optimization: Batch-loads all existing payrolls and attendance
     * records upfront instead of per-employee queries (N+1 fix).
     *
     * @param  Carbon  $periodStartDate
     * @param  Carbon  $periodEndDate
     * @return array
     */
    public function validateAttendanceCompleteness(Carbon $periodStartDate, Carbon $periodEndDate): array
    {
        $employees = Employee::all();

        $startDate = $periodStartDate->copy();
        $endDate = $periodEndDate->copy();

        // working_days = Mon-Sat calendar days (Sundays excluded by loop)
        $workingDays = $this->countWorkingDays($startDate, $endDate);

        // === BATCH QUERIES (N+1 fix) ===
        $existingPayrollEmployeeIds = Payroll::where('period_start_date', $startDate->format('Y-m-d'))
            ->pluck('employee_id')
            ->toArray();

        $allAttendances = Attendance::whereIn('employee_id', $employees->pluck('employee_code'))
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        $allPersonalKasbons = Kasbon::whereIn('employee_id', $employees->pluck('employee_code'))
            ->where('period_start_date', $startDate->format('Y-m-d'))
            ->pending()
            ->get()
            ->groupBy('employee_id');

        $allTeamKasbons = Kasbon::where('kasbon_type', 'team')
            ->where('period_start_date', $startDate->format('Y-m-d'))
            ->where('status', 'pending')
            ->get();
        // === END BATCH QUERIES ===

        $incompleteEmployees = [];
        $completeEmployees = [];
        $alreadyGenerated = [];
        $newEmployees = [];

        foreach ($employees as $employee) {
            if (in_array($employee->employee_code, $existingPayrollEmployeeIds)) {
                $alreadyGenerated[] = [
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                ];
                continue;
            }

            $employeeJoinDate = $employee->join_date ? Carbon::parse($employee->join_date) : $startDate->copy();

            if ($employeeJoinDate->greaterThan($endDate)) {
                continue;
            }

            $newEmployees[] = [
                'name' => $employee->name,
                'employee_code' => $employee->employee_code,
                'join_date' => $employee->join_date ? $employeeJoinDate->format('Y-m-d') : null,
            ];

            $employeeWorkingDates = [];
            $employeeStartDate = $employeeJoinDate->greaterThan($startDate) ? $employeeJoinDate : $startDate;
            $currentCheckDate = $employeeStartDate->copy();

            while ($currentCheckDate->lte($endDate)) {
                if ($currentCheckDate->dayOfWeek !== Carbon::SUNDAY) {
                    $employeeWorkingDates[] = $currentCheckDate->format('Y-m-d');
                }
                $currentCheckDate->addDay();
            }

            $employeeAttendances = $allAttendances->get($employee->employee_code, new Collection());
            $attendanceDates = $employeeAttendances->pluck('attendance_date')->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })->toArray();

            $missingDates = array_diff($employeeWorkingDates, $attendanceDates);

            $requiredDays = count($employeeWorkingDates);
            $filledDays = count($attendanceDates);

            if ($filledDays < $requiredDays || count($missingDates) > 0) {
                $incompleteEmployees[] = [
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'total_days' => $requiredDays,
                    'filled_days' => $filledDays,
                    'missing_days' => count($missingDates),
                    'missing_dates' => array_values($missingDates),
                    'join_date' => $employeeJoinDate->format('Y-m-d'),
                ];
            } else {
                $completeEmployees[] = [
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'total_days' => $requiredDays,
                    'filled_days' => $filledDays,
                ];
            }
        }

        $hasNewEmployees = count($newEmployees) > 0;

        // === KASBON VALIDATION ===
        $kasbonIssues = [];
        $divisionTotals = [];

        foreach ($employees as $employee) {
            $dailyWage = $employee->daily_wage ?? $employee->base_salary;
            $maxSalary = $dailyWage * 6;

            if ($employee->division) {
                if (!isset($divisionTotals[$employee->division])) {
                    $divisionTotals[$employee->division] = [
                        'total_salary' => 0,
                        'employee_count' => 0,
                    ];
                }
                $divisionTotals[$employee->division]['total_salary'] += $maxSalary;
                $divisionTotals[$employee->division]['employee_count']++;
            }
        }

        foreach ($employees as $employee) {
            $dailyWage = $employee->daily_wage ?? $employee->base_salary;
            $maxSalary = $dailyWage * 6;

            $personalKasbonTotal = $allPersonalKasbons
                ->get($employee->employee_code, new Collection())
                ->sum('amount');

            if ($personalKasbonTotal > $maxSalary) {
                $kasbonIssues[] = [
                    'type' => 'personal',
                    'employee_name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'kasbon_amount' => $personalKasbonTotal,
                    'max_salary' => $maxSalary,
                    'daily_wage' => $dailyWage,
                ];
            }
        }

        foreach ($allTeamKasbons as $teamKasbon) {
            if ($teamKasbon->division && isset($divisionTotals[$teamKasbon->division])) {
                $divisionMaxSalary = $divisionTotals[$teamKasbon->division]['total_salary'];

                if ($teamKasbon->amount > $divisionMaxSalary) {
                    $kasbonIssues[] = [
                        'type' => 'team',
                        'division' => $teamKasbon->division,
                        'kasbon_amount' => $teamKasbon->amount,
                        'max_salary' => $divisionMaxSalary,
                        'employee_count' => $divisionTotals[$teamKasbon->division]['employee_count'],
                    ];
                }
            }
        }

        $canGenerate = count($newEmployees) > 0
            && count($incompleteEmployees) === 0
            && count($kasbonIssues) === 0;

        return [
            'working_days' => $workingDays,
            'period_start' => $startDate->format('d/m/Y'),
            'period_end' => $endDate->format('d/m/Y'),
            'period_start_day' => $startDate->format('l, d M Y'),
            'period_end_day' => $endDate->format('l, d M Y'),
            'incomplete_employees' => $incompleteEmployees,
            'complete_employees' => $completeEmployees,
            'already_generated' => $alreadyGenerated,
            'has_new_employees' => $hasNewEmployees,
            'new_employees' => $newEmployees,
            'total_employees' => count($employees),
            'kasbon_issues' => $kasbonIssues,
            'can_generate' => $canGenerate,
        ];
    }

    /**
     * Generate weekly payroll for daily workers.
     *
     * Calculation process:
     * 1. Validate attendance completeness (reject if incomplete)
     * 2. Calculate team kasbon per division
     * 3. For each employee: daily_wage x present_days + overtime - kasbon
     * 4. Create payroll record with status 'draft'
     * 5. Create SalaryReminder record
     * 6. Mark personal and team kasbons as deducted
     *
     * @param  Carbon        $periodStartDate
     * @param  Carbon        $periodEndDate
     * @param  int           $additionalExpenses
     * @param  string|null   $additionalExpensesNotes
     * @param  string|null   $projectName
     * @return array  ['success' => bool, 'message' => string]
     */
    public function generatePayroll(
        Carbon $periodStartDate,
        Carbon $periodEndDate,
        int $additionalExpenses = 0,
        ?string $additionalExpensesNotes = null,
        ?string $projectName = null
    ): array {
        $startDate = $periodStartDate->copy();
        $endDate = $periodEndDate->copy();

        $workingDays = $this->countWorkingDays($startDate, $endDate);

        $periodMonth = $startDate->month;
        $periodYear = $startDate->year;

        // Detect week_number from getWeeksInMonth
        $weeks = static::getWeeksInMonth($periodYear, $periodMonth);
        $weekNumber = 1;
        foreach ($weeks as $index => $week) {
            if ($week['start']->format('Y-m-d') === $startDate->format('Y-m-d')) {
                $weekNumber = $week['week_number'];
                break;
            }
        }

        // === ATTENDANCE VALIDATION ===
        $employees = Employee::all();
        $incompleteEmployees = [];

        $existingPayrollEmployeeIds = Payroll::where('period_start_date', $startDate->format('Y-m-d'))
            ->pluck('employee_id')
            ->toArray();

        $allAttendances = Attendance::whereIn('employee_id', $employees->pluck('employee_code'))
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        foreach ($employees as $employee) {
            if (in_array($employee->employee_code, $existingPayrollEmployeeIds)) {
                continue;
            }

            $employeeJoinDate = $employee->join_date ? Carbon::parse($employee->join_date) : $startDate->copy();

            if ($employeeJoinDate->greaterThan($endDate)) {
                continue;
            }

            $employeeWorkingDates = [];
            $employeeStartDate = $employeeJoinDate->greaterThan($startDate) ? $employeeJoinDate : $startDate;
            $currentCheckDate = $employeeStartDate->copy();

            while ($currentCheckDate->lte($endDate)) {
                if ($currentCheckDate->dayOfWeek !== Carbon::SUNDAY) {
                    $employeeWorkingDates[] = $currentCheckDate->format('Y-m-d');
                }
                $currentCheckDate->addDay();
            }

            $employeeAttendances = $allAttendances->get($employee->employee_code, new Collection());
            $attendanceDates = $employeeAttendances->pluck('attendance_date')->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })->toArray();

            $missingDates = array_diff($employeeWorkingDates, $attendanceDates);

            $requiredDays = count($employeeWorkingDates);
            $filledDays = count($attendanceDates);

            if ($filledDays < $requiredDays || count($missingDates) > 0) {
                $incompleteEmployees[] = [
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'total_days' => $requiredDays,
                    'filled_days' => $filledDays,
                    'missing_days' => count($missingDates),
                    'missing_dates' => $missingDates,
                ];
            }
        }

        if (count($incompleteEmployees) > 0) {
            $errorMessage = '<strong>Tidak dapat generate payroll!</strong><br>Data absensi belum lengkap untuk beberapa karyawan.<br><br>';

            foreach ($incompleteEmployees as $emp) {
                $errorMessage .= '❌ <strong>' . $emp['name'] . '</strong> (' . $emp['employee_code'] . '): ';
                $errorMessage .= '<strong class="text-red-600">' . $emp['filled_days'] . '</strong> dari <strong>' . $emp['total_days'] . '</strong> hari kerja';

                if (!empty($emp['missing_dates'])) {
                    $dates = array_map(fn($date) => Carbon::parse($date)->format('d/m'), $emp['missing_dates']);
                    $errorMessage .= '<br>&nbsp;&nbsp;&nbsp;Tanggal kosong: ' . implode(', ', $dates);
                }
                $errorMessage .= '<br>';
            }

            $errorMessage .= '<br><strong>Catatan:</strong> Setiap karyawan harus memiliki absensi lengkap untuk semua hari kerjanya.<br>Silakan lengkapi data absensi di menu <strong>SDM → Absensi</strong> terlebih dahulu.';

            return ['success' => false, 'message' => $errorMessage];
        }

        // === CALCULATE TEAM KASBON PER DIVISION ===
        $divisionGroups = $employees->groupBy('division');

        $allTeamKasbons = Kasbon::where('kasbon_type', 'team')
            ->where('period_start_date', $startDate->format('Y-m-d'))
            ->where('status', 'pending')
            ->get()
            ->groupBy('division');

        $kasbonPerDivision = [];
        foreach ($divisionGroups as $division => $divisionEmployees) {
            if ($division) {
                $totalTeamKasbonForDivision = $allTeamKasbons->get($division, new Collection())->sum('amount');
                $employeeCountInDivision = $divisionEmployees->count();
                $kasbonPerDivision[$division] = $employeeCountInDivision > 0
                    ? $totalTeamKasbonForDivision / $employeeCountInDivision
                    : 0;
            }
        }

        $allPersonalKasbons = Kasbon::whereIn('employee_id', $employees->pluck('employee_code'))
            ->where('period_start_date', $startDate->format('Y-m-d'))
            ->pending()
            ->get()
            ->groupBy('employee_id');

        // === GENERATE PAYROLL PER EMPLOYEE ===
        $payrolls = [];
        $personalKasbonIdsToMark = [];

        foreach ($employees as $employee) {
            if (in_array($employee->employee_code, $existingPayrollEmployeeIds)) {
                continue;
            }

            $employeeAttendances = $allAttendances->get($employee->employee_code, new Collection());

            $presentDays = $employeeAttendances->whereIn('status', ['hadir', 'lembur'])->count();
            $permissionDays = $employeeAttendances->where('status', 'izin')->count();
            $sickDays = $employeeAttendances->where('status', 'sakit')->count();
            $leaveDays = $employeeAttendances->where('status', 'cuti')->count();
            $overtimeDays = $employeeAttendances->where('status', 'lembur')->count();

            $dailyWage = $employee->daily_wage ?? $employee->base_salary;
            $totalWage = $dailyWage * $presentDays;

            $overtimeTotal = $employeeAttendances->where('status', 'lembur')->sum('overtime_total');

            $grossWage = $totalWage + $overtimeTotal;

            $personalKasbon = $allPersonalKasbons
                ->get($employee->employee_code, new Collection())
                ->sum('amount');

            $teamKasbonPerPerson = $employee->division && isset($kasbonPerDivision[$employee->division])
                ? $kasbonPerDivision[$employee->division]
                : 0;

            $totalKasbonDeduction = $personalKasbon + $teamKasbonPerPerson;
            $netWage = $grossWage - $totalKasbonDeduction;

            $payroll = Payroll::create([
                'employee_id' => $employee->employee_code,
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'period_type' => 'weekly',
                'week_number' => $weekNumber,
                'period_start_date' => $startDate->format('Y-m-d'),
                'period_end_date' => $endDate->format('Y-m-d'),
                'project_name' => $projectName,
                'base_salary' => $dailyWage,
                'total_work_days' => $workingDays,
                'present_days' => $presentDays,
                'permission_days' => $permissionDays,
                'sick_days' => $sickDays,
                'leave_days' => $leaveDays,
                'overtime_days' => $overtimeDays,
                'deduction_amount' => 0,
                'overtime_total' => $overtimeTotal,
                'kasbon_deduction' => $totalKasbonDeduction,
                'additional_expenses' => $additionalExpenses,
                'additional_expenses_notes' => $additionalExpensesNotes,
                'net_salary' => $netWage,
                'status' => 'draft',
            ]);

            SalaryReminder::updateOrCreate(
                ['payroll_id' => $payroll->id],
                [
                    'employee_id' => $employee->employee_code,
                    'period_month' => $periodMonth,
                    'period_year' => $periodYear,
                    'reminder_date' => Carbon::now(),
                    'status' => $payroll->status ?? 'draft',
                    'notification_sent_at' => null,
                    'notes' => 'Reminder gaji untuk periode ' . $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'),
                ]
            );

            $payrolls[] = $payroll;

            $employeeKasbonIds = $allPersonalKasbons
                ->get($employee->employee_code, new Collection())
                ->pluck('id')
                ->toArray();
            $personalKasbonIdsToMark = array_merge($personalKasbonIdsToMark, $employeeKasbonIds);
        }

        // Mark personal kasbons as deducted (batch update)
        if (!empty($personalKasbonIdsToMark)) {
            Kasbon::whereIn('id', $personalKasbonIdsToMark)->update([
                'status' => 'deducted',
            ]);
        }

        // Mark team kasbons as deducted
        if (!empty($payrolls)) {
            $payrollCollection = collect($payrolls);
            $processedDivisions = [];

            foreach ($employees as $employee) {
                if ($employee->division && !in_array($employee->division, $processedDivisions)) {
                    $teamKasbonsForDivision = $allTeamKasbons->get($employee->division, new Collection());

                    foreach ($teamKasbonsForDivision as $kasbon) {
                        $firstPayrollInDivision = $payrollCollection->first();

                        if ($firstPayrollInDivision) {
                            $kasbon->update([
                                'status' => 'deducted',
                                'deducted_in_payroll_id' => $firstPayrollInDivision->id,
                            ]);
                        }
                    }

                    $processedDivisions[] = $employee->division;
                }
            }
        }

        return ['success' => true, 'message' => 'Payroll berhasil digenerate!'];
    }

    /**
     * Bulk pay multiple payroll records.
     *
     * Updates status from 'draft' to 'paid' and sets the payment date.
     * Also syncs SalaryReminder status for the paid payrolls.
     *
     * @param  array   $ids     Array of payroll IDs
     * @param  string  $paymentDate  Payment date (Y-m-d)
     * @return array   ['success' => bool, 'message' => string, 'count' => int]
     */
    public function bulkPayPayrolls(array $ids, string $paymentDate): array
    {
        if (empty($ids)) {
            return ['success' => false, 'message' => 'Tidak ada data yang dipilih!', 'count' => 0];
        }

        $updated = Payroll::whereIn('id', $ids)
            ->where('status', 'draft')
            ->update([
                'payment_date' => $paymentDate,
                'status' => 'paid',
            ]);

        if ($updated > 0) {
            SalaryReminder::whereIn('payroll_id', $ids)->update([
                'status' => 'paid',
                'notification_sent_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => "Berhasil membayar {$updated} payroll!",
                'count' => $updated,
            ];
        }

        return ['success' => false, 'message' => 'Tidak ada payroll yang dapat dibayar!', 'count' => 0];
    }

    /**
     * Delete draft payroll records in bulk.
     *
     * Only payroll with status 'draft' can be deleted.
     * Paid payroll is protected from deletion.
     *
     * @param  array  $ids  Array of payroll IDs
     * @return array  ['success' => bool, 'message' => string]
     */
    public function deleteDraftPayrolls(array $ids): array
    {
        if (empty($ids)) {
            return ['success' => false, 'message' => 'Tidak ada data yang dipilih!'];
        }

        Payroll::whereIn('id', $ids)->where('status', 'draft')->delete();

        return ['success' => true, 'message' => 'Data payroll berhasil dihapus!'];
    }

    /**
     * Get payrolls collection for Excel/PDF export.
     *
     * Supports filtering by month and year.
     * Includes employee relationship to avoid N+1 in export.
     *
     * @param  int|null  $month
     * @param  int|null  $year
     * @return Collection|null  Null if no data found
     */
    public function getPayrollsForExport(?int $month, ?int $year): ?Collection
    {
        $payrolls = Payroll::with('employee')
            ->when($month, fn($query) => $query->whereMonth('period_start_date', $month))
            ->when($year, fn($query) => $query->whereYear('period_start_date', $year))
            ->latest('period_start_date')
            ->latest('created_at')
            ->get();

        return $payrolls->isEmpty() ? null : $payrolls;
    }

    /**
     * Get payrolls for export filtered by a specific date range.
     *
     * @param  Carbon  $periodStartDate
     * @param  Carbon  $periodEndDate
     * @return Collection|null
     */
    public function getPayrollsForExportByDateRange(Carbon $periodStartDate, Carbon $periodEndDate): ?Collection
    {
        $payrolls = Payroll::with('employee')
            ->where('period_start_date', $periodStartDate->format('Y-m-d'))
            ->where('period_end_date', $periodEndDate->format('Y-m-d'))
            ->get();

        return $payrolls->isEmpty() ? null : $payrolls;
    }

    /**
     * Get all weeks in a month using Monday-Saturday week system.
     *
     * Each week runs from Monday to Saturday (6 working days).
     * Weeks are NOT cut at month boundaries — a week that starts in
     * February and ends in March is treated as a single period.
     *
     * If the month does not start on Monday, the days before the first
     * Monday form a partial "Week 1" (e.g. Wed-Sat = 4 days).
     *
     * Example for February 2028 (1 Feb = Wednesday):
     *   Week 1: Feb 1-4   (Wed-Sat) = 4 days
     *   Week 2: Feb 6-11  (Mon-Sat) = 6 days
     *   Week 3: Feb 13-18 (Mon-Sat) = 6 days
     *   Week 4: Feb 20-25 (Mon-Sat) = 6 days
     *   Week 5: Feb 27 - Mar 4 (Mon-Sat) = 6 days (cross-month)
     *
     * Sunday is always excluded as a non-working day.
     *
     * @param  int  $year
     * @param  int  $month
     * @return array<int, array{week_number: int, start: Carbon, end: Carbon}>
     */
    public static function getWeeksInMonth(int $year, int $month): array
    {
        $firstDayOfMonth = Carbon::create($year, $month, 1);

        $weeks = [];
        $weekNumber = 1;
        $currentDate = $firstDayOfMonth->copy();

        while (true) {
            // Skip Sundays — they are never a week start
            if ($currentDate->dayOfWeek === Carbon::SUNDAY) {
                $currentDate->addDay();
                if ($currentDate->month !== $month || $currentDate->year !== $year) {
                    break;
                }
                continue;
            }

            $weekStart = $currentDate->copy();

            // Find the Saturday of this week
            $weekEnd = $weekStart->copy();
            while ($weekEnd->dayOfWeek !== Carbon::SATURDAY) {
                $weekEnd->addDay();
            }

            // Only include weeks that START in this month
            if ($weekStart->month === $month && $weekStart->year === $year) {
                $weeks[] = [
                    'week_number' => $weekNumber,
                    'start' => $weekStart->copy(),
                    'end' => $weekEnd->copy(),
                ];
                $weekNumber++;
            }

            // Move to next Monday (Saturday + 2 days)
            $currentDate = $weekEnd->copy()->addDays(2);

            // Stop if we've moved past the target month
            if ($currentDate->month !== $month || $currentDate->year !== $year) {
                break;
            }
        }

        return $weeks;
    }

    /**
     * Calculate range dates for a specific week in a month.
     *
     * @param  int  $year
     * @param  int  $month
     * @param  int  $weekNumber  1-N (depends on month)
     * @return array ['start' => Carbon, 'end' => Carbon, 'working_days' => int]
     *
     * @throws \InvalidArgumentException
     */
    public function getWeekDateRange(int $year, int $month, int $weekNumber): array
    {
        $weeks = static::getWeeksInMonth($year, $month);

        $weekIndex = $weekNumber - 1;

        if ($weekIndex < 0 || $weekIndex >= count($weeks)) {
            throw new \InvalidArgumentException(
                "Minggu {$weekNumber} tidak valid untuk bulan {$month}/{$year}. " .
                "Terdapat " . count($weeks) . " minggu."
            );
        }

        $week = $weeks[$weekIndex];

        $workingDays = $this->countWorkingDays($week['start'], $week['end']);

        return [
            'start' => $week['start'],
            'end' => $week['end'],
            'working_days' => $workingDays,
        ];
    }

    /**
     * Count working days (Mon-Sat) between two dates inclusive.
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @return int
     */
    private function countWorkingDays(Carbon $startDate, Carbon $endDate): int
    {
        $count = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            if ($current->dayOfWeek !== Carbon::SUNDAY) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Validate additional expenses JSON from frontend.
     *
     * Ensures the JSON is valid and recalculates total server-side
     * for security (prevents tampered totals from frontend).
     *
     * @param  int|null  $frontendTotal
     * @param  string|null  $notesJson
     * @return array  ['total' => int, 'notes' => string]
     */
    public function validateAdditionalExpenses(?int $frontendTotal, ?string $notesJson): array
    {
        if (!$notesJson) {
            return ['total' => 0, 'notes' => '[]'];
        }

        $expenseItems = json_decode($notesJson, true);

        if (!is_array($expenseItems)) {
            return ['total' => 0, 'notes' => '[]'];
        }

        $calculatedTotal = array_sum(array_column($expenseItems, 'amount'));

        if ($calculatedTotal != $frontendTotal) {
            return ['total' => $calculatedTotal, 'notes' => $notesJson];
        }

        return ['total' => $frontendTotal, 'notes' => $notesJson];
    }
}
