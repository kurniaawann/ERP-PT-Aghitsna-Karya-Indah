<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Kasbon;
use App\Models\Sdm\Employee;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\InputNormalizer;

class KasbonController extends Controller
{


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
                return $query->whereMonth('period_start_date', $month);
            })
            ->when($year, function ($query, $year) {
                return $query->whereYear('period_start_date', $year);
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
            'amount' => InputNormalizer::normalizeCurrency($request->input('amount')),
        ]);

        $validated = $request->validate([
            'kasbon_type' => 'required|in:personal,team',
            'employee_id' => 'required_if:kasbon_type,personal|nullable|exists:employees,employee_code',
            'division' => 'required_if:kasbon_type,team|nullable|string|max:100',
            'amount' => 'required|integer|min:1000',
            'kasbon_date' => 'required|date',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2020',
            'period_start_date' => 'required|date',
            'period_end_date' => 'required|date|after_or_equal:period_start_date',
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
            'period_start_date.required' => 'Tanggal mulai periode harus diisi',
            'period_end_date.required' => 'Tanggal akhir periode harus diisi',
            'period_end_date.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai',
        ]);

        // Auto-detect week_number dari period_start_date
        $validated['week_number'] = Carbon::parse($validated['period_start_date'])->weekOfMonth;

        // Validasi kasbon personal berdasarkan kehadiran dan status payroll
        if ($validated['kasbon_type'] === 'personal') {
            $employee = Employee::findOrFail($validated['employee_id']);
            $periodStartDate = Carbon::parse($validated['period_start_date']);
            $kasbonDate = $validated['kasbon_date'];

            // Cek apakah payroll minggu ini sudah paid
            if ($employee->isPayrollPaidByStartDate($periodStartDate)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', sprintf(
                        'Tidak dapat melakukan kasbon! Payroll periode %s sudah dibayar (status: paid). Kasbon hanya bisa dilakukan untuk minggu yang belum dibayar.',
                        $periodStartDate->format('d M Y')
                    ));
            }

            // Hitung maksimal kasbon berdasarkan kehadiran
            $daysWorked = $employee->getAttendanceUpToDate($periodStartDate, $kasbonDate);
            $maxKasbon = $employee->getMaxKasbonUpToDate($periodStartDate, $kasbonDate);

            // VALIDASI PENTING: Jika belum ada kehadiran sama sekali, tidak bisa kasbon!
            if ($daysWorked == 0) {
                $periodEndDate = Carbon::parse($validated['period_end_date']);
                return redirect()->back()
                    ->withInput()
                    ->with('error', sprintf(
                        'Tidak dapat melakukan kasbon! %s belum memiliki catatan kehadiran periode %s - %s. Kasbon hanya bisa dilakukan setelah karyawan hadir bekerja.',
                        $employee->name,
                        $periodStartDate->format('d M'),
                        $periodEndDate->format('d M Y')
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
            'amount' => InputNormalizer::normalizeCurrency($request->input('amount')),
        ]);

        $validated = $request->validate([
            'kasbon_type' => 'required|in:personal,team',
            'employee_id' => 'required_if:kasbon_type,personal|nullable|exists:employees,employee_code',
            'division' => 'required_if:kasbon_type,team|nullable|string|max:100',
            'amount' => 'required|integer|min:1000',
            'kasbon_date' => 'required|date',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2020',
            'period_start_date' => 'required|date',
            'period_end_date' => 'required|date|after_or_equal:period_start_date',
            'notes' => 'nullable|string|max:500',
        ]);

        // Validasi kasbon personal berdasarkan kehadiran
        if ($validated['kasbon_type'] === 'personal' && !empty($validated['period_start_date'])) {
            $employee = Employee::findOrFail($validated['employee_id']);
            $periodStartDate = Carbon::parse($validated['period_start_date']);
            $kasbonDate = $validated['kasbon_date'];

            $daysWorked = $employee->getAttendanceUpToDate($periodStartDate, $kasbonDate);
            $maxKasbon = $employee->getMaxKasbonUpToDate($periodStartDate, $kasbonDate);
            $dailyWage = $employee->daily_wage ?? $employee->base_salary;

            if (!$employee->canTakeKasbon($validated['amount'], $periodStartDate, $kasbonDate)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', sprintf(
                        'Kasbon melebihi batas maksimal! %s hanya masuk %d hari pada periode ini sampai tanggal %s. Maksimal kasbon: Rp %s (Rp %s × %d hari)',
                        $employee->name,
                        $daysWorked,
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
        $periodStartDate = $request->period_start_date;
        $employeeId = $request->employee_id;

        if ($periodStartDate) {
            if ($employeeId) {
                $personalKasbon = Kasbon::getTotalForEmployee($employeeId, $periodStartDate);
            } else {
                $personalKasbon = 0;
            }

            $teamKasbon = Kasbon::getTotalTeamKasbon($periodStartDate);
        } else {
            $personalKasbon = 0;
            $teamKasbon = 0;
        }

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
        $periodStartDate = $request->input('period_start_date');
        $kasbonDate = $request->input('kasbon_date');

        if (!$employeeId || !$periodStartDate || !$kasbonDate) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tidak lengkap. Pastikan karyawan, periode, dan tanggal kasbon sudah dipilih.'
            ], 400);
        }

        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan'
            ], 404);
        }

        $periodStartDateCarbon = Carbon::parse($periodStartDate);

        // Cek apakah payroll minggu ini sudah paid
        if ($employee->isPayrollPaidByStartDate($periodStartDateCarbon)) {
            return response()->json([
                'success' => false,
                'payroll_paid' => true,
                'message' => sprintf(
                    'Payroll periode %s sudah dibayar (status: paid). Kasbon hanya bisa dilakukan untuk minggu yang belum dibayar.',
                    $periodStartDateCarbon->format('d M Y')
                )
            ], 400);
        }

        $daysWorked = $employee->getAttendanceUpToDate($periodStartDateCarbon, $kasbonDate);
        $maxKasbon = $employee->getMaxKasbonUpToDate($periodStartDateCarbon, $kasbonDate);
        $dailyWage = $employee->daily_wage ?? $employee->base_salary;

        // Jika belum ada kehadiran sama sekali, return dengan max kasbon 0
        if ($daysWorked == 0) {
            return response()->json([
                'success' => false,
                'employee_name' => $employee->name,
                'days_worked' => 0,
                'max_kasbon' => 0,
                'no_attendance' => true,
                'message' => sprintf(
                    '%s belum memiliki catatan kehadiran pada periode ini. Kasbon hanya bisa dilakukan setelah karyawan hadir bekerja.',
                    $employee->name
                )
            ], 400);
        }

        return response()->json([
            'success' => true,
            'employee_name' => $employee->name,
            'days_worked' => $daysWorked,
            'daily_wage' => $dailyWage,
            'max_kasbon' => $maxKasbon,
            'payroll_paid' => false,
            'no_attendance' => false,
            'max_kasbon_formatted' => 'Rp ' . number_format($maxKasbon, 0, ',', '.'),
            'message' => sprintf(
                '%s sudah masuk %d hari sampai %s. Maksimal kasbon: Rp %s',
                $employee->name,
                $daysWorked,
                \Carbon\Carbon::parse($kasbonDate)->format('d/m/Y'),
                number_format($maxKasbon, 0, ',', '.')
            )
        ]);
    }
}
