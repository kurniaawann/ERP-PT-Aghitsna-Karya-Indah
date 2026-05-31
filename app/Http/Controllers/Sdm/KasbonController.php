<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Kasbon;
use App\Models\Sdm\Employee;
use Illuminate\Http\Request;
use Carbon\Carbon;

class KasbonController extends Controller
{
    private function normalizeCurrencyInput($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) str_replace(['.', ','], '', (string) $value);
    }

    /**
     * Menampilkan halaman daftar kasbon dengan fitur filter dan pencarian.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');
        $status = $request->input('status');
        $type = $request->input('type');

        $kasbons = Kasbon::with('employee')
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('kasbon_code', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($empQuery) use ($search) {
                            $empQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($month, function ($query, $month) {
                return $query->where('period_month', $month);
            })
            ->when($year, function ($query, $year) {
                return $query->where('period_year', $year);
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($type, function ($query, $type) {
                return $query->where('kasbon_type', $type);
            })
            ->latest('kasbon_date')
            ->latest('created_at')
            ->paginate(10)
            ->appends($request->only(['search', 'month', 'year', 'status', 'type']));

        // Ambil data employees dan divisions untuk dropdown
        $employees = Employee::orderBy('name')->get();
        $divisions = \App\Models\Sdm\Division::orderBy('name')->get();

        return view('pages.sdm.kasbon', compact('kasbons', 'employees', 'divisions', 'search', 'month', 'year', 'status', 'type'));
    }

    /**
     * Simpan kasbon baru
     */
    public function store(Request $request)
    {
        $request->merge([
            'amount' => $this->normalizeCurrencyInput($request->input('amount')),
        ]);

        $validated = $request->validate([
            'kasbon_type' => 'required|in:personal,team',
            'employee_id' => 'required_if:kasbon_type,personal|nullable|exists:employees,employee_code',
            'division' => 'required_if:kasbon_type,team|nullable|string|max:100',
            'amount' => 'required|integer|min:1000',
            'kasbon_date' => 'required|date',
            'week_number' => 'nullable|integer|min:1|max:4',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2020',
            'notes' => 'nullable|string|max:500',
        ], [
            'kasbon_type.required' => 'Jenis kasbon harus dipilih',
            'employee_id.required_if' => 'Karyawan harus dipilih untuk kasbon personal',
            'division.required_if' => 'Divisi harus dipilih untuk kasbon tim',
            'amount.required' => 'Jumlah kasbon harus diisi',
            'amount.min' => 'Jumlah kasbon minimal Rp 1.000',
            'kasbon_date.required' => 'Tanggal kasbon harus diisi',
            'period_month.required' => 'Bulan periode harus diisi',
            'period_year.required' => 'Tahun periode harus diisi',
        ]);

        // Auto-detect minggu dari tanggal kasbon
        $weekNumber = Employee::detectWeekNumber($validated['kasbon_date']);
        $validated['week_number'] = $weekNumber;

        // Validasi kasbon personal berdasarkan kehadiran dan status payroll
        if ($validated['kasbon_type'] === 'personal') {
            $employee = Employee::findOrFail($validated['employee_id']);
            $month = $validated['period_month'];
            $year = $validated['period_year'];
            $kasbonDate = $validated['kasbon_date'];

            // Cek apakah payroll minggu ini sudah paid
            if ($employee->isPayrollPaid($month, $year, $weekNumber)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', sprintf(
                        'Tidak dapat melakukan kasbon! Payroll minggu ke-%d bulan %d/%d untuk %s sudah dibayar (status: paid). Kasbon hanya bisa dilakukan untuk minggu yang belum dibayar.',
                        $weekNumber,
                        $month,
                        $year,
                        $employee->name
                    ));
            }

            // Hitung maksimal kasbon berdasarkan kehadiran
            $daysWorked = $employee->getAttendanceUpToDate($month, $year, $weekNumber, $kasbonDate);
            $maxKasbon = $employee->getMaxKasbonUpToDate($month, $year, $weekNumber, $kasbonDate);

            // VALIDASI PENTING: Jika belum ada kehadiran sama sekali, tidak bisa kasbon!
            if ($daysWorked == 0) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', sprintf(
                        'Tidak dapat melakukan kasbon! %s belum memiliki catatan kehadiran di minggu ke-%d (tanggal %d-%d %s %d). Kasbon hanya bisa dilakukan setelah karyawan hadir bekerja.',
                        $employee->name,
                        $weekNumber,
                        (($weekNumber - 1) * 7) + 1,
                        min($weekNumber * 7, \Carbon\Carbon::create($year, $month)->endOfMonth()->day),
                        \Carbon\Carbon::create($year, $month)->translatedFormat('F'),
                        $year
                    ));
            }

            // Validasi apakah jumlah kasbon tidak melebihi maksimal
            if ($validated['amount'] > $maxKasbon) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', sprintf(
                        'Jumlah kasbon Rp %s melebihi batas maksimal! Berdasarkan kehadiran %d hari kerja (gaji harian Rp %s), maksimal kasbon adalah Rp %s',
                        number_format($validated['amount'], 0, ',', '.'),
                        $daysWorked,
                        number_format($employee->daily_wage, 0, ',', '.'),
                        number_format($maxKasbon, 0, ',', '.')
                    ));
            }
        }

        // Generate kasbon code
        $validated['kasbon_code'] = Kasbon::generateKasbonCode();
        $validated['status'] = 'pending';

        // Jika kasbon team, set employee_id ke null
        if ($validated['kasbon_type'] === 'team') {
            $validated['employee_id'] = null;
        } else {
            // Jika kasbon personal, set division ke null
            $validated['division'] = null;
        }

        Kasbon::create($validated);

        return redirect()->back()->with('success', 'Kasbon berhasil ditambahkan');
    }

    /**
     * Update kasbon yang sudah ada
     */
    public function update(Request $request, $kasbonCode)
    {
        $kasbon = Kasbon::findOrFail($kasbonCode);

        // Hanya bisa update jika status masih pending
        if ($kasbon->status === 'deducted') {
            return redirect()->back()->with('error', 'Kasbon yang sudah dipotong tidak bisa diubah');
        }

        $request->merge([
            'amount' => $this->normalizeCurrencyInput($request->input('amount')),
        ]);

        $validated = $request->validate([
            'kasbon_type' => 'required|in:personal,team',
            'employee_id' => 'required_if:kasbon_type,personal|nullable|exists:employees,employee_code',
            'division' => 'required_if:kasbon_type,team|nullable|string|max:100',
            'amount' => 'required|integer|min:1000',
            'kasbon_date' => 'required|date',
            'week_number' => 'nullable|integer|min:1|max:4',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2020',
            'notes' => 'nullable|string|max:500',
        ]);

        // Validasi kasbon personal berdasarkan kehadiran
        if ($validated['kasbon_type'] === 'personal' && $validated['week_number']) {
            $employee = Employee::findOrFail($validated['employee_id']);
            $month = $validated['period_month'];
            $year = $validated['period_year'];
            $weekNumber = $validated['week_number'];
            $kasbonDate = $validated['kasbon_date'];

            $daysWorked = $employee->getAttendanceUpToDate($month, $year, $weekNumber, $kasbonDate);
            $maxKasbon = $employee->getMaxKasbonUpToDate($month, $year, $weekNumber, $kasbonDate);
            $dailyWage = $employee->daily_wage ?? $employee->base_salary;

            if (!$employee->canTakeKasbon($validated['amount'], $month, $year, $weekNumber, $kasbonDate)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', sprintf(
                        'Kasbon melebihi batas maksimal! %s hanya masuk %d hari pada minggu ke-%d sampai tanggal %s. Maksimal kasbon: Rp %s (Rp %s × %d hari)',
                        $employee->name,
                        $daysWorked,
                        $weekNumber,
                        \Carbon\Carbon::parse($kasbonDate)->format('d/m/Y'),
                        number_format($maxKasbon, 0, ',', '.'),
                        number_format($dailyWage, 0, ',', '.'),
                        $daysWorked
                    ));
            }
        }

        // Jika kasbon team, set employee_id ke null
        if ($validated['kasbon_type'] === 'team') {
            $validated['employee_id'] = null;
        } else {
            // Jika kasbon personal, set division ke null
            $validated['division'] = null;
        }

        $kasbon->update($validated);

        return redirect()->back()->with('success', 'Kasbon berhasil diupdate');
    }

    /**
     * Hapus kasbon terpilih (bulk delete)
     */
    public function destroySelected(Request $request)
    {
        // Ambil array kasbon_code dari input dengan nama 'selected_kasbons'
        $selectedIds = $request->input('selected_kasbons', []);

        // Validasi: cek apakah tidak ada yang dipilih
        if (empty($selectedIds)) {
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        $deleted = 0;
        $skipped = 0;

        foreach ($selectedIds as $kasbonCode) {
            $kasbon = Kasbon::find($kasbonCode);

            if (!$kasbon) {
                continue;
            }

            // Hanya bisa hapus jika status masih pending
            if ($kasbon->status === 'deducted') {
                $skipped++;
                continue;
            }

            $kasbon->delete();
            $deleted++;
        }

        if ($deleted > 0 && $skipped > 0) {
            return redirect()->back()->with('success', "Berhasil menghapus {$deleted} kasbon. {$skipped} kasbon tidak dapat dihapus karena sudah dipotong.");
        } elseif ($deleted > 0) {
            return redirect()->back()->with('success', "Data terpilih berhasil dihapus. ({$deleted} kasbon)");
        } else {
            return redirect()->back()->with('error', 'Semua kasbon yang dipilih sudah dipotong dan tidak dapat dihapus.');
        }
    }

    /**
     * Get total kasbon untuk periode tertentu (untuk ditampilkan saat generate payroll)
     */
    public function getTotalForPeriod(Request $request)
    {
        $month = $request->period_month;
        $year = $request->period_year;
        $weekNumber = $request->week_number;
        $employeeId = $request->employee_id;

        if ($employeeId) {
            // Get kasbon personal untuk employee tertentu
            $personalKasbon = Kasbon::getTotalForEmployee($employeeId, $month, $year, $weekNumber);
        } else {
            $personalKasbon = 0;
        }

        // Get kasbon team untuk periode ini
        $teamKasbon = Kasbon::getTotalTeamKasbon($month, $year, $weekNumber);

        return response()->json([
            'personal_kasbon' => $personalKasbon,
            'team_kasbon' => $teamKasbon,
            'total_kasbon' => $personalKasbon + $teamKasbon,
        ]);
    }

    /**
     * Check maksimal kasbon berdasarkan kehadiran sampai tanggal kasbon
     */
    public function checkMaxKasbon(Request $request)
    {
        $employeeId = $request->input('employee_id');
        $month = $request->input('period_month');
        $year = $request->input('period_year');
        $kasbonDate = $request->input('kasbon_date');

        if (!$employeeId || !$month || !$year || !$kasbonDate) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tidak lengkap. Pastikan karyawan, bulan, tahun, dan tanggal kasbon sudah dipilih.'
            ], 400);
        }

        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan'
            ], 404);
        }

        // Auto-detect minggu dari tanggal kasbon
        $weekNumber = Employee::detectWeekNumber($kasbonDate);

        // Cek apakah payroll minggu ini sudah paid
        if ($employee->isPayrollPaid($month, $year, $weekNumber)) {
            return response()->json([
                'success' => false,
                'payroll_paid' => true,
                'week_number' => $weekNumber,
                'message' => sprintf(
                    'Payroll minggu ke-%d sudah dibayar (status: paid). Kasbon hanya bisa dilakukan untuk minggu yang belum dibayar.',
                    $weekNumber
                )
            ], 400);
        }

        $daysWorked = $employee->getAttendanceUpToDate($month, $year, $weekNumber, $kasbonDate);
        $maxKasbon = $employee->getMaxKasbonUpToDate($month, $year, $weekNumber, $kasbonDate);
        $dailyWage = $employee->daily_wage ?? $employee->base_salary;

        // Jika belum ada kehadiran sama sekali, return dengan max kasbon 0
        if ($daysWorked == 0) {
            return response()->json([
                'success' => false,
                'employee_name' => $employee->name,
                'days_worked' => 0,
                'max_kasbon' => 0,
                'week_number' => $weekNumber,
                'no_attendance' => true,
                'message' => sprintf(
                    '%s belum memiliki catatan kehadiran di minggu ke-%d. Kasbon hanya bisa dilakukan setelah karyawan hadir bekerja.',
                    $employee->name,
                    $weekNumber
                )
            ], 400);
        }

        return response()->json([
            'success' => true,
            'employee_name' => $employee->name,
            'days_worked' => $daysWorked,
            'daily_wage' => $dailyWage,
            'max_kasbon' => $maxKasbon,
            'week_number' => $weekNumber,
            'payroll_paid' => false,
            'no_attendance' => false,
            'max_kasbon_formatted' => 'Rp ' . number_format($maxKasbon, 0, ',', '.'),
            'message' => sprintf(
                '%s sudah masuk %d hari pada minggu ke-%d sampai %s. Maksimal kasbon: Rp %s',
                $employee->name,
                $daysWorked,
                $weekNumber,
                \Carbon\Carbon::parse($kasbonDate)->format('d/m/Y'),
                number_format($maxKasbon, 0, ',', '.')
            )
        ]);
    }
}

