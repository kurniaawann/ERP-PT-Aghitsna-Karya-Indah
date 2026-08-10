<?php

namespace App\Services\Sdm;

use App\Models\Sdm\Payroll;
use App\Models\Sdm\Employee;
use App\Models\Sdm\Attendance;
use App\Models\Sdm\KasbonPayment;

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
 * Lembur hari Minggu (akhir periode) ikut dihitung dalam payroll,
 * namun Minggu tidak wajib diisi dalam validasi kelengkapan absensi.
 */
class PayrollService
{
    /**
     * Mendapatkan daftar payroll dengan paginasi dan relasi karyawan.
     *
     * Mendukung filter berdasarkan pencarian (nama/kode), bulan, tahun.
     * Hasil diurutkan berdasarkan periode terbaru terlebih dahulu.
     *
     * Logika:
     * - Pencarian hanya via whereHas relasi employee (nama/kode).
     * - Bulan/tahun/minggu difilter pada period_start_date — periode adalah
     *   kunci utama identifikasi payroll mingguan.
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
        ?int $weekNumber = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return Payroll::with('employee')
            ->where('created_by', auth()->id())
            ->when($search, function ($query, $search) {
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->when($month, fn($query) => $query->whereMonth('period_start_date', $month))
            ->when($year, fn($query) => $query->whereYear('period_start_date', $year))
            ->when($weekNumber, fn($query) => $query->where('week_number', $weekNumber))
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
     *
     * Catatan: Kasbon (tim maupun perorangan) TIDAK memblokir generate payroll —
     * kasbon diperbolehkan melebihi gaji. Potongan kasbon hanya diterapkan saat
     * generatePayroll berjalan.
     *
     * Mengembalikan laporan status komprehensif yang digunakan oleh frontend
     * untuk menentukan apakah pembuatan payroll diperbolehkan.
     *
     * Optimasi query: Mengambil semua data payroll dan absensi yang sudah ada
     * secara batch di awal alih-alih per karyawan (perbaikan N+1).
     *
     * Logika alur per karyawan:
     * 1. Lewati jika sudah ada payroll untuk periode ini (already_generated).
     * 2. Lewati jika join_date lebih besar dari akhir periode (belum bekerja).
     * 3. Susun daftar hari kerja yang diwajibkan: dari max(join_date, start)
     *    sampai end, mengecualikan Minggu.
     * 4. Bandingkan dengan tanggal absensi milik karyawan (dari batch query);
     *    selisihnya = hari hilang. Lengkap → complete, ada kurang → incomplete.
     * 5. can_generate = ada karyawan baru DAN tidak ada yang incomplete.
     * 6. Rentang absensi diambil sampai endDate+1 (termasuk Minggu) karena
     *    lembur Minggu ikut dihitung, meski Minggu bukan hari kerja wajib.
     *
     * @param  Carbon  $periodStartDate
     * @param  Carbon  $periodEndDate
     * @return array
     */
    public function validateAttendanceCompleteness(Carbon $periodStartDate, Carbon $periodEndDate): array
    {
        $employees = Employee::where('created_by', auth()->id())->get();

        $startDate = $periodStartDate->copy();
        $endDate = $periodEndDate->copy();

        // working_days = hari kalender Senin-Sabtu (Minggu dikecualikan oleh loop)
        $workingDays = $this->countWorkingDays($startDate, $endDate);

        // === QUERY BATCH (perbaikan N+1) ===
        $existingPayrollEmployeeIds = Payroll::where('period_start_date', $startDate->format('Y-m-d'))
            ->where('created_by', auth()->id())
            ->pluck('employee_id')
            ->toArray();

        // Rentang absensi meliputi Minggu (endDate + 1) karena lembur hari Minggu
        // diperbolehkan diinput dan harus ikut dihitung dalam payroll.
        // Minggu tidak termasuk hari kerja wajib (tidak divalidasi kelengkapan).
        $allAttendances = Attendance::whereIn('employee_id', $employees->pluck('employee_code'))
            ->whereBetween('attendance_date', [$startDate, $endDate->copy()->addDay()])
            ->get()
            ->groupBy('employee_id');
        // === AKHIR QUERY BATCH ===

        $incompleteEmployees = [];
        $completeEmployees = [];
        $alreadyGenerated = [];
        $newEmployees = [];
        $employeesWithoutProject = [];

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

            if (empty($employee->project_name)) {
                $employeesWithoutProject[] = [
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'join_date' => $employeeJoinDate->format('Y-m-d'),
                ];
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
            $filledDays = count(array_intersect($employeeWorkingDates, $attendanceDates));

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

        $canGenerate = count($newEmployees) > 0
            && count($incompleteEmployees) === 0
            && count($employeesWithoutProject) === 0;

        return [
            'working_days' => $workingDays,
            'period_start' => $startDate->format('d/m/Y'),
            'period_end' => $endDate->format('d/m/Y'),
            'period_start_day' => $startDate->format('l, d M Y'),
            'period_end_day' => $endDate->format('l, d M Y'),
            'incomplete_employees' => $incompleteEmployees,
            'complete_employees' => $completeEmployees,
            'already_generated' => $alreadyGenerated,
            'employees_without_project' => $employeesWithoutProject,
            'has_new_employees' => $hasNewEmployees,
            'new_employees' => $newEmployees,
            'total_employees' => count($employees),
            'kasbon_issues' => [],
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
     * 5. Tandai kasbon personal dan team sebagai sudah dipotong
     *
     * Logika potongan kasbon:
     * - Hanya KasbonPayment manual yang BELUM di-assign ke payroll
     *   (payroll_id IS NULL) yang dipotong — mencegah potongan ganda.
     * - Kasbon personal dipotong penuh dari gaji karyawan terkait.
     * - Kasbon divisi (team) TIDAK dibagi rata ke karyawan — hanya ditandai
     *   sudah diproses (payroll_id di-assign) dan dimunculkan sebagai rekap
     *   pada cetakan payroll (REKAPITULASI DANA), tidak memotong upah per orang.
     * - Rumus: net_salary = (daily_wage × present_days) + overtime_total
     *   - kasbon_deduction (personal saja).
     * - week_number dideteksi dari getWeeksInMonth() dengan mencocokkan
     *   tanggal mulai periode.
     *
     * @param  Carbon        $periodStartDate
     * @param  Carbon        $periodEndDate
     * @return array  ['success' => bool, 'message' => string]
     */
    public function generatePayroll(
        Carbon $periodStartDate,
        Carbon $periodEndDate
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
        $employees = Employee::where('created_by', auth()->id())->get();
        $incompleteEmployees = [];
        $employeesWithoutProject = [];

        $existingPayrollEmployeeIds = Payroll::where('period_start_date', $startDate->format('Y-m-d'))
            ->where('created_by', auth()->id())
            ->pluck('employee_id')
            ->toArray();

        // Rentang absensi meliputi Minggu (endDate + 1) karena lembur hari Minggu
        // diperbolehkan diinput dan harus ikut dihitung dalam payroll.
        $allAttendances = Attendance::whereIn('employee_id', $employees->pluck('employee_code'))
            ->whereBetween('attendance_date', [$startDate, $endDate->copy()->addDay()])
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

            if (empty($employee->project_name)) {
                $employeesWithoutProject[] = [
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                ];
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
            $filledDays = count(array_intersect($employeeWorkingDates, $attendanceDates));

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

        if (count($employeesWithoutProject) > 0) {
            $names = implode(', ', array_map(fn($emp) => '<strong>' . $emp['name'] . '</strong> (' . $emp['employee_code'] . ')', $employeesWithoutProject));

            $errorMessage = '<strong>Tidak dapat generate payroll!</strong><br>Karyawan berikut belum memiliki proyek:<br><br>';
            $errorMessage .= '❌ ' . $names;
            $errorMessage .= '<br><br><strong>Catatan:</strong> Setiap karyawan harus memiliki proyek sebelum payroll dapat dibuat.<br>Silakan lengkapi data proyek di menu <strong>Human Resource → Data Karyawan</strong> terlebih dahulu.';

            return ['success' => false, 'message' => $errorMessage];
        }

        // === HITUNG POTONGAN KASBON YANG SUDAH DIBAYAR TAPI BELUM DIPOTONG ===
        // Pendekatan: cari KasbonPayment manual (bayar tunai) yang belum di-assign ke payroll
        $employeeCodes = $employees->pluck('employee_code')->toArray();

        // Personal: cari kasbon payment per karyawan
        $pendingPersonalPayments = KasbonPayment::whereNull('payroll_id')
            ->where('payment_method', 'manual')
            ->whereHas('kasbon', function ($q) use ($employeeCodes) {
                $q->whereIn('employee_id', $employeeCodes)
                    ->where('kasbon_type', 'personal');
            })
            ->with('kasbon')
            ->get()
            ->groupBy('kasbon.employee_id');

        $personalKasbonPerEmployee = [];
        foreach ($pendingPersonalPayments as $empCode => $payments) {
            $personalKasbonPerEmployee[$empCode] = $payments->sum('amount');
        }

        // Team: cari kasbon payment per divisi
        $pendingTeamPayments = KasbonPayment::whereNull('payroll_id')
            ->where('payment_method', 'manual')
            ->whereHas('kasbon', function ($q) {
                $q->where('kasbon_type', 'team');
            })
            ->with('kasbon')
            ->get()
            ->groupBy('kasbon.division');

        // === BUAT PAYROLL PER KARYAWAN ===
        $payrolls = [];

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

            $personalKasbon = $personalKasbonPerEmployee[$employee->employee_code] ?? 0;

            // Kasbon divisi (team) TIDAK dibagi rata ke setiap karyawan divisi.
            // Kasbon divisi hanya dimunculkan sebagai rekap pada cetakan payroll
            // (section REKAPITULASI DANA), bukan memotong upah per orang.
            $totalKasbonDeduction = $personalKasbon;
            $netWage = $grossWage - $totalKasbonDeduction;

            $payroll = Payroll::create([
                'employee_id' => $employee->employee_code,
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'period_type' => 'weekly',
                'week_number' => $weekNumber,
                'period_start_date' => $startDate->format('Y-m-d'),
                'period_end_date' => $endDate->format('Y-m-d'),
                'project_name' => $employee->project_name,
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
                'net_salary' => $netWage,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            $payrolls[] = $payroll;

            // Assign KasbonPayment personal ke payroll ini
            $empPayments = $pendingPersonalPayments->get($employee->employee_code, new Collection());
            foreach ($empPayments as $payment) {
                $payment->payroll_id = $payroll->id;
                $payment->save();
            }
        }

        // Assign KasbonPayment team ke payroll pertama per divisi
        if (!empty($payrolls)) {
            $payrollCollection = collect($payrolls);
            $processedDivisions = [];

            foreach ($employees as $employee) {
                if ($employee->division && !in_array($employee->division, $processedDivisions)) {
                    $divPayments = $pendingTeamPayments->get($employee->division, new Collection());
                    $firstPayroll = $payrollCollection->first();

                    foreach ($divPayments as $payment) {
                        $payment->payroll_id = $firstPayroll?->id;
                        $payment->save();
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
     *
     * Logika:
     * - UPDATE massal dijalankan hanya untuk id terpilih yang masih berstatus
     *   'draft' → payroll 'paid' tidak mungkin dibayar dua kali.
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
            ->where('created_by', auth()->id())
            ->update([
                'payment_date' => $paymentDate,
                'status' => 'paid',
            ]);

        if ($updated > 0) {
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
     * Logika:
     * - Dihapus per record (loop $payroll->delete()) karena Payroll punya
     *   relasi/observer yang perlu dipicu per model.
     *
     * @param  array  $ids  Array ID payroll
     * @return array  ['success' => bool, 'message' => string]
     */
    public function deleteDraftPayrolls(array $ids): array
    {
        if (empty($ids)) {
            return ['success' => false, 'message' => 'Tidak ada data yang dipilih!'];
        }

        $payrolls = Payroll::whereIn('id', $ids)->where('status', 'draft')->where('created_by', auth()->id())->get();

        foreach ($payrolls as $payroll) {
            $payroll->delete();
        }

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
    public function getPayrollsForExport(?int $month, ?int $year, ?int $weekNumber = null): ?Collection
    {
        $payrolls = Payroll::with('employee')
            ->where('created_by', auth()->id())
            ->when($month, fn($query) => $query->whereMonth('period_start_date', $month))
            ->when($year, fn($query) => $query->whereYear('period_start_date', $year))
            ->when($weekNumber, fn($query) => $query->where('week_number', $weekNumber))
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
            ->where('created_by', auth()->id())
            ->where('period_start_date', $periodStartDate->format('Y-m-d'))
            ->where('period_end_date', $periodEndDate->format('Y-m-d'))
            ->get();

        return $payrolls->isEmpty() ? null : $payrolls;
    }

    /**
     * Mendapatkan rekap kasbon divisi (team) untuk payroll yang diexport.
     *
     * Kasbon divisi tidak dibagi rata ke karyawan, melainkan hanya
     * dimunculkan sebagai informasi pada section REKAPITULASI DANA saat
     * mencetak payroll (PDF/Excel). Data diambil dari KasbonPayment yang
     * sudah di-assign (payroll_id) ke payroll terpilih, dikelompokkan
     * per divisi.
     *
     * @param  Collection  $payrolls  Koleksi payroll yang diexport
     * @return Collection  Collection mapping nama divisi -> total kasbon
     */
    public function getTeamKasbonRecap(Collection $payrolls): Collection
    {
        $payrollIds = $payrolls->pluck('id');

        if ($payrollIds->isEmpty()) {
            return collect();
        }

        return KasbonPayment::whereIn('payroll_id', $payrollIds)
            ->whereHas('kasbon', fn ($q) => $q->where('kasbon_type', 'team'))
            ->with('kasbon')
            ->get()
            ->groupBy('kasbon.division')
            ->map(fn ($payments) => (int) $payments->sum('amount'))
            ->filter(fn ($total) => $total > 0);
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

        // Minggu kerja selalu dimulai hari Senin. Jika tanggal 1 jatuh di tengah
        // minggu (bukan Senin), hari-hari awal bulan tersebut sudah termasuk
        // minggu terakhir bulan sebelumnya, jadi tidak dibuat minggu parsial di sini.
        $currentDate = $firstDayOfMonth->copy();
        while ($currentDate->dayOfWeek !== Carbon::MONDAY) {
            $currentDate->addDay();
        }

        while ($currentDate->month === $month && $currentDate->year === $year) {
            $weekStart = $currentDate->copy();

            // Cari hari Sabtu dari minggu ini
            $weekEnd = $weekStart->copy();
            while ($weekEnd->dayOfWeek !== Carbon::SATURDAY) {
                $weekEnd->addDay();
            }

            $weeks[] = [
                'week_number' => $weekNumber,
                'start' => $weekStart->copy(),
                'end' => $weekEnd->copy(),
            ];
            $weekNumber++;

            // Pindah ke Senin minggu berikutnya (Sabtu + 2 hari)
            $currentDate = $weekEnd->copy()->addDays(2);
        }

        return $weeks;
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
}
