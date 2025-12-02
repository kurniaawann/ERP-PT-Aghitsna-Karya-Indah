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
     * Menampilkan halaman daftar payroll dengan fitur filter dan pencarian.
     * 
     * Fitur:
     * - Filter berdasarkan bulan dan tahun
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
     * Memeriksa kelengkapan data absensi sebelum generate payroll.
     * 
     * Proses validasi:
     * 1. Hitung total hari kerja (Senin-Sabtu, ~26-27 hari/bulan)
     * 2. Cek setiap karyawan apakah sudah punya payroll untuk periode ini
     * 3. Cek kelengkapan absensi berdasarkan tanggal join karyawan
     * 4. Identifikasi karyawan baru (belum punya payroll)
     * 
     * Return JSON:
     * - working_days: Total hari kerja dalam periode
     * - incomplete_employees: Karyawan dengan absensi tidak lengkap
     * - already_generated: Karyawan yang sudah di-generate payroll
     * - has_new_employees: Boolean ada karyawan baru atau tidak
     * - can_generate: Boolean boleh generate atau tidak
     */
    public function checkAttendanceCompleteness(Request $request)
    {
        // Ambil parameter bulan dan tahun dari request
        $month = $request->period_month;
        $year = $request->period_year;

        // Ambil semua data karyawan aktif
        $employees = Employee::all();

        // Hitung total hari kerja dalam periode (Senin-Sabtu saja, Minggu libur)
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $workingDays = 0;
        $allDates = [];
        $currentDate = $startDate->copy();

        // Loop setiap tanggal dalam periode untuk menghitung hari kerja
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
            // Cek apakah payroll untuk periode ini sudah ada
            $existingPayroll = Payroll::where('employee_id', $employee->employee_code)
                ->where('period_month', $month)
                ->where('period_year', $year)
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
     * Generate payroll untuk periode tertentu (bulan & tahun).
     * 
     * Proses perhitungan:
     * 1. Loop semua karyawan aktif
     * 2. Skip karyawan yang sudah punya payroll untuk periode ini
     * 3. Ambil data absensi karyawan untuk periode tersebut
     * 4. Hitung breakdown absensi (hadir, izin, sakit, cuti, lembur)
     * 5. Hitung potongan: Rp 30.000/hari untuk izin & sakit (BUKAN cuti)
     * 6. Hitung total lembur dari data overtime
     * 7. Hitung gaji bersih: Base Salary - Potongan + Overtime
     * 8. Simpan ke database dengan status 'draft'
     * 
     * Catatan:
     * - Cuti tidak dipotong gaji
     * - Izin dan sakit dipotong Rp 30.000/hari
     * - Payroll awal berstatus 'draft', bisa diubah ke 'paid' nanti
     */
    public function generate(Request $request)
    {
        // Ambil parameter periode dari request
        $month = $request->period_month;
        $year = $request->period_year;

        // Ambil semua data karyawan
        $employees = Employee::all();

        // Loop setiap karyawan untuk generate payroll
        foreach ($employees as $employee) {
            // Cek apakah payroll untuk periode ini sudah ada
            $existingPayroll = Payroll::where('employee_id', $employee->employee_code)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->first();

            if ($existingPayroll) {
                continue; // Skip jika sudah pernah digenerate
            }

            // Tentukan range tanggal untuk periode ini
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            // Ambil semua data absensi karyawan untuk periode ini
            $attendances = Attendance::where('employee_id', $employee->employee_code)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get();

            // Hitung breakdown berdasarkan status absensi
            $presentDays = $attendances->where('status', 'hadir')->count(); // Hari hadir
            $permissionDays = $attendances->where('status', 'izin')->count(); // Hari izin
            $sickDays = $attendances->where('status', 'sakit')->count(); // Hari sakit
            $leaveDays = $attendances->where('status', 'cuti')->count(); // Hari cuti
            $overtimeDays = $attendances->where('status', 'lembur')->count(); // Hari lembur

            // Hitung potongan gaji: Rp 30.000 per hari untuk izin & sakit
            // PENTING: Cuti TIDAK dipotong!
            $deductionDays = $permissionDays + $sickDays;
            $deductionAmount = $deductionDays * 30000;

            // Hitung total uang lembur dari field overtime_total
            $overtimeTotal = $attendances->where('status', 'lembur')->sum('overtime_total');

            // Hitung gaji bersih = Base Salary - Potongan + Overtime
            $netSalary = $employee->base_salary - $deductionAmount + $overtimeTotal;

            // Simpan data payroll ke database dengan status 'draft'
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
                'status' => 'draft', // Status awal adalah draft
            ]);
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
        $pdf = Pdf::loadView('exports.payroll_pdf', $data);
        $pdf->setPaper('a4', 'landscape'); // Kertas A4 landscape (karena banyak kolom)

        // Generate nama file dengan format: Laporan_Payroll_{periode}_{timestamp}.pdf
        $fileName = 'Laporan_Payroll_' . ($month ? $month . '_' : '') . ($year ? $year : 'Semua') . '_' . date('Ymd_His') . '.pdf';

        // Download file PDF
        return $pdf->download($fileName);
    }
}
