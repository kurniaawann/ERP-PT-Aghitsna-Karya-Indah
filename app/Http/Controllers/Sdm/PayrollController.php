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
     * 1. Hitung total hari kerja dalam 1 minggu (Senin-Sabtu, 6 hari)
     * 2. Cek setiap karyawan apakah sudah punya payroll untuk minggu ini
     * 3. Cek kelengkapan absensi berdasarkan tanggal join karyawan
     * 4. Identifikasi karyawan baru (belum punya payroll)
     * 
     * Return JSON:
     * - working_days: Total hari kerja dalam minggu (6 hari)
     * - incomplete_employees: Karyawan dengan absensi tidak lengkap
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

        // Ambil semua data karyawan aktif
        $employees = Employee::all();

        // Hitung range tanggal untuk minggu yang dipilih
        $weekDates = $this->getWeekDateRange($year, $month, $weekNumber);
        $startDate = $weekDates['start'];
        $endDate = $weekDates['end'];

        $workingDays = 0;
        $allDates = [];
        $currentDate = $startDate->copy();

        // Loop setiap tanggal dalam minggu untuk menghitung hari kerja (Senin-Sabtu)
        while ($currentDate->lte($endDate)) {
            // Hanya hitung Senin-Sabtu (dayOfWeek 1-6), skip Minggu (0)
            if ($currentDate->dayOfWeek !== 0) {
                $workingDays++;
                $allDates[] = $currentDate->format('Y-m-d');
            }
            $currentDate->addDay();
        }

        // Siapkan array untuk menampung hasil pengecekan
        $incompleteEmployees = []; // Karyawan dengan absensi tidak lengkap
        $alreadyGenerated = []; // Karyawan yang sudah punya payroll
        $newEmployees = []; // Karyawan baru yang belum punya payroll

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

            // Jika karyawan join sebelum atau pada periode ini, masukkan ke new_employees
            if ($employeeJoinDate->lessThanOrEqualTo($endDate)) {
                $newEmployees[] = [
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'join_date' => $employeeJoinDate->format('Y-m-d'),
                ];
            }

            // Ambil semua data absensi karyawan untuk periode ini
            $attendances = Attendance::where('employee_id', $employee->employee_code)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get();

            // Mapping tanggal absensi ke format Y-m-d
            $attendanceDates = $attendances->pluck('attendance_date')->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })->toArray();

            // Hitung hari kerja yang seharusnya diisi untuk karyawan ini
            // (hanya hitung sejak tanggal join sampai akhir periode)
            $employeeWorkingDates = [];
            if ($employeeJoinDate->lessThanOrEqualTo($endDate)) {
                // Tentukan start date: jika join di tengah bulan, mulai dari join date
                $employeeStartDate = $employeeJoinDate->greaterThan($startDate) ? $employeeJoinDate : $startDate;
                $currentCheckDate = $employeeStartDate->copy();

                // Loop dari start date sampai end date
                while ($currentCheckDate->lte($endDate)) {
                    // Hanya hitung Senin-Sabtu (skip Minggu)
                    if ($currentCheckDate->dayOfWeek !== 0) {
                        $employeeWorkingDates[] = $currentCheckDate->format('Y-m-d');
                    }
                    $currentCheckDate->addDay();
                }
            }

            // Cari tanggal yang belum diisi absensi (tanggal kerja minus tanggal absensi)
            $missingDates = array_diff($employeeWorkingDates, $attendanceDates);

            // Jika ada tanggal yang kosong, masukkan ke array incomplete
            if (count($missingDates) > 0) {
                $incompleteEmployees[] = [
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'total_days' => count($employeeWorkingDates), // Total hari kerja yang seharusnya
                    'filled_days' => count($attendanceDates), // Hari yang sudah diisi
                    'missing_days' => count($missingDates), // Hari yang belum diisi
                    'missing_dates' => array_values($missingDates), // Detail tanggal yang kosong
                ];
            }
        }

        // Tentukan apakah ada karyawan baru (belum punya payroll untuk periode ini)
        $hasNewEmployees = count($newEmployees) > 0;

        // Return response JSON untuk AJAX request dari frontend
        return response()->json([
            'working_days' => $workingDays,
            'incomplete_employees' => $incompleteEmployees,
            'already_generated' => $alreadyGenerated,
            'has_new_employees' => $hasNewEmployees,
            'new_employees' => $newEmployees,
            'total_employees' => count($employees),
            'can_generate' => count($incompleteEmployees) === 0, // Hanya boleh generate jika tidak ada incomplete
        ]);
    }

    /**
     * Generate payroll MINGGUAN untuk pekerja harian.
     * 
     * Proses perhitungan:
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

        // Ambil semua pekerja
        $employees = Employee::all();

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

            // Hitung total hari kerja dalam minggu (Senin-Sabtu = 6 hari)
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
    private function getWeekDateRange($year, $month, $weekNumber)
    {
        // Tentukan tanggal awal dan akhir berdasarkan minggu
        if ($weekNumber == 1) {
            $startDay = 1;
            $endDay = 7;
        } elseif ($weekNumber == 2) {
            $startDay = 8;
            $endDay = 14;
        } elseif ($weekNumber == 3) {
            $startDay = 15;
            $endDay = 21;
        } else { // Minggu 4
            $startDay = 22;
            // Akhir bulan bisa 28, 29, 30, atau 31 tergantung bulannya
            $endDay = Carbon::create($year, $month, 1)->endOfMonth()->day;
        }

        $startDate = Carbon::create($year, $month, $startDay);
        $endDate = Carbon::create($year, $month, $endDay);

        // Hitung hari kerja (Senin-Sabtu saja, skip Minggu)
        $workingDays = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            // dayOfWeek: 0=Minggu, 1=Senin, ..., 6=Sabtu
            if ($currentDate->dayOfWeek !== 0) {
                $workingDays++;
            }
            $currentDate->addDay();
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
            'working_days' => $workingDays,
        ];
    }
}
