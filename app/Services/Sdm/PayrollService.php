<?php

namespace App\Services\Sdm;

use App\Models\Finance\ProjectRecap;
use App\Models\Sdm\Payroll;
use App\Models\Sdm\Employee;
use App\Models\Sdm\Attendance;
use App\Models\Sdm\KasbonPayment;
use App\Models\Sdm\Executive;
use App\Services\Report\ProjectFinancialReportService;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk mengelola bisnis logika payroll.
 *
 * Menangani daftar payroll, validasi absensi, pembuatan,
 * pembayaran massal, penghapusan, dan persiapan data ekspor.
 * Semua logika perhitungan gaji dipusatkan di sini.
 *
 * Identifikasi periode menggunakan period_start_date sebagai kunci utama.
 * Minggu berjalan dari Senin-Sabtu dan TIDAK dipotong di batas bulan.
 * Hari Minggu adalah hari libur: tidak ada absensi maupun lembur di hari
 * Minggu, sehingga Minggu dikecualikan dari semua perhitungan payroll.
 */
class PayrollService
{
    /**
     * Service untuk membuat entri "Upah Pekerja" pada Laporan Keuangan Proyek
     * ketika payroll dibayar (draft → paid).
     */
    public function __construct(
        private readonly ProjectFinancialReportService $financialReportService,
    ) {
    }

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
     * @param  int|null     $weekNumber
     * @param  string|null  $projectName
     * @param  int          $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedPayrolls(
        ?string $search,
        ?int $month,
        ?int $year,
        ?int $weekNumber = null,
        ?string $projectName = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->buildPayrollIndexQuery($search, $month, $year, $weekNumber, $projectName)
            ->paginate($perPage);
    }

    /**
     * Mendapatkan SEMUA payroll yang cocok dengan filter index (tanpa paginasi).
     *
     * Dipakai sebagai bahan pengelompokan per proyek + periode sebelum
     * paginasi diterapkan pada level GRUP (bukan per baris payroll), sehingga
     * satu grup tidak terpotong antar halaman.
     *
     * @param  string|null  $search
     * @param  int|null     $month
     * @param  int|null     $year
     * @param  int|null     $weekNumber
     * @param  string|null  $projectName
     * @return Collection
     */
    public function getPayrollsForIndex(
        ?string $search,
        ?int $month,
        ?int $year,
        ?int $weekNumber = null,
        ?string $projectName = null
    ): Collection {
        return $this->buildPayrollIndexQuery($search, $month, $year, $weekNumber, $projectName)
            ->get();
    }

    /**
     * Query dasar daftar payroll index (relasi karyawan + filter + urutan).
     *
     * Dipakai bersama oleh getPaginatedPayrolls (paginasi per baris) dan
     * getPayrollsForIndex (semua baris untuk pengelompokan per proyek).
     *
     * @param  string|null  $search
     * @param  int|null     $month
     * @param  int|null     $year
     * @param  int|null     $weekNumber
     * @param  string|null  $projectName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildPayrollIndexQuery(
        ?string $search,
        ?int $month,
        ?int $year,
        ?int $weekNumber,
        ?string $projectName
    ): \Illuminate\Database\Eloquent\Builder {
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
            ->when($projectName, fn($query) => $query->where('project_name', $projectName))
            ->latest('period_start_date')
            ->latest('created_at');
    }

    /**
     * Menerapkan paginasi pada level GRUP (proyek + periode).
     *
     * Satu halaman memuat grup-grup UTUH — baris karyawan di dalamnya tidak
     * pernah terpotong antar halaman. Opsi paginasi (URL saat ini + query
     * filter aktif) dijaga agar link pagination mempertahankan filter.
     *
     * @param  Collection  $groups   Koleksi grup hasil groupPayrollsForView
     * @param  int         $perPage  Jumlah grup per halaman
     * @return LengthAwarePaginator
     */
    public function paginatePayrollGroups(Collection $groups, int $perPage = 10): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $groups->forPage($page, $perPage)->values();

        return new LengthAwarePaginator($items, $groups->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'query' => request()->query(),
        ]);
    }

    /**
     * Mengelompokkan koleksi payroll per proyek + periode untuk tampilan index.
     *
     * Kelompok dibentuk dari kombinasi project_name dan period_start_date
     * (satu lembar payroll = satu proyek + satu minggu). Setiap kelompok
     * menyertakan ringkasan agregat (jumlah karyawan, subtotal upah bersih,
     * jumlah draft/paid) agar header grup bisa menampilkan total tanpa query
     * tambahan.
     *
     * @param  Collection  $payrolls  Koleksi payroll yang akan dikelompokkan
     * @return Collection<int, array<string, mixed>>
     */
    public function groupPayrollsForView(Collection $payrolls): Collection
    {
        return $payrolls->groupBy(function (Payroll $payroll) {
            $project = $payroll->project_name ?: 'Tanpa Proyek';
            $period = $payroll->period_start_date
                ? Carbon::parse($payroll->period_start_date)->format('Y-m-d')
                : 'legacy';

            return $project.'|'.$period;
        })->map(function (Collection $group) {
            $first = $group->first();
            $draftCount = $group->where('status', 'draft')->count();
            $paidCount = $group->where('status', 'paid')->count();

            return [
                'project_name' => $first->project_name ?: 'Tanpa Proyek',
                'period_start_date' => $first->period_start_date,
                'period_end_date' => $first->period_end_date,
                'week_number' => $first->week_number,
                'period_month' => $first->period_month,
                'period_year' => $first->period_year,
                'payrolls' => $group->values(),
                'count' => $group->count(),
                'draft_count' => $draftCount,
                'paid_count' => $paidCount,
                'total_net' => (int) $group->sum('net_salary'),
                'total_base' => (int) $group->sum('base_salary'),
                'total_overtime' => (int) $group->sum('overtime_total'),
                'total_kasbon' => (int) $group->sum('kasbon_deduction'),
            ];
        })->values();
    }

    /**
     * Mendapatkan daftar proyek unik dari data karyawan milik user saat ini.
     *
     * Dipakai sebagai opsi multi-select proyek pada modal Generate Payroll.
     * Hanya proyek yang benar-benar dimiliki karyawan yang dimunculkan agar
     * opsi yang dipilih selalu bisa diproses (guard "tanpa proyek" tidak
     * menghalangi generate).
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function getProjectOptions(): Collection
    {
        return Employee::where('created_by', auth()->id())
            ->whereNotNull('project_name')
            ->where('project_name', '!=', '')
            ->distinct()
            ->orderBy('project_name')
            ->pluck('project_name');
    }

    /**
     * Membangun snapshot petinggi dari ID terpilih untuk blok tanda tangan.
     *
     * Mengambil petinggi milik user saat ini berdasarkan ID per peran lalu
     * mengubahnya menjadi array snapshot berisi id, name, position, dan
     * signature_image. Dipanggil saat generatePayroll agar pilihan
     * penandatangan tersimpan pada setiap record payroll.
     *
     * Peran yang tidak terpilih (ID kosong) bernilai null — kolom tanda
     * tangan pada PDF akan menampilkan garis putus-putus sebagai fallback.
     *
     * @param  array<string, mixed>  $signatureIds  Mapping peran => ID petinggi
     * @return array<string, array<string, mixed>|null>
     */
    public function resolveSignatureSnapshot(array $signatureIds): array
    {
        $roles = ['disetujui', 'diperiksa', 'dibuat'];

        $ids = array_values(array_filter(array_map(
            fn ($value) => (int) $value,
            array_intersect_key($signatureIds, array_flip($roles))
        )));

        $executives = Executive::where('created_by', auth()->id())
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $snapshot = [];
        foreach ($roles as $role) {
            $id = isset($signatureIds[$role]) ? (int) $signatureIds[$role] : null;
            $executive = $id ? $executives->get($id) : null;

            $snapshot[$role] = $executive ? [
                'id' => $executive->id,
                'name' => $executive->name,
                'position' => $executive->position,
                'signature_image' => $executive->signature_image,
            ] : null;
        }

        return $snapshot;
    }

    /**
     * Mendapatkan snapshot petinggi untuk cetakan PDF sebuah payroll.
     *
     * Mengutamakan snapshot yang tersimpan saat payroll di-generate agar
     * dokumen konsisten dengan pilihan penandatangan saat itu. Bila payroll
     * belum memiliki snapshot (data lama), ketiga kolom tanda tangan
     * (Disetujui/Diperiksa/Dibuat) bernilai null sehingga PDF menampilkan
     * garis putus-putus sebagai fallback.
     *
     * @param  Payroll|null  $payroll
     * @return array<string, array<string, mixed>|null>
     */
    public function getPayrollSignatures(?Payroll $payroll): array
    {
        if ($payroll && is_array($payroll->signatures)) {
            return $payroll->signatures;
        }

        return [
            'disetujui' => null,
            'diperiksa' => null,
            'dibuat' => null,
        ];
    }

    /**
     * Memvalidasi kelengkapan absensi untuk karyawan (per proyek, jika dipilih)
     * dalam periode minggu tertentu.
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
 *    sampai end, mengecualikan Minggu (hari libur).
 * 4. Bandingkan dengan tanggal absensi milik karyawan (dari batch query);
 *    selisihnya = hari hilang. Lengkap → complete, ada kurang → incomplete.
 * 5. can_generate = ada karyawan baru DAN tidak ada yang incomplete.
 * 6. Rentang absensi dibatasi startDate sampai endDate (Senin-Sabtu) —
 *    Minggu tidak dihitung karena tidak ada absensi di hari libur.
 *
 * @param  Carbon        $periodStartDate
 * @param  Carbon        $periodEndDate
 * @param  array|null    $projectNames  Filter hanya karyawan pada proyek-proyek tertentu (opsional)
 * @return array
 */
    public function validateAttendanceCompleteness(Carbon $periodStartDate, Carbon $periodEndDate, ?array $projectNames = null): array
    {
        $employees = Employee::where('created_by', auth()->id())
            ->when($projectNames, fn ($query) => $query->whereIn('project_name', $projectNames))
            ->get();

        $startDate = $periodStartDate->copy();
        $endDate = $periodEndDate->copy();

        // working_days = hari kalender Senin-Sabtu (Minggu dikecualikan oleh loop)
        $workingDays = $this->countWorkingDays($startDate, $endDate);

        // === QUERY BATCH (perbaikan N+1) ===
        $existingPayrollEmployeeIds = Payroll::where('period_start_date', $startDate->format('Y-m-d'))
            ->where('created_by', auth()->id())
            ->pluck('employee_id')
            ->toArray();

        // Rentang absensi = startDate sampai endDate (Senin-Sabtu).
        // Minggu adalah hari libur, jadi tidak ada absensi maupun lembur
        // yang perlu diambil untuk hari Minggu.
        $allAttendances = Attendance::whereIn('employee_id', $employees->pluck('employee_code'))
            ->whereBetween('attendance_date', [$startDate, $endDate])
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
            'project_names' => $projectNames,
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
     * Membuat payroll mingguan untuk pekerja harian (per proyek, jika dipilih).
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
     * @param  array|null    $projectNames   Filter hanya karyawan pada proyek-proyek tertentu (opsional)
     * @param  array|null    $signatories    Penanda tangan per proyek:
     *                                       [Nama Proyek => [disetujui|diperiksa|dibuat => ID petinggi]]
     * @return array  ['success' => bool, 'message' => string]
     */
    public function generatePayroll(
        Carbon $periodStartDate,
        Carbon $periodEndDate,
        ?array $projectNames = null,
        ?array $signatories = null
    ): array {
        $startDate = $periodStartDate->copy();
        $endDate = $periodEndDate->copy();

        $workingDays = $this->countWorkingDays($startDate, $endDate);

        $periodMonth = $startDate->month;
        $periodYear = $startDate->year;

        // Snapshot penanda tangan disimpan per payroll sesuai proyek karyawan
        // (setiap proyek bisa memiliki penanda tangan berbeda).
        $signatories = $signatories ?? [];

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
        $employees = Employee::where('created_by', auth()->id())
            ->when($projectNames, fn ($query) => $query->whereIn('project_name', $projectNames))
            ->get();
        $incompleteEmployees = [];
        $employeesWithoutProject = [];

        $existingPayrollEmployeeIds = Payroll::where('period_start_date', $startDate->format('Y-m-d'))
            ->where('created_by', auth()->id())
            ->pluck('employee_id')
            ->toArray();

        // Rentang absensi = startDate sampai endDate (Senin-Sabtu).
        // Minggu adalah hari libur, jadi tidak ada absensi maupun lembur
        // yang perlu diambil untuk hari Minggu.
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

            // Snapshot penanda tangan proyek karyawan ini (setiap proyek bisa
            // memiliki penanda tangan yang berbeda pada batch yang sama).
            $signaturesSnapshot = $this->resolveSignatureSnapshot(
                $signatories[$employee->project_name] ?? []
            );

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
                'signatures' => $signaturesSnapshot,
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
     * Menghitung ulang snapshot payroll draft untuk seorang karyawan pada
     * periode tertentu (dikenali lewat period_start_date).
     *
     * Hanya payroll berstatus 'draft' yang dihitung ulang — payroll 'paid'
     * dibekukan agar nominal yang sudah dibayar tidak berubah secara retrospektif.
     *
     * Menghitung ulang:
     * - present_days / permission_days / sick_days / leave_days / overtime_days
     *   dari tabel absensi dalam rentang periode payroll.
     * - overtime_total dari total lembur dalam rentang periode.
     * - kasbon_deduction dari KasbonPayment personal yang ter-assign ke payroll
     *   ini, setelah merekonsiliasi assignment (payment baru di-assign, payment
     *   yang tidak lagi cocok di-unassign).
     * - net_salary = (base_salary × present_days) + overtime_total - kasbon_deduction
     *   (base_salary adalah snapshot upah harian saat generate, tidak diubah).
     *
     * @param  string         $employeeCode    Kode karyawan
     * @param  Carbon|string  $periodStartDate Tanggal mulai periode (Y-m-d)
     * @return bool  true jika payroll draft ditemukan dan berhasil dihitung ulang
     */
    public function recalculateForEmployeePeriod(string $employeeCode, Carbon|string $periodStartDate): bool
    {
        $periodStart = $periodStartDate instanceof Carbon
            ? $periodStartDate->format('Y-m-d')
            : Carbon::parse($periodStartDate)->format('Y-m-d');

        $payroll = Payroll::where('employee_id', $employeeCode)
            ->where('period_start_date', $periodStart)
            ->where('status', 'draft')
            ->where('created_by', auth()->id())
            ->first();

        if (!$payroll) {
            return false;
        }

        return $this->recalculatePayrollSnapshot($payroll);
    }

    /**
     * Menghitung ulang snapshot payroll draft untuk seorang karyawan yang
     * periodenya menimpa rentang tanggal yang berubah (misalnya absensi).
     *
     * Dipakai oleh hook auto-recalculasi pada perubahan absensi/lembur.
     * Rentang tanggal bisa memotong lebih dari satu periode mingguan,
     * sehingga semua payroll draft yang tumpang tindih ikut dihitung ulang.
     *
     * @param  string  $employeeCode  Kode karyawan
     * @param  Carbon  $startDate     Tanggal mulai rentang perubahan (inklusif)
     * @param  Carbon  $endDate       Tanggal akhir rentang perubahan (inklusif)
     * @return int  Jumlah payroll yang berhasil dihitung ulang
     */
    public function recalculateForAttendanceRange(string $employeeCode, Carbon $startDate, Carbon $endDate): int
    {
        $payrolls = Payroll::where('employee_id', $employeeCode)
            ->where('status', 'draft')
            ->where('created_by', auth()->id())
            ->whereDate('period_start_date', '<=', $endDate->format('Y-m-d'))
            ->whereDate('period_end_date', '>=', $startDate->format('Y-m-d'))
            ->get();

        $recalculated = 0;

        foreach ($payrolls as $payroll) {
            if ($this->recalculatePayrollSnapshot($payroll)) {
                $recalculated++;
            }
        }

        return $recalculated;
    }

    /**
     * Menghitung ulang satu snapshot payroll draft dari sumber datanya saat ini.
     *
     * Logika sama dengan generatePayroll: jumlah hari hadir/lembur/izin/sakit/cuti
     * dihitung dari absensi dalam rentang periode, overtime_total dari kolom
     * overtime_total pada absensi berstatus lembur, kasbon_deduction dari
     * KasbonPayment personal yang ter-assign, lalu net_salary diturunkan.
     *
     * @param  Payroll  $payroll  Payroll draft yang akan dihitung ulang
     * @return bool
     */
    private function recalculatePayrollSnapshot(Payroll $payroll): bool
    {
        $startDate = Carbon::parse($payroll->period_start_date);
        $endDate = Carbon::parse($payroll->period_end_date);

        $attendances = Attendance::where('employee_id', $payroll->employee_id)
            ->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $presentDays = $attendances->whereIn('status', ['hadir', 'lembur'])->count();
        $permissionDays = $attendances->where('status', 'izin')->count();
        $sickDays = $attendances->where('status', 'sakit')->count();
        $leaveDays = $attendances->where('status', 'cuti')->count();
        $overtimeDays = $attendances->where('status', 'lembur')->count();
        $overtimeTotal = (int) $attendances->where('status', 'lembur')->sum('overtime_total');

        $this->reconcileKasbonPayments($payroll);

        $kasbonDeduction = (int) KasbonPayment::where('payroll_id', $payroll->id)
            ->whereHas('kasbon', fn($q) => $q->where('kasbon_type', 'personal'))
            ->sum('amount');

        $totalWage = (int) $payroll->base_salary * $presentDays;
        $netWage = $totalWage + $overtimeTotal - $kasbonDeduction;

        return $payroll->update([
            'present_days' => $presentDays,
            'permission_days' => $permissionDays,
            'sick_days' => $sickDays,
            'leave_days' => $leaveDays,
            'overtime_days' => $overtimeDays,
            'overtime_total' => $overtimeTotal,
            'kasbon_deduction' => $kasbonDeduction,
            'net_salary' => $netWage,
        ]);
    }

    /**
     * Merekonstruksi assignment KasbonPayment personal ke payroll draft.
     *
     * Dua arah:
     * 1. Unassign payment personal yang ter-assign ke payroll ini tetapi tidak
     *    lagi cocok dengan karyawan/periode payroll (misal periode kasbon diubah).
     * 2. Assign payment personal yang belum ter-assign (payroll_id IS NULL)
     *    untuk karyawan + periode yang sama dengan payroll ini — kasbon baru yang
     *    dicicil setelah payroll digenerate otomatis ikut dipotong.
     *
     * Kasbon team (per divisi) tidak disentuh di sini: assignment-nya untuk rekap
     * dan bukan potongan upah per orang.
     *
     * @param  Payroll  $payroll  Payroll draft yang sedang dihitung ulang
     * @return void
     */
    private function reconcileKasbonPayments(Payroll $payroll): void
    {
        $employeeCode = $payroll->employee_id;
        $periodStart = Carbon::parse($payroll->period_start_date)->format('Y-m-d');

        KasbonPayment::where('payroll_id', $payroll->id)
            ->whereHas('kasbon', function ($q) use ($employeeCode, $periodStart) {
                $q->where('kasbon_type', 'personal')
                    ->where(function ($qq) use ($employeeCode, $periodStart) {
                        $qq->where('employee_id', '!=', $employeeCode)
                            ->orWhere('period_start_date', '!=', $periodStart);
                    });
            })
            ->update(['payroll_id' => null]);

        $unassigned = KasbonPayment::whereNull('payroll_id')
            ->where('payment_method', 'manual')
            ->whereHas('kasbon', function ($q) use ($employeeCode, $periodStart) {
                $q->where('employee_id', $employeeCode)
                    ->where('period_start_date', $periodStart)
                    ->where('kasbon_type', 'personal');
            })
            ->get();

        foreach ($unassigned as $payment) {
            $payment->payroll_id = $payroll->id;
            $payment->save();
        }
    }

    /**
     * Membayar beberapa data payroll secara massal.
     *
     * Memperbarui status dari 'draft' menjadi 'paid' dan mengatur tanggal
     * pembayaran, lalu otomatis mencatat upah pekerja ke Laporan Keuangan
     * Proyek terkait.
     *
     * Logika:
     * - Hanya payroll terpilih yang masih berstatus 'draft' yang diproses —
     *   payroll 'paid' tidak mungkin dibayar dua kali.
     * - Untuk setiap payroll yang baru dibayar, satu baris "Upah Pekerja"
     *   (expense = net_salary) dibuat pada laporan keuangan rekap proyek yang
     *   namanya cocok dengan project_name payroll (pencocokan case-insensitive).
     *   Proyek yang tidak memiliki Rekap Proyek dilewati (upah tetap dibayar,
     *   hanya entri laporan yang tidak dibuat).
     * - Pembayaran dan pembuatan entri laporan dibungkus satu transaksi DB agar
     *   gagal di tengah tidak menyisakan payroll terbayar tanpa laporan.
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

        $payrolls = Payroll::with('employee')
            ->whereIn('id', $ids)
            ->where('status', 'draft')
            ->where('created_by', auth()->id())
            ->get();

        if ($payrolls->isEmpty()) {
            return ['success' => false, 'message' => 'Tidak ada payroll yang dapat dibayar!', 'count' => 0];
        }

        try {
            return DB::transaction(function () use ($payrolls, $paymentDate) {
                $count = 0;
                $skippedRecaps = [];

                foreach ($payrolls as $payroll) {
                    $payroll->update([
                        'payment_date' => $paymentDate,
                        'status' => 'paid',
                    ]);
                    $count++;

                    if (empty($payroll->project_name)) {
                        continue;
                    }

                    $recap = ProjectRecap::whereRaw(
                        'LOWER(project_name) = ?',
                        [mb_strtolower(trim($payroll->project_name))]
                    )->first();

                    if (! $recap) {
                        $skippedRecaps[] = $payroll->project_name;
                        continue;
                    }

                    $this->financialReportService->createPayrollExpenseItem($recap, $payroll);
                }

                $message = "Berhasil membayar {$count} payroll!";

                $skippedRecaps = array_values(array_unique($skippedRecaps));

                if (! empty($skippedRecaps)) {
                    $message .= ' Entri Laporan Keuangan tidak dibuat untuk proyek yang belum memiliki Rekap Proyek: '
                        .implode(', ', $skippedRecaps).'.';
                }

                return ['success' => true, 'message' => $message, 'count' => $count];
            });
        } catch (\Throwable $e) {
            Log::error('Bulk pay payroll gagal: '.$e->getMessage());

            return ['success' => false, 'message' => 'Terjadi kesalahan saat membayar payroll. Data tidak disimpan.', 'count' => 0];
        }
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
     * Mendukung filter berdasarkan bulan, tahun, minggu, dan proyek.
     * Termasuk relasi karyawan untuk menghindari N+1 saat ekspor.
     *
     * @param  int|null      $month
     * @param  int|null      $year
     * @param  int|null      $weekNumber
     * @param  string|null   $projectName
     * @return Collection|null  Null jika tidak ada data ditemukan
     */
    public function getPayrollsForExport(?int $month, ?int $year, ?int $weekNumber = null, ?string $projectName = null): ?Collection
    {
        $payrolls = Payroll::with('employee')
            ->where('created_by', auth()->id())
            ->when($month, fn($query) => $query->whereMonth('period_start_date', $month))
            ->when($year, fn($query) => $query->whereYear('period_start_date', $year))
            ->when($weekNumber, fn($query) => $query->where('week_number', $weekNumber))
            ->when($projectName, fn($query) => $query->where('project_name', $projectName))
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
