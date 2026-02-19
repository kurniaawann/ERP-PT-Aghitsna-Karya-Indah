<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Payroll;
use App\Models\Sdm\Employee;
use App\Models\Sdm\Attendance;
use App\Models\Sdm\Kasbon;
use App\Exports\Sdm\PayrollExport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollController extends Controller
{
    /**
     * Menampilkan halaman daftar payroll dengan fitur filter dan pencarian.
     * 
     * Fitur:
     * - Filter berdasarkan bulan, tahun, dan minggu
     * - Pencarian berdasarkan nama atau kode karyawan
     * - Pagination 10 data per halaman
     * - Sorting berdasarkan periode terbaru
     */
    public function index(Request $request)
    {
        // Ambil parameter filter dari request
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');
        $weekNumber = $request->input('week_number');

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
            ->when($weekNumber, function ($query, $weekNumber) {
                return $query->where('week_number', $weekNumber);
            })
            ->latest('period_year')
            ->latest('period_month')
            ->latest('week_number')
            ->latest('created_at')
            ->paginate(10);

        return view('pages.sdm.payroll', compact('payrolls', 'search', 'month', 'year', 'weekNumber'));
    }

    /**
     * Menampilkan form untuk generate payroll.
     * 
     * Method ini jarang digunakan karena form sudah ada di index page.
     * Hanya menyiapkan data karyawan untuk dropdown jika diperlukan.
     */
    public function create()
    {
        // Ambil semua data karyawan
        $employees = Employee::all();
        return view('pages.sdm.payroll', compact('employees'));
    }

    /**
     * Memeriksa kelengkapan data absensi sebelum generate payroll MINGGUAN.
     * 
     * Proses validasi:
     * 1. Periode: Senin sampai Minggu (7 hari)
     * 2. Hari kerja WAJIB: Senin-Sabtu (6 hari minimum)
     * 3. Hari kerja OPSIONAL: Minggu (jika ada yang masuk, dihitung sebagai hari ke-7)
     * 4. Validasi: Hanya mewajibkan Senin-Sabtu lengkap, Minggu tidak wajib
     * 
     * Return JSON:
     * - working_days: 6 hari (jika tidak ada yang masuk Minggu) atau 7 hari (jika ada)
     * - incomplete_employees: Karyawan dengan absensi Senin-Sabtu tidak lengkap
     * - already_generated: Karyawan yang sudah di-generate payroll
     * - has_new_employees: Boolean ada karyawan baru atau tidak
     * - can_generate: Boolean boleh generate atau tidak
     */
    public function checkAttendanceCompleteness(Request $request)
    {
        // Ambil parameter periode dari request
        $month = $request->period_month;
        $year = $request->period_year;
        $weekNumber = $request->week_number; // 1, 2, 3, atau 4

        // Ambil semua data karyawan
        $employees = Employee::all();

        // Hitung range tanggal untuk minggu yang dipilih
        $weekDates = $this->getWeekDateRange($year, $month, $weekNumber);
        $startDate = $weekDates['start'];
        $endDate = $weekDates['end'];

        $workingDays = 0;
        $allDates = [];
        $currentDate = $startDate->copy();

        // Loop setiap tanggal dalam minggu untuk menghitung hari dalam periode
        while ($currentDate->lte($endDate)) {
            $workingDays++;
            $allDates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }

        // Cek apakah ada karyawan yang masuk di hari Minggu
        $sundayDate = null;
        $hasSundayAttendance = false;
        $tempDate = $startDate->copy();
        while ($tempDate->lte($endDate)) {
            if ($tempDate->dayOfWeek === 0) { // Minggu
                $sundayDate = $tempDate->format('Y-m-d');
                break;
            }
            $tempDate->addDay();
        }

        if ($sundayDate) {
            $hasSundayAttendance = Attendance::whereDate('attendance_date', $sundayDate)
                ->whereIn('employee_id', $employees->pluck('employee_code'))
                ->exists();
        }

        // Working days untuk display: 6 hari (wajib) atau 7 hari (jika ada yang masuk Minggu)
        $displayWorkingDays = $hasSundayAttendance ? 7 : 6;

        // Siapkan array untuk menampung hasil pengecekan
        $incompleteEmployees = []; // Karyawan dengan absensi tidak lengkap
        $completeEmployees = []; // Karyawan dengan absensi lengkap
        $alreadyGenerated = []; // Karyawan yang sudah punya payroll
        $newEmployees = []; // Karyawan baru yang belum punya payroll
        $skippedEmployees = []; // Karyawan yang di-skip (belum join pada periode ini)

        // Loop setiap karyawan untuk dicek kelengkapan absensinya
        foreach ($employees as $employee) {
            // Cek apakah payroll untuk minggu ini sudah ada
            $existingPayroll = Payroll::where('employee_id', $employee->employee_code)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->where('week_number', $weekNumber)
                ->first();

            if ($existingPayroll) {
                // Jika sudah ada payroll, masukkan ke array already_generated
                $alreadyGenerated[] = [
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                ];
                continue; // Skip pengecekan selanjutnya untuk karyawan ini
            }

            // Ambil tanggal join karyawan
            $employeeJoinDate = Carbon::parse($employee->join_date);

            // Skip karyawan yang join SETELAH periode berakhir
            if ($employeeJoinDate->greaterThan($endDate)) {
                continue;
            }

            // Karyawan ini adalah karyawan yang HARUS punya payroll (belum di-generate)
            $newEmployees[] = [
                'name' => $employee->name,
                'employee_code' => $employee->employee_code,
                'join_date' => $employeeJoinDate->format('Y-m-d'),
            ];

            // Hitung hari kerja WAJIB untuk karyawan ini (Senin-Sabtu saja, Minggu opsional)
            $employeeWorkingDates = [];
            $employeeStartDate = $employeeJoinDate->greaterThan($startDate) ? $employeeJoinDate : $startDate;
            $currentCheckDate = $employeeStartDate->copy();

            while ($currentCheckDate->lte($endDate)) {
                // Hanya hitung Senin-Sabtu sebagai hari kerja wajib (dayOfWeek 1-6)
                // Minggu (dayOfWeek 0) adalah opsional, tidak wajib diisi
                if ($currentCheckDate->dayOfWeek !== 0) {
                    $employeeWorkingDates[] = $currentCheckDate->format('Y-m-d');
                }
                $currentCheckDate->addDay();
            }

            // Ambil semua data absensi karyawan untuk periode ini
            $attendances = Attendance::where('employee_id', $employee->employee_code)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get();

            \Log::info('Fetched attendances', [
                'employee' => $employee->name,
                'employee_code' => $employee->employee_code,
                'attendance_count' => $attendances->count(),
                'attendances' => $attendances->pluck('attendance_date')->toArray(),
            ]);

            // Mapping tanggal absensi ke format Y-m-d
            $attendanceDates = $attendances->pluck('attendance_date')->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })->toArray();

            // Cari tanggal yang belum diisi absensi (tanggal kerja minus tanggal absensi)
            $missingDates = array_diff($employeeWorkingDates, $attendanceDates);

            \Log::info('Employee attendance check', [
                'name' => $employee->name,
                'employee_code' => $employee->employee_code,
                'employee_start_date' => $employeeStartDate->format('Y-m-d'),
                'working_dates' => $employeeWorkingDates,
                'working_dates_count' => count($employeeWorkingDates),
                'attendance_dates' => $attendanceDates,
                'filled_dates_count' => count($attendanceDates),
                'missing_dates_count' => count($missingDates),
                'missing_dates' => array_values($missingDates)
            ]);

            // VALIDASI: Semua hari kerja karyawan harus terisi absensi
            // Hari kerja dihitung dari tanggal join atau awal periode (mana yang lebih akhir)
            // Minimal harus ada absensi dan semua tanggal kerja terisi
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
                // Karyawan dengan absensi lengkap
                $completeEmployees[] = [
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'total_days' => $requiredDays,
                    'filled_days' => $filledDays,
                ];
            }
        }

        // Tentukan apakah ada karyawan baru (belum punya payroll untuk periode ini)
        $hasNewEmployees = count($newEmployees) > 0;

        // VALIDASI KASBON - Cek apakah ada kasbon yang melebihi gaji
        $kasbonIssues = [];
        $divisionTotals = []; // Total gaji per divisi

        // 1. Hitung total gaji per divisi (daily_wage × 6 hari kerja wajib)
        foreach ($employees as $employee) {
            $dailyWage = $employee->daily_wage ?? $employee->base_salary;
            $maxSalary = $dailyWage * 6; // 6 hari kerja wajib (Senin-Sabtu)

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

        // 2. Validasi kasbon personal per karyawan
        foreach ($employees as $employee) {
            $dailyWage = $employee->daily_wage ?? $employee->base_salary;
            $maxSalary = $dailyWage * 6; // 6 hari kerja wajib

            $personalKasbon = \App\Models\Sdm\Kasbon::getTotalForEmployee(
                $employee->employee_code,
                $month,
                $year,
                $weekNumber
            );

            if ($personalKasbon > $maxSalary) {
                $kasbonIssues[] = [
                    'type' => 'personal',
                    'employee_name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'kasbon_amount' => $personalKasbon,
                    'max_salary' => $maxSalary,
                    'daily_wage' => $dailyWage,
                ];
            }
        }

        // 3. Validasi kasbon divisi (team)
        $teamKasbons = \App\Models\Sdm\Kasbon::where('kasbon_type', 'team')
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->where('week_number', $weekNumber)
            ->where('status', 'pending')
            ->get();

        foreach ($teamKasbons as $teamKasbon) {
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

        // Tentukan apakah boleh generate:
        // 1. Harus ada karyawan yang perlu di-generate (newEmployees tidak kosong)
        // 2. Tidak boleh ada karyawan dengan absensi tidak lengkap (incompleteEmployees kosong)
        // 3. Tidak boleh ada kasbon yang melebihi gaji (kasbonIssues kosong)
        $canGenerate = count($newEmployees) > 0 && count($incompleteEmployees) === 0 && count($kasbonIssues) === 0;

        // Debug log
        \Log::info('Payroll Check Attendance RESULT', [
            'month' => $month,
            'year' => $year,
            'week' => $weekNumber,
            'period' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
            'total_employees' => count($employees),
            'new_employees_count' => count($newEmployees),
            'new_employees_list' => array_column($newEmployees, 'name'),
            'complete_count' => count($completeEmployees),
            'complete_list' => array_column($completeEmployees, 'name'),
            'incomplete_count' => count($incompleteEmployees),
            'incomplete_list' => array_column($incompleteEmployees, 'name'),
            'already_generated_count' => count($alreadyGenerated),
            'already_generated_list' => array_column($alreadyGenerated, 'name'),
            'can_generate' => $canGenerate,
        ]);

        // Return response JSON untuk AJAX request dari frontend
        return response()->json([
            'working_days' => $displayWorkingDays,
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
            'kasbon_issues' => $kasbonIssues, // Tambahkan informasi kasbon bermasalah
            'can_generate' => $canGenerate, // Boleh generate hanya jika ada karyawan baru DAN tidak ada yang incomplete DAN tidak ada kasbon bermasalah
        ]);
    }

    /**
     * Generate payroll MINGGUAN untuk pekerja harian.
     * 
     * Proses perhitungan:
     * 0. VALIDASI: Cek kelengkapan absensi semua karyawan terlebih dahulu
     * 1. Loop semua pekerja harian aktif
     * 2. Skip yang sudah punya payroll untuk minggu ini
     * 3. Ambil data absensi untuk minggu tersebut (Senin-Sabtu)
     * 4. Hitung upah: daily_wage × hari masuk (tidak masuk = tidak dibayar)
     * 5. Hitung kasbon yang perlu dipotong (personal + bagian dari team kasbon)
     * 6. Tambah additional expenses (token listrik/air, dll)
     * 7. Hitung upah bersih: Total Upah - Kasbon
     * 8. Simpan ke database dengan status 'draft'
     * 
     * Catatan:
     * - Pekerja harian dibayar per hari × hari masuk
     * - Tidak masuk = tidak dapat upah hari itu
     * - Kasbon otomatis dipotong saat generate payroll
     * - Additional expenses adalah pengeluaran PT untuk benefit karyawan
     * - WAJIB: Semua absensi harus lengkap sebelum generate
     */
    public function generate(Request $request)
    {
        // Ambil parameter periode dari request
        $month = $request->period_month;
        $year = $request->period_year;
        $weekNumber = $request->week_number; // 1, 2, 3, atau 4
        $additionalExpenses = $request->additional_expenses ?? 0; // Token listrik/air, dll (opsional)
        $additionalExpensesNotes = $request->additional_expenses_notes;

        // Hitung range tanggal untuk minggu yang dipilih
        $weekDates = $this->getWeekDateRange($year, $month, $weekNumber);
        $startDate = $weekDates['start'];
        $endDate = $weekDates['end'];

        // ========================================
        // VALIDASI KELENGKAPAN ABSENSI
        // ========================================
        $employees = Employee::all();
        $incompleteEmployees = [];

        foreach ($employees as $employee) {
            // Skip karyawan yang sudah punya payroll untuk periode ini
            $existingPayroll = Payroll::where('employee_id', $employee->employee_code)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->where('week_number', $weekNumber)
                ->first();

            if ($existingPayroll) {
                continue;
            }

            // Ambil tanggal join karyawan
            $employeeJoinDate = Carbon::parse($employee->join_date);

            // Skip karyawan yang belum join pada periode ini
            if ($employeeJoinDate->greaterThan($endDate)) {
                continue;
            }

            // Hitung hari kerja WAJIB yang seharusnya diisi (Senin-Sabtu saja, Minggu opsional)
            $employeeWorkingDates = [];
            $employeeStartDate = $employeeJoinDate->greaterThan($startDate) ? $employeeJoinDate : $startDate;
            $currentCheckDate = $employeeStartDate->copy();

            while ($currentCheckDate->lte($endDate)) {
                // Hanya hitung Senin-Sabtu sebagai hari kerja wajib (dayOfWeek 1-6)
                // Minggu (dayOfWeek 0) adalah opsional, tidak wajib diisi
                if ($currentCheckDate->dayOfWeek !== 0) {
                    $employeeWorkingDates[] = $currentCheckDate->format('Y-m-d');
                }
                $currentCheckDate->addDay();
            }

            // Ambil data absensi yang sudah terisi
            $attendances = Attendance::where('employee_id', $employee->employee_code)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get();

            $attendanceDates = $attendances->pluck('attendance_date')->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })->toArray();

            // Cek tanggal yang belum diisi
            $missingDates = array_diff($employeeWorkingDates, $attendanceDates);

            // VALIDASI: Semua hari kerja harus terisi absensi
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

        // TOLAK jika ada absensi yang tidak lengkap atau kurang dari 6
        if (count($incompleteEmployees) > 0) {
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

            $errorMessage = '<strong>Tidak dapat generate payroll!</strong><br>Data absensi belum lengkap untuk beberapa karyawan.<br><br>';

            foreach ($incompleteEmployees as $emp) {
                $errorMessage .= '❌ <strong>' . $emp['name'] . '</strong> (' . $emp['employee_code'] . '): ';
                $errorMessage .= '<strong class="text-red-600">' . $emp['filled_days'] . '</strong> dari <strong>' . $emp['total_days'] . '</strong> hari kerja';

                // Tampilkan tanggal yang kosong jika ada
                if (!empty($emp['missing_dates'])) {
                    $dates = array_map(function ($date) {
                        return Carbon::parse($date)->format('d/m');
                    }, $emp['missing_dates']);
                    $errorMessage .= '<br>&nbsp;&nbsp;&nbsp;Tanggal kosong: ' . implode(', ', $dates);
                }
                $errorMessage .= '<br>';
            }

            $errorMessage .= '<br><strong>Catatan:</strong> Setiap karyawan harus memiliki absensi lengkap untuk semua hari kerjanya.<br>Silakan lengkapi data absensi di menu <strong>SDM → Absensi</strong> terlebih dahulu.';

            return redirect()->back()->with('error', $errorMessage);
        }
        // ========================================
        // END VALIDASI
        // ========================================

        // Group employees by division untuk hitung kasbon team per divisi
        $divisionGroups = $employees->groupBy('division');

        // Hitung kasbon team per divisi
        $kasbonPerDivision = [];
        foreach ($divisionGroups as $division => $divisionEmployees) {
            if ($division) { // Skip if division is null
                $totalTeamKasbonForDivision = Kasbon::where('kasbon_type', 'team')
                    ->where('division', $division)
                    ->where('period_month', $month)
                    ->where('period_year', $year)
                    ->where('week_number', $weekNumber)
                    ->where('status', 'pending')
                    ->sum('amount');

                $employeeCountInDivision = $divisionEmployees->count();
                $kasbonPerDivision[$division] = $employeeCountInDivision > 0
                    ? $totalTeamKasbonForDivision / $employeeCountInDivision
                    : 0;
            }
        }

        // Loop setiap pekerja untuk generate payroll
        foreach ($employees as $employee) {
            // Cek apakah payroll untuk minggu ini sudah ada
            $existingPayroll = Payroll::where('employee_id', $employee->employee_code)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->where('week_number', $weekNumber)
                ->first();

            if ($existingPayroll) {
                continue; // Skip jika sudah pernah digenerate
            }

            // Ambil data absensi pekerja untuk minggu ini
            $attendances = Attendance::where('employee_id', $employee->employee_code)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get();

            // Hitung hari masuk (hadir + lembur)
            $presentDays = $attendances->whereIn('status', ['hadir', 'lembur'])->count();
            $permissionDays = $attendances->where('status', 'izin')->count();
            $sickDays = $attendances->where('status', 'sakit')->count();
            $leaveDays = $attendances->where('status', 'cuti')->count();
            $overtimeDays = $attendances->where('status', 'lembur')->count();

            // Hitung total hari kerja dalam minggu (bisa 6-7 hari tergantung apakah ada yang masuk Minggu)
            $totalWorkDays = $weekDates['working_days'];

            // Upah pekerja harian = daily_wage × hari masuk
            // Jika tidak masuk = tidak dapat upah
            $dailyWage = $employee->daily_wage ?? $employee->base_salary;
            $totalWage = $dailyWage * $presentDays;

            // Hitung bonus lembur jika ada
            $overtimeTotal = $attendances->where('status', 'lembur')->sum('overtime_total');

            // Total upah kotor = upah harian × hari masuk + bonus lembur
            $grossWage = $totalWage + $overtimeTotal;

            // Hitung kasbon yang perlu dipotong
            // 1. Kasbon personal
            $personalKasbon = Kasbon::getTotalForEmployee($employee->employee_code, $month, $year, $weekNumber);
            // 2. Bagian dari kasbon team (hanya untuk divisi yang sama)
            $teamKasbonPerPerson = $employee->division && isset($kasbonPerDivision[$employee->division])
                ? $kasbonPerDivision[$employee->division]
                : 0;
            $totalKasbonDeduction = $personalKasbon + $teamKasbonPerPerson;

            // Hitung upah bersih = Upah Kotor - Kasbon
            $netWage = $grossWage - $totalKasbonDeduction;

            // Buat payroll record
            $payroll = Payroll::create([
                'employee_id' => $employee->employee_code,
                'period_month' => $month,
                'period_year' => $year,
                'period_type' => 'weekly',
                'week_number' => $weekNumber,
                'base_salary' => $dailyWage, // Simpan daily wage di base_salary
                'total_work_days' => $totalWorkDays,
                'present_days' => $presentDays,
                'permission_days' => $permissionDays,
                'sick_days' => $sickDays,
                'leave_days' => $leaveDays,
                'overtime_days' => $overtimeDays,
                'deduction_amount' => 0, // Tidak ada potongan izin/sakit untuk pekerja harian
                'overtime_total' => $overtimeTotal,
                'kasbon_deduction' => $totalKasbonDeduction,
                'additional_expenses' => $additionalExpenses, // Token listrik/air, dll
                'additional_expenses_notes' => $additionalExpensesNotes,
                'net_salary' => $netWage,
                'status' => 'draft',
            ]);

            // Update status kasbon menjadi 'deducted'
            $kasbons = Kasbon::where('employee_id', $employee->employee_code)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->where('week_number', $weekNumber)
                ->pending()
                ->get();

            foreach ($kasbons as $kasbon) {
                $kasbon->markAsDeducted($payroll->id);
            }
        }

        // Update kasbon team untuk setiap divisi (hanya sekali per divisi)
        $processedDivisions = [];
        foreach ($employees as $employee) {
            if ($employee->division && !in_array($employee->division, $processedDivisions)) {
                $teamKasbons = Kasbon::where('kasbon_type', 'team')
                    ->where('division', $employee->division)
                    ->where('period_month', $month)
                    ->where('period_year', $year)
                    ->where('week_number', $weekNumber)
                    ->pending()
                    ->get();

                foreach ($teamKasbons as $kasbon) {
                    // Ambil payroll pertama dari divisi ini untuk referensi
                    $firstPayrollInDivision = Payroll::whereHas('employee', function ($q) use ($employee) {
                        $q->where('division', $employee->division);
                    })
                        ->where('period_month', $month)
                        ->where('period_year', $year)
                        ->where('week_number', $weekNumber)
                        ->first();

                    if ($firstPayrollInDivision) {
                        $kasbon->markAsDeducted($firstPayrollInDivision->id);
                    }
                }

                $processedDivisions[] = $employee->division;
            }
        }

        return redirect()->route('payroll.index')->with('success', 'Payroll berhasil digenerate!');
    }

    /**
     * Membayar payroll secara bulk (multiple selection).
     * 
     * Proses:
     * 1. Ambil array ID payroll yang dipilih dari checkbox
     * 2. Validasi apakah ada data yang dipilih
     * 3. Update status dari 'draft' menjadi 'paid'
     * 4. Set tanggal pembayaran (default: hari ini)
     * 
     * Catatan:
     * - Hanya payroll berstatus 'draft' yang bisa dibayar
     * - Payroll yang sudah 'paid' tidak akan diupdate lagi
     */
    public function bulkPay(Request $request)
    {
        // Ambil array ID dari checkbox yang dipilih
        $ids = $request->input('ids');
        // Ambil tanggal pembayaran (default: hari ini)
        $paymentDate = $request->input('payment_date', now()->toDateString());

        // Validasi: pastikan ada data yang dipilih
        if (empty($ids)) {
            return redirect()->route('payroll.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Update payroll yang statusnya masih 'draft' menjadi 'paid'
        $updated = Payroll::whereIn('id', $ids)
            ->where('status', 'draft') // Hanya yang draft
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
     * Menghapus data payroll secara bulk (multiple selection).
     * 
     * Proses:
     * 1. Ambil array ID yang dipilih dari checkbox
     * 2. Validasi apakah ada data yang dipilih
     * 3. Hapus data payroll yang berstatus 'draft'
     * 
     * Keamanan:
     * - Hanya payroll berstatus 'draft' yang bisa dihapus
     * - Payroll yang sudah 'paid' tidak bisa dihapus (proteksi data)
     */
    public function destroy(Request $request)
    {
        // Ambil array ID dari checkbox yang dipilih
        $ids = $request->input('ids');

        // Validasi: pastikan ada data yang dipilih
        if (empty($ids)) {
            return redirect()->route('payroll.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Hapus hanya payroll yang statusnya masih 'draft'
        // Payroll yang sudah 'paid' tidak akan terhapus (sebagai proteksi)
        Payroll::whereIn('id', $ids)->where('status', 'draft')->delete();

        return redirect()->route('payroll.index')->with('success', 'Data payroll berhasil dihapus!');
    }

    /**
     * Export data payroll ke file Excel (.xlsx).
     * 
     * Fitur:
     * - Export semua data atau filter berdasarkan bulan/tahun
     * - Format file: Laporan_Payroll_{bulan}_{tahun}_{timestamp}.xlsx
     * - Menggunakan library Maatwebsite Excel
     * - Include data karyawan (relasi)
     * 
     * Filter:
     * - Jika bulan & tahun dipilih: export data periode tertentu
     * - Jika kosong: export semua data payroll
     */
    public function exportExcel(Request $request)
    {
        // Ambil parameter filter dari request
        $month = $request->input('month');
        $year = $request->input('year');

        // Query data payroll dengan filter (jika ada)
        $payrolls = Payroll::with('employee')
            ->when($month, function ($query, $month) {
                // Filter berdasarkan bulan jika dipilih
                return $query->where('period_month', $month);
            })
            ->when($year, function ($query, $year) {
                // Filter berdasarkan tahun jika dipilih
                return $query->where('period_year', $year);
            })
            ->latest('period_year')
            ->latest('period_month')
            ->latest('created_at')
            ->get();

        // Validasi: pastikan ada data untuk diexport
        if ($payrolls->isEmpty()) {
            return redirect()->route('payroll.index')->with('error', 'Tidak ada data payroll untuk diexport!');
        }

        // Generate nama file dengan format: Laporan_Payroll_{periode}_{timestamp}.xlsx
        $fileName = 'Laporan_Payroll_' . ($month ? $month . '_' : '') . ($year ? $year : 'Semua') . '_' . date('Ymd_His') . '.xlsx';

        // Download file Excel menggunakan PayrollExport class
        return Excel::download(new PayrollExport($payrolls, $month, $year), $fileName);
    }

    /**
     * Export data payroll ke file PDF.
     * 
     * Fitur:
     * - Export semua data atau filter berdasarkan bulan/tahun
     * - Format file: Laporan_Payroll_{bulan}_{tahun}_{timestamp}.pdf
     * - Orientasi landscape (karena banyak kolom)
     * - Include ringkasan total (gaji pokok, potongan, lembur, gaji bersih)
     * - Menggunakan library DomPDF
     * 
     * Layout:
     * - Header dengan periode laporan
     * - Tabel detail payroll semua karyawan
     * - Footer dengan total keseluruhan
     */
    public function exportPdf(Request $request)
    {
        // Ambil parameter filter dari request
        $month = $request->input('month');
        $year = $request->input('year');

        // Query data payroll dengan filter (jika ada)
        $payrolls = Payroll::with('employee')
            ->when($month, function ($query, $month) {
                // Filter berdasarkan bulan jika dipilih
                return $query->where('period_month', $month);
            })
            ->when($year, function ($query, $year) {
                // Filter berdasarkan tahun jika dipilih
                return $query->where('period_year', $year);
            })
            ->latest('period_year')
            ->latest('period_month')
            ->latest('created_at')
            ->get();

        // Validasi: pastikan ada data untuk diexport
        if ($payrolls->isEmpty()) {
            return redirect()->route('payroll.index')->with('error', 'Tidak ada data payroll untuk diexport!');
        }

        // Format teks periode untuk ditampilkan di header PDF
        if ($month && $year) {
            // Jika bulan & tahun dipilih: "Januari 2025"
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
            // Jika hanya tahun: "Tahun 2025"
            $periodText = 'Tahun ' . $year;
        } else {
            // Jika tidak ada filter: "Semua Periode"
            $periodText = 'Semua Periode';
        }

        // Hitung total keseluruhan untuk ditampilkan di footer PDF
        $totalBaseSalary = $payrolls->sum('base_salary'); // Total gaji pokok
        $totalDeduction = $payrolls->sum('deduction_amount'); // Total potongan
        $totalOvertime = $payrolls->sum('overtime_total'); // Total lembur
        $totalNetSalary = $payrolls->sum('net_salary'); // Total gaji bersih

        // Siapkan data untuk dikirim ke view PDF
        $data = [
            'payrolls' => $payrolls, // Data payroll semua karyawan
            'periodText' => $periodText, // Teks periode untuk header
            'totalBaseSalary' => $totalBaseSalary,
            'totalDeduction' => $totalDeduction,
            'totalOvertime' => $totalOvertime,
            'totalNetSalary' => $totalNetSalary,
        ];

        // Generate PDF dari view dengan orientasi landscape
        $pdf = Pdf::loadView('exports.sdm.payroll-pdf', $data);
        $pdf->setPaper('a4', 'landscape'); // Kertas A4 landscape (karena banyak kolom)

        // Generate nama file dengan format: Laporan_Payroll_{periode}_{timestamp}.pdf
        $fileName = 'Laporan_Payroll_' . ($month ? $month . '_' : '') . ($year ? $year : 'Semua') . '_' . date('Ymd_His') . '.pdf';

        // Download file PDF
        return $pdf->download($fileName);
    }

    /**
     * Helper: Hitung range tanggal untuk minggu tertentu dalam bulan.
     * 
     * Minggu 1: Tanggal 1-7
     * Minggu 2: Tanggal 8-14
     * Minggu 3: Tanggal 15-21
     * Minggu 4: Tanggal 22-akhir bulan
     * 
     * @param int $year Tahun
     * @param int $month Bulan (1-12)
     * @param int $weekNumber Minggu ke berapa (1-4)
     * @return array ['start' => Carbon, 'end' => Carbon, 'working_days' => int]
     */
    /**
     * Mendapatkan range tanggal untuk minggu tertentu berdasarkan hari kerja (Senin-Sabtu)
     * Sistem akan mencari hari Senin di minggu tersebut dan menghitung sampai Sabtu
     * 
     * Contoh Februari 2026:
     * - 1 Feb = Minggu
     * - Minggu 1: Senin 2 Feb - Sabtu 7 Feb (6 hari kerja)
     * - Minggu 2: Senin 9 Feb - Sabtu 14 Feb (6 hari kerja)
     * - Minggu 3: Senin 16 Feb - Sabtu 21 Feb (6 hari kerja)
     * - Minggu 4: Senin 23 Feb - Sabtu 28 Feb (6 hari kerja)
     */
    private function getWeekDateRange($year, $month, $weekNumber)
    {
        // Buat objek untuk tanggal 1 bulan tersebut
        $firstDayOfMonth = Carbon::create($year, $month, 1);

        // Cari hari Senin pertama di bulan ini
        // dayOfWeek: 0=Minggu, 1=Senin, 2=Selasa, ..., 6=Sabtu
        if ($firstDayOfMonth->dayOfWeek === 0) {
            // Jika tanggal 1 adalah Minggu, Senin pertama adalah tanggal 2
            $firstMonday = $firstDayOfMonth->copy()->addDay();
        } elseif ($firstDayOfMonth->dayOfWeek === 1) {
            // Jika tanggal 1 adalah Senin, langsung pakai tanggal 1
            $firstMonday = $firstDayOfMonth->copy();
        } else {
            // Jika tanggal 1 adalah Selasa-Sabtu, cari Senin berikutnya
            $firstMonday = $firstDayOfMonth->copy()->next(Carbon::MONDAY);
        }

        // Hitung tanggal mulai berdasarkan minggu
        // Minggu 1 = Senin pertama + 0 minggu
        // Minggu 2 = Senin pertama + 1 minggu
        // Minggu 3 = Senin pertama + 2 minggu
        // Minggu 4 = Senin pertama + 3 minggu
        $startDate = $firstMonday->copy()->addWeeks($weekNumber - 1);

        // Tanggal akhir adalah Minggu (6 hari setelah Senin)
        $endDate = $startDate->copy()->addDays(6); // Senin + 6 = Minggu

        // Pastikan endDate tidak melebihi akhir bulan
        $lastDayOfMonth = Carbon::create($year, $month, 1)->endOfMonth();
        if ($endDate->greaterThan($lastDayOfMonth)) {
            $endDate = $lastDayOfMonth;
        }

        // Hitung hari kerja (termasuk Minggu jika ada yang masuk)
        $workingDays = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            // Hitung semua hari dalam periode (Senin-Minggu)
            $workingDays++;
            $currentDate->addDay();
        }

        \Log::info('Week Date Range Calculated', [
            'year' => $year,
            'month' => $month,
            'week_number' => $weekNumber,
            'first_monday' => $firstMonday->format('Y-m-d (l)'),
            'start_date' => $startDate->format('Y-m-d (l)'),
            'end_date' => $endDate->format('Y-m-d (l)'),
            'working_days' => $workingDays,
        ]);

        return [
            'start' => $startDate,
            'end' => $endDate,
            'working_days' => $workingDays,
        ];
    }
}
