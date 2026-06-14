<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Kasbon;
use App\Models\Sdm\Employee;
use App\Models\Sdm\Payroll;
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
            'amount' => InputNormalizer::normalizeCurrency($request->input('amount')),
        ]);

        $validated = $request->validate([
            'kasbon_type' => 'required|in:personal,team',
            'employee_id' => 'required_if:kasbon_type,personal|nullable|exists:employees,employee_code',
            'division' => 'required_if:kasbon_type,team|nullable|string|max:100',
            'employee_details' => 'nullable|array',
            'employee_details.*' => 'exists:employees,employee_code',
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
            'employee_details.required' => 'Karyawan harus dipilih untuk kasbon tim',
            'amount.required' => 'Jumlah kasbon harus diisi',
            'amount.min' => 'Jumlah kasbon minimal Rp 1.000',
            'kasbon_date.required' => 'Tanggal kasbon harus diisi',
            'period_month.required' => 'Bulan periode harus diisi',
            'period_year.required' => 'Tahun periode harus diisi',
        ]);

        // Auto-detect minggu dari tanggal kasbon
        $weekNumber = Employee::detectWeekNumber($validated['kasbon_date']);
        $validated['week_number'] = $weekNumber;

        $month = $validated['period_month'];
        $year = $validated['period_year'];
        $kasbonDate = $validated['kasbon_date'];

        // ==========================================
        // VALIDASI TERPADU: kumpulkan semua error
        // ==========================================
        $attendanceErrors = [];     // Karyawan tanpa kehadiran
        $allocationErrors = [];     // Karyawan overload

        if ($validated['kasbon_type'] === 'personal') {
            $employee = Employee::findOrFail($validated['employee_id']);

            // Cek apakah payroll sudah paid (langsung return)
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

            $daysWorked = $employee->getAttendanceUpToDate($month, $year, $weekNumber, $kasbonDate);

            // Attendance check
            if ($daysWorked == 0) {
                $attendanceErrors[] = $employee;
            }

            // Double allocation check: total existing + new <= max
            if ($daysWorked > 0) {
                $existingTotal = $this->getTotalKasbonBurden($employee->employee_code, $month, $year, $weekNumber);
                $totalWithNew = $existingTotal + $validated['amount'];
                $maxKasbon = $employee->getMaxKasbonUpToDate($month, $year, $weekNumber, $kasbonDate);

                if ($totalWithNew > $maxKasbon) {
                    $allocationErrors[] = [
                        'name' => $employee->name,
                        'wage' => $maxKasbon,
                        'totalKasbon' => $totalWithNew,
                    ];
                }
            }
        } elseif ($validated['kasbon_type'] === 'team') {
            if (empty($validated['employee_details']) || count($validated['employee_details']) === 0) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Minimal 1 karyawan harus dipilih untuk kasbon tim.');
            }

            $perPerson = intdiv($validated['amount'], count($validated['employee_details']));
            $teamEmployees = Employee::whereIn('employee_code', $validated['employee_details'])->get();

            foreach ($teamEmployees as $emp) {
                $daysWorked = $emp->getAttendanceUpToDate($month, $year, $weekNumber, $kasbonDate);

                // Attendance check
                if ($daysWorked == 0) {
                    $attendanceErrors[] = $emp;
                    continue;
                }

                // Double allocation check
                $existingTotal = $this->getTotalKasbonBurden($emp->employee_code, $month, $year, $weekNumber);
                $totalWithNew = $existingTotal + $perPerson;
                $maxKasbon = $emp->getMaxKasbonUpToDate($month, $year, $weekNumber, $kasbonDate);

                if ($totalWithNew > $maxKasbon) {
                    $allocationErrors[] = [
                        'name' => $emp->name,
                        'wage' => $maxKasbon,
                        'totalKasbon' => $totalWithNew,
                    ];
                }
            }
        }

        // Jika ada error validasi, tampilkan semua sekaligus
        $errorMessages = [];
        if (!empty($attendanceErrors)) {
            $errorMessages[] = $this->buildAttendanceErrorList($attendanceErrors, $weekNumber);
        }
        if (!empty($allocationErrors)) {
            $errorMessages[] = $this->buildAllocationErrorList($allocationErrors);
        }
        if (!empty($errorMessages)) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode('<br><br>', $errorMessages));
        }

        // Generate kasbon code
        $validated['kasbon_code'] = Kasbon::generateKasbonCode();
        $validated['status'] = 'pending';

        // Jika kasbon team, set employee_id ke null, simpan employee_details, dan set remaining_amount
        if ($validated['kasbon_type'] === 'team') {
            $validated['employee_id'] = null;
            $validated['employee_details'] = $request->input('employee_details', []);
            $validated['remaining_amount'] = $validated['amount'];
        } else {
            // Jika kasbon personal, set division, employee_details, dan remaining_amount ke null
            $validated['division'] = null;
            $validated['employee_details'] = null;
            $validated['remaining_amount'] = null;
        }

        // Cek payroll status sebelum menyimpan
        if ($validated['kasbon_type'] === 'personal') {
            $lockedName = $this->getLockedPayrollEmployee(
                [$validated['employee_id']],
                $validated['period_month'],
                $validated['period_year'],
                $weekNumber
            );
            if ($lockedName) {
                return redirect()->back()->withInput()
                    ->with('error', 'Tidak dapat menyimpan kasbon karena payroll ' . $lockedName . ' periode ini sudah diproses.');
            }
        } elseif ($validated['kasbon_type'] === 'team') {
            $details = $request->input('employee_details', []);
            $lockedName = $this->getLockedPayrollEmployee(
                $details,
                $validated['period_month'],
                $validated['period_year'],
                $weekNumber
            );
            if ($lockedName) {
                return redirect()->back()->withInput()
                    ->with('error', 'Tidak dapat menyimpan kasbon karena payroll ' . $lockedName . ' periode ini sudah diproses.');
            }
        }

        $kasbon = Kasbon::create($validated);

        // Auto-update draft payroll setelah kasbon tersimpan
        if ($validated['kasbon_type'] === 'personal') {
            $this->adjustPayrollKasbon(
                $validated['employee_id'],
                $validated['period_month'],
                $validated['period_year'],
                $weekNumber,
                $validated['amount']
            );
        } elseif ($validated['kasbon_type'] === 'team') {
            $details = $request->input('employee_details', []);
            $count = count($details);
            if ($count > 0) {
                $perPerson = intdiv($validated['amount'], $count);
                foreach ($details as $empCode) {
                    $this->adjustPayrollKasbon(
                        $empCode,
                        $validated['period_month'],
                        $validated['period_year'],
                        $weekNumber,
                        $perPerson
                    );
                }
            }
        }

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
            'employee_details' => 'nullable|array',
            'employee_details.*' => 'exists:employees,employee_code',
            'amount' => 'required|integer|min:1000',
            'kasbon_date' => 'required|date',
            'week_number' => 'nullable|integer|min:1|max:4',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2020',
            'notes' => 'nullable|string|max:500',
        ]);

        // Simpan data lama untuk perbandingan
        $oldType = $kasbon->kasbon_type;
        $oldAmount = $kasbon->amount;
        $oldEmployeeId = $kasbon->employee_id;
        $oldDetails = $kasbon->employee_details ?? [];

        // Auto-detect week_number jika tidak dikirim dari form
        if (empty($validated['week_number'])) {
            $validated['week_number'] = Employee::detectWeekNumber($validated['kasbon_date']);
        }
        $weekNumber = $validated['week_number'];
        $month = $validated['period_month'];
        $year = $validated['period_year'];
        $kasbonDate = $validated['kasbon_date'];

        // ==========================================
        // VALIDASI TERPADU: kumpulkan semua error
        // ==========================================
        $attendanceErrors = [];
        $allocationErrors = [];

        if ($validated['kasbon_type'] === 'personal') {
            $employee = Employee::findOrFail($validated['employee_id']);
            $daysWorked = $employee->getAttendanceUpToDate($month, $year, $weekNumber, $kasbonDate);

            if ($daysWorked == 0) {
                $attendanceErrors[] = $employee;
            }

            if ($daysWorked > 0) {
                $existingTotal = $this->getTotalKasbonBurden(
                    $employee->employee_code, $month, $year, $weekNumber, $kasbon->kasbon_code
                );
                $totalWithNew = $existingTotal + $validated['amount'];
                $maxKasbon = $employee->getMaxKasbonUpToDate($month, $year, $weekNumber, $kasbonDate);

                if ($totalWithNew > $maxKasbon) {
                    $allocationErrors[] = [
                        'name' => $employee->name,
                        'wage' => $maxKasbon,
                        'totalKasbon' => $totalWithNew,
                    ];
                }
            }
        } elseif ($validated['kasbon_type'] === 'team') {
            $newDetails = $request->input('employee_details', []);
            if (!empty($newDetails)) {
                $perPerson = intdiv($validated['amount'], count($newDetails));
                $teamEmployees = Employee::whereIn('employee_code', $newDetails)->get();

                foreach ($teamEmployees as $emp) {
                    $daysWorked = $emp->getAttendanceUpToDate($month, $year, $weekNumber, $kasbonDate);

                    if ($daysWorked == 0) {
                        $attendanceErrors[] = $emp;
                        continue;
                    }

                    $existingTotal = $this->getTotalKasbonBurden(
                        $emp->employee_code, $month, $year, $weekNumber, $kasbon->kasbon_code
                    );
                    $totalWithNew = $existingTotal + $perPerson;
                    $maxKasbon = $emp->getMaxKasbonUpToDate($month, $year, $weekNumber, $kasbonDate);

                    if ($totalWithNew > $maxKasbon) {
                        $allocationErrors[] = [
                            'name' => $emp->name,
                            'wage' => $maxKasbon,
                            'totalKasbon' => $totalWithNew,
                        ];
                    }
                }
            }
        }

        // Jika ada error validasi, tampilkan semua sekaligus
        $errorMessages = [];
        if (!empty($attendanceErrors)) {
            $errorMessages[] = $this->buildAttendanceErrorList($attendanceErrors, $weekNumber);
        }
        if (!empty($allocationErrors)) {
            $errorMessages[] = $this->buildAllocationErrorList($allocationErrors);
        }
        if (!empty($errorMessages)) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode('<br><br>', $errorMessages));
        }

        // Kumpulkan semua karyawan yang terdampak (lama + baru)
        $affectedEmployees = [];
        if ($oldType === 'personal' && $oldEmployeeId) {
            $affectedEmployees[] = $oldEmployeeId;
        } elseif ($oldType === 'team') {
            $affectedEmployees = array_merge($affectedEmployees, $oldDetails);
        }
        if ($validated['kasbon_type'] === 'personal' && !empty($validated['employee_id'])) {
            $affectedEmployees[] = $validated['employee_id'];
        } elseif ($validated['kasbon_type'] === 'team') {
            $newDetails = $request->input('employee_details', []);
            $affectedEmployees = array_merge($affectedEmployees, $newDetails);
        }
        $affectedEmployees = array_unique($affectedEmployees);

        // Cek payroll status sebelum menyimpan
        $lockedName = $this->getLockedPayrollEmployee(
            $affectedEmployees,
            $validated['period_month'],
            $validated['period_year'],
            $validated['week_number']
        );
        if ($lockedName) {
            return redirect()->back()->withInput()
                ->with('error', 'Tidak dapat mengupdate kasbon karena payroll ' . $lockedName . ' periode ini sudah diproses.');
        }

        // Jika kasbon team, set employee_id ke null dan simpan employee_details
        if ($validated['kasbon_type'] === 'team') {
            $validated['employee_id'] = null;
            $validated['employee_details'] = $request->input('employee_details', []);
        } else {
            // Jika kasbon personal, set division dan employee_details ke null
            $validated['division'] = null;
            $validated['employee_details'] = null;
        }

        $kasbon->update($validated);

        // Reverse dampak lama
        if ($oldType === 'personal' && $oldEmployeeId) {
            $this->adjustPayrollKasbon(
                $oldEmployeeId,
                $validated['period_month'],
                $validated['period_year'],
                $validated['week_number'],
                -$oldAmount
            );
        } elseif ($oldType === 'team' && !empty($oldDetails)) {
            $oldPerPerson = intdiv($oldAmount, count($oldDetails));
            foreach ($oldDetails as $empCode) {
                $this->adjustPayrollKasbon(
                    $empCode,
                    $validated['period_month'],
                    $validated['period_year'],
                    $validated['week_number'],
                    -$oldPerPerson
                );
            }
        }

        // Terapkan dampak baru
        if ($validated['kasbon_type'] === 'personal') {
            $this->adjustPayrollKasbon(
                $validated['employee_id'],
                $validated['period_month'],
                $validated['period_year'],
                $validated['week_number'],
                $validated['amount']
            );
        } elseif ($validated['kasbon_type'] === 'team') {
            $newDetails = $request->input('employee_details', []);
            $count = count($newDetails);
            if ($count > 0) {
                $newPerPerson = intdiv($validated['amount'], $count);
                foreach ($newDetails as $empCode) {
                    $this->adjustPayrollKasbon(
                        $empCode,
                        $validated['period_month'],
                        $validated['period_year'],
                        $validated['week_number'],
                        $newPerPerson
                    );
                }
            }
        }

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

        $payrollUpdated = 0;

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

            // Reverse payroll impact sebelum menghapus
            if ($kasbon->kasbon_type === 'personal' && $kasbon->employee_id) {
                $this->adjustPayrollKasbon(
                    $kasbon->employee_id,
                    $kasbon->period_month,
                    $kasbon->period_year,
                    $kasbon->week_number,
                    -$kasbon->amount
                );
                $payrollUpdated++;
            } elseif ($kasbon->kasbon_type === 'team') {
                $details = $kasbon->employee_details ?? [];
                $count = count($details);
                if ($count > 0) {
                    $perPerson = intdiv($kasbon->amount, $count);
                    foreach ($details as $empCode) {
                        $this->adjustPayrollKasbon(
                            $empCode,
                            $kasbon->period_month,
                            $kasbon->period_year,
                            $kasbon->week_number,
                            -$perPerson
                        );
                    }
                    $payrollUpdated++;
                }
            }

            $kasbon->delete();
            $deleted++;
        }

        $msg = "Berhasil menghapus {$deleted} kasbon.";
        if ($payrollUpdated > 0) {
            $msg .= " {$payrollUpdated} payroll draft telah diperbarui.";
        }
        if ($skipped > 0) {
            $msg .= " {$skipped} kasbon tidak dapat dihapus karena sudah dipotong.";
        }
        if ($deleted > 0) {
            return redirect()->back()->with('success', $msg);
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

    /**
     * Adjust payroll kasbon_deduction and net_salary for a draft payroll.
     * Only updates if payroll exists and status is 'draft'.
     */
    private function adjustPayrollKasbon($employeeCode, $month, $year, $weekNumber, $delta)
    {
        $payroll = Payroll::where('employee_id', $employeeCode)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->where('week_number', $weekNumber)
            ->first();

        if (!$payroll || $payroll->status !== 'draft') return;

        $payroll->kasbon_deduction = max(0, ($payroll->kasbon_deduction ?? 0) + $delta);
        $payroll->net_salary = max(0, ($payroll->net_salary ?? 0) - $delta);
        $payroll->save();
    }

    /**
     * Check if ANY affected employee has a non-draft payroll for the period.
     * Returns the first locked employee name found, or null if all clear.
     */
    private function getLockedPayrollEmployee($employeeCodes, $month, $year, $weekNumber)
    {
        foreach ($employeeCodes as $empCode) {
            $payroll = Payroll::where('employee_id', $empCode)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->where('week_number', $weekNumber)
                ->first();

            if ($payroll && $payroll->status !== 'draft') {
                $emp = Employee::find($empCode);
                return $emp->name ?? $empCode;
            }
        }
        return null;
    }

    /**
     * Hitung total beban kasbon yang akan dipotong dari seorang karyawan pada periode tertentu.
     * Mencakup kasbon personal + bagian dari kasbon team.
     */
    private function getTotalKasbonBurden($employeeCode, $month, $year, $weekNumber, $excludeKasbonCode = null)
    {
        $total = Kasbon::getTotalForEmployee($employeeCode, $month, $year, $weekNumber);

        $teamQuery = Kasbon::where('kasbon_type', 'team')
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->where('week_number', $weekNumber)
            ->where('status', 'pending')
            ->where('remaining_amount', '>', 0);

        if ($excludeKasbonCode) {
            $teamQuery->where('kasbon_code', '!=', $excludeKasbonCode);
        }

        foreach ($teamQuery->get() as $tk) {
            $details = $tk->employee_details ?? [];
            if (in_array($employeeCode, $details)) {
                $count = count($details);
                if ($count > 0) {
                    $total += intdiv($tk->amount, $count);
                }
            }
        }

        return $total;
    }

    /**
     * Build HTML error list for attendance validation failures.
     */
    private function buildAttendanceErrorList($employees, $weekNumber)
    {
        $items = '';
        foreach ($employees as $emp) {
            $items .= '• ' . $emp['name'] . '<br>';
        }
        return 'Kasbon tidak dapat diproses karena karyawan berikut belum memiliki catatan kehadiran pada minggu ke-' . $weekNumber . ':<br><br>' .
               $items .
               '<br>Kasbon hanya dapat dilakukan setelah karyawan memiliki catatan kehadiran.';
    }

    /**
     * Build HTML error list for double allocation validation failures.
     */
    private function buildAllocationErrorList($overLimitEmployees)
    {
        $items = '';
        foreach ($overLimitEmployees as $data) {
            $items .= '• ' . $data['name'] . '<br>' .
                      '&nbsp;&nbsp;Gaji: Rp' . number_format($data['wage'], 0, ',', '.') . '<br>' .
                      '&nbsp;&nbsp;Total Kasbon: Rp' . number_format($data['totalKasbon'], 0, ',', '.') . '<br><br>';
        }
        return 'Kasbon tidak dapat disimpan karena total kasbon yang akan dipotong melebihi gaji karyawan yang tersedia.<br><br>' .
               'Karyawan berikut melebihi batas potongan:<br><br>' .
               $items;
    }
}

