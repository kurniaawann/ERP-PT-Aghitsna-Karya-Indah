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
 * Service untuk mengelola bisnis logika payroll.
 *
 * Menangani daftar payroll, validasi absensi, pembuatan,
 * pembayaran massal, penghapusan, dan persiapan data ekspor.
 * Semua logika perhitungan gaji dipusatkan di sini.
 *
 * Identifikasi periode menggunakan period_start_date sebagai kunci utama.
 * Minggu berjalan dari Senin-Sabtu dan TIDAK dipotong di batas bulan.
 */
class PayrollService
{
    /**
     * Mendapatkan daftar payroll dengan paginasi dan relasi karyawan.
     *
     * Mendukung filter berdasarkan pencarian (nama/kode), bulan, tahun.
     * Hasil diurutkan berdasarkan periode terbaru terlebih dahulu.
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
     * Memvalidasi kelengkapan absensi untuk semua karyawan dalam periode minggu tertentu.
     *
     * Pemeriksaan:
     * 1. Karyawan mana yang sudah memiliki payroll untuk periode ini
     * 2. Karyawan mana yang memiliki absensi tidak lengkap (hari yang kurang)
     * 3. Karyawan mana yang memiliki kasbon personal melebihi gaji maksimal
     * 4. Divisi mana yang memiliki kasbon team melebihi total gaji divisi
     *
     * Mengembalikan laporan status komprehensif yang digunakan oleh frontend
     * untuk menentukan apakah pembuatan payroll diperbolehkan.
     *
     * Optimasi query: Mengambil semua data payroll dan absensi yang sudah ada
     * secara batch di awal alih-alih per karyawan (perbaikan N+1).
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

        // working_days = hari kalender Senin-Sabtu (Minggu dikecualikan oleh loop)
        $workingDays = $this->countWorkingDays($startDate, $endDate);

        // === QUERY BATCH (perbaikan N+1) ===
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
        // === AKHIR QUERY BATCH ===

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

        // === VALIDASI KASBON ===
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
     * Membuat payroll mingguan untuk pekerja harian.
     *
     * Proses perhitungan:
     * 1. Validasi kelengkapan absensi (tolak jika tidak lengkap)
     * 2. Hitung kasbon team per divisi
     * 3. Untuk setiap karyawan: gaji harian × hari hadir + lembur - kasbon
     * 4. Buat data payroll dengan status 'draft'
     * 5. Buat data SalaryReminder
     * 6. Tandai kasbon personal dan team sebagai sudah dipotong
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

        // Mendeteksi week_number dari getWeeksInMonth
        $weeks = static::getWeeksInMonth($periodYear, $periodMonth);
        $weekNumber = 1;
        foreach ($weeks as $index => $week) {
            if ($week['start']->format('Y-m-d') === $startDate->format('Y-m-d')) {
                $weekNumber = $week['week_number'];
                break;
            }
        }

        // === VALIDASI ABSENSI ===
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

        // === HITUNG KASBON TEAM PER DIVISI ===
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

        // === BUAT PAYROLL PER KARYAWAN ===
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

        // Tandai kasbon personal sebagai sudah dipotong (update batch)
        if (!empty($personalKasbonIdsToMark)) {
            Kasbon::whereIn('id', $personalKasbonIdsToMark)->update([
                'status' => 'deducted',
            ]);
        }

        // Tandai kasbon team sebagai sudah dipotong
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
     * Membayar beberapa data payroll secara massal.
     *
     * Memperbarui status dari 'draft' menjadi 'paid' dan mengatur tanggal pembayaran.
     * Juga menyinkronkan status SalaryReminder untuk payroll yang sudah dibayar.
     *
     * @param  array   $ids     Array ID payroll
     * @param  string  $paymentDate  Tanggal pembayaran (Y-m-d)
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
     * Menghapus data payroll draft secara massal.
     *
     * Hanya payroll dengan status 'draft' yang bisa dihapus.
     * Payroll yang sudah dibayar dilindungi dari penghapusan.
     *
     * @param  array  $ids  Array ID payroll
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
     * Mendapatkan koleksi payroll untuk ekspor Excel/PDF.
     *
     * Mendukung filter berdasarkan bulan dan tahun.
     * Termasuk relasi karyawan untuk menghindari N+1 saat ekspor.
     *
     * @param  int|null  $month
     * @param  int|null  $year
     * @return Collection|null  Null jika tidak ada data ditemukan
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
     * Mendapatkan payroll untuk ekspor yang difilter berdasarkan rentang tanggal tertentu.
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
     * Mendapatkan semua minggu dalam sebulan menggunakan sistem minggu Senin-Sabtu.
     *
     * Setiap minggu berjalan dari Senin hingga Sabtu (6 hari kerja).
     * Minggu TIDAK dipotong di batas bulan — minggu yang dimulai di
     * Februari dan berakhir di Maret diperlakukan sebagai satu periode.
     *
     * Jika bulan tidak dimulai pada hari Senin, hari-hari sebelum Senin
     * pertama membentuk "Minggu 1" parsial (contoh: Rabu-Sabtu = 4 hari).
     *
     * Contoh untuk Februari 2028 (1 Februari = hari Rabu):
     *   Minggu 1: Feb 1-4   (Rabu-Sabtu) = 4 hari
     *   Minggu 2: Feb 6-11  (Senin-Sabtu) = 6 hari
     *   Minggu 3: Feb 13-18 (Senin-Sabtu) = 6 hari
     *   Minggu 4: Feb 20-25 (Senin-Sabtu) = 6 hari
     *   Minggu 5: Feb 27 - Mar 4 (Senin-Sabtu) = 6 hari (lintas bulan)
     *
     * Minggu selalu dikecualikan sebagai hari non-kerja.
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
            // Lewati hari Minggu — tidak pernah menjadi awal minggu
            if ($currentDate->dayOfWeek === Carbon::SUNDAY) {
                $currentDate->addDay();
                if ($currentDate->month !== $month || $currentDate->year !== $year) {
                    break;
                }
                continue;
            }

            $weekStart = $currentDate->copy();

            // Cari hari Sabtu dari minggu ini
            $weekEnd = $weekStart->copy();
            while ($weekEnd->dayOfWeek !== Carbon::SATURDAY) {
                $weekEnd->addDay();
            }

            // Hanya sertakan minggu yang DIMULAI di bulan ini
            if ($weekStart->month === $month && $weekStart->year === $year) {
                $weeks[] = [
                    'week_number' => $weekNumber,
                    'start' => $weekStart->copy(),
                    'end' => $weekEnd->copy(),
                ];
                $weekNumber++;
            }

            // Pindah ke Senin berikutnya (Sabtu + 2 hari)
            $currentDate = $weekEnd->copy()->addDays(2);

            // Berhenti jika sudah melampaui bulan yang dituju
            if ($currentDate->month !== $month || $currentDate->year !== $year) {
                break;
            }
        }

        return $weeks;
    }

    /**
     * Menghitung rentang tanggal untuk minggu tertentu dalam sebulan.
     *
     * @param  int  $year
     * @param  int  $month
     * @param  int  $weekNumber  1-N (tergantung bulan)
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
     * Menghitung hari kerja (Senin-Sabtu) antara dua tanggal secara inklusif.
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
     * Memvalidasi JSON pengeluaran tambahan dari frontend.
     *
     * Memastikan JSON valid dan menghitung ulang total di sisi server
     * demi keamanan (mencegah total yang dimanipulasi dari frontend).
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
