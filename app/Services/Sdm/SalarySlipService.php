<?php

namespace App\Services\Sdm;

use App\Models\Sdm\Employee;
use App\Models\Sdm\Executive;
use App\Models\Sdm\Kasbon;
use App\Models\Sdm\SalarySlip;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk mengelola Slip Gaji Karyawan Bulanan.
 *
 * Alur: admin mengisi rekap absensi 1 bulan per karyawan (matriks
 * attendance_detail). Hari Minggu dan hari libur yang dipilih saat generate
 * otomatis ditandai "L" (Libur). Sistem menghitung:
 *   Penerimaan = gaji pokok + (transport x hadir) + (makan x hadir)
 *   Potongan   = BPJS Kesehatan 1% gaji pokok + JHT 2% UMP + JPN 1% UMP
 *                + PPh 21 (manual) + kasbon pending
 *   THP        = Penerimaan - Potongan
 * Iuran perusahaan (BPJS 4%, JHT 3,7%, JKK 0,24%, JKM 0,30% x UMP) disimpan
 * sebagai informasi slip. Slip disimpan sebagai snapshot (bisa diedit
 * sebelum paid dan dicetak ulang kapan saja).
 */
class SalarySlipService
{
    /**
     * Mendapatkan daftar slip gaji untuk halaman index.
     */
    public function getSlipsForIndex(?string $search, ?int $month, ?int $year): Builder
    {
        return SalarySlip::with('employee')
            ->where('created_by', auth()->id())
            ->when($month, fn ($query) => $query->where('period_month', $month))
            ->when($year, fn ($query) => $query->where('period_year', $year))
            ->when($search, function ($query, $search) {
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('id');
    }

    /**
     * Karyawan bulanan yang berhak dibuatkan slip pada periode tertentu
     * (belum memiliki slip untuk periode tersebut).
     *
     * @return \Illuminate\Support\Collection<int, Employee>
     */
    public function getEligibleEmployees(int $periodYear, int $periodMonth)
    {
        $existing = SalarySlip::where('created_by', auth()->id())
            ->where('period_year', $periodYear)
            ->where('period_month', $periodMonth)
            ->pluck('employee_code')
            ->all();

        return Employee::where('created_by', auth()->id())
            ->where('employment_type', 'bulanan')
            ->whereNotIn('employee_code', $existing)
            ->orderBy('name')
            ->get();
    }

    /**
     * Membuat slip gaji draft untuk karyawan bulanan terpilih pada periode.
     *
     * Setiap slip dibuat dengan matriks absensi default: hari Minggu dan
     * tanggal pada $holidayDates ditandai "L" (Libur), sisanya "H" (Hadir).
     * Admin dapat mengubah status hari tertentu di modal absensi slip.
     *
     * @param  array<int, string>  $employeeCodes
     * @param  array<string, mixed>  $signatureIds  Mapping peran => ID petinggi
     * @param  array<int, string>  $holidayDates  Tanggal libur "Y-m-d" pada periode
     * @return array{success: bool, message: string, count: int}
     */
    public function generateSlips(array $employeeCodes, int $periodYear, int $periodMonth, array $signatureIds = [], array $holidayDates = []): array
    {
        $employees = Employee::where('created_by', auth()->id())
            ->where('employment_type', 'bulanan')
            ->whereIn('employee_code', $employeeCodes)
            ->get();

        if ($employees->isEmpty()) {
            return ['success' => false, 'message' => 'Tidak ada karyawan bulanan yang dipilih.', 'count' => 0];
        }

        $existing = SalarySlip::where('created_by', auth()->id())
            ->where('period_year', $periodYear)
            ->where('period_month', $periodMonth)
            ->pluck('employee_code')
            ->all();

        $signatures = $this->resolveSignatureSnapshot($signatureIds);
        $defaultAttendance = $this->buildDefaultAttendance($periodYear, $periodMonth, $holidayDates);
        $created = 0;
        $skipped = 0;

        foreach ($employees as $employee) {
            if (in_array($employee->employee_code, $existing, true)) {
                $skipped++;

                continue;
            }

            $this->createSlip($employee, $periodYear, $periodMonth, $defaultAttendance, $signatures);
            $created++;
        }

        $message = "Berhasil membuat {$created} slip gaji.";
        if ($skipped > 0) {
            $message .= " {$skipped} karyawan dilewati karena sudah memiliki slip periode ini.";
        }

        return ['success' => true, 'message' => $message, 'count' => $created];
    }

    /**
     * Membuat satu slip draft lalu menghitung ulang nilainya.
     */
    private function createSlip(Employee $employee, int $periodYear, int $periodMonth, array $attendance, array $signatures): SalarySlip
    {
        $slip = SalarySlip::create([
            'employee_code' => $employee->employee_code,
            'period_year' => $periodYear,
            'period_month' => $periodMonth,
            'base_salary' => (int) ($employee->base_salary ?? 0),
            'transport_rate' => (int) ($employee->transport_rate ?? 0),
            'meal_rate' => (int) ($employee->meal_rate ?? 0),
            'ump' => (int) ($employee->ump ?? 0),
            'attendance_detail' => $attendance,
            'signatures' => $signatures ?: null,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        $this->recalculate($slip);

        return $slip;
    }

    /**
     * Matriks absensi default untuk satu bulan: Minggu dan hari libur yang
     * dipilih ditandai "L" (Libur), hari lain "H" (Hadir).
     *
     * @param  array<int, string>  $holidayDates  Tanggal libur "Y-m-d" pada periode
     * @return array<int, string>
     */
    private function buildDefaultAttendance(int $periodYear, int $periodMonth, array $holidayDates = []): array
    {
        $daysInMonth = Carbon::createFromDate($periodYear, $periodMonth, 1)->daysInMonth;

        $holidaySet = array_flip(array_values(array_filter(
            $holidayDates,
            fn ($date) => is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
        )));

        $attendance = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate($periodYear, $periodMonth, $day);
            $isSunday = $date->dayOfWeek === Carbon::SUNDAY;
            $isHoliday = isset($holidaySet[$date->format('Y-m-d')]);

            $attendance[$day] = ($isSunday || $isHoliday)
                ? SalarySlip::DAY_LIBUR
                : SalarySlip::DAY_PRESENT;
        }

        return $attendance;
    }

    /**
     * Menyimpan matriks absensi (dan PPh 21 manual opsional) lalu menghitung ulang.
     *
     * @param  array<int, string>  $attendanceDetail  Kunci 1..hari-dalam-bulan
     * @param  int|null  $pph21  PPh 21 manual (dibayar karyawan)
     */
    public function updateAttendance(SalarySlip $slip, array $attendanceDetail, ?int $pph21 = null): bool
    {
        if ($slip->isPaid()) {
            throw new \DomainException('Slip gaji yang sudah dibayar tidak dapat diubah. Hapus slip paid terlebih dahulu untuk mengubah data periode ini.');
        }

        if ($pph21 !== null) {
            $slip->pph21 = max(0, (int) $pph21);
        }

        $daysInMonth = $slip->days_in_month;
        $normalized = [];
        $validStatuses = [
            SalarySlip::DAY_PRESENT,
            SalarySlip::DAY_PERMISSION,
            SalarySlip::DAY_SICK,
            SalarySlip::DAY_LEAVE,
            SalarySlip::DAY_ABSENT,
            SalarySlip::DAY_LIBUR,
        ];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $status = $attendanceDetail[$day] ?? null;

            if (in_array($status, $validStatuses, true)) {
                $normalized[$day] = $status;
            } else {
                $normalized[$day] = SalarySlip::DAY_PRESENT;
            }
        }

        $slip->attendance_detail = $normalized;

        return $this->recalculate($slip);
    }

    /**
     * Memperbarui catatan slip (draft saja).
     */
    public function updateNotes(SalarySlip $slip, ?string $notes): bool
    {
        if ($slip->isPaid()) {
            throw new \DomainException('Slip gaji yang sudah dibayar tidak dapat diubah.');
        }

        $slip->notes = $notes ?: null;

        return $slip->save();
    }

    /**
     * Menghitung ulang semua angka slip dari matriks absensi yang tersimpan.
     *
     * Rumus:
     * - libur_days = jumlah status "L" (Minggu + hari libur terpilih)
     * - work_days  = hari kalender - libur_days
     * - transport/makan = tarif x jumlah hadir (H)
     * - total_income = gaji pokok + transport + makan
     * - Potongan = BPJS Kes 1% gaji pokok + JHT 2% UMP + JPN 1% UMP
     *              + PPh 21 (manual) + kasbon pending
     * - THP (net_salary) = total_income - total_deduction
     */
    public function recalculate(SalarySlip $slip): bool
    {
        $daysInMonth = $slip->days_in_month;
        $matrix = is_array($slip->attendance_detail) ? $slip->attendance_detail : [];

        $present = 0;
        $permission = 0;
        $sick = 0;
        $leave = 0;
        $absent = 0;
        $libur = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            switch ($matrix[$day] ?? SalarySlip::DAY_PRESENT) {
                case SalarySlip::DAY_LIBUR:
                    $libur++;
                    break;
                case SalarySlip::DAY_PERMISSION:
                    $permission++;
                    break;
                case SalarySlip::DAY_SICK:
                    $sick++;
                    break;
                case SalarySlip::DAY_LEAVE:
                    $leave++;
                    break;
                case SalarySlip::DAY_ABSENT:
                    $absent++;
                    break;
                default:
                    $present++;
                    break;
            }
        }

        $baseSalary = (int) $slip->base_salary;
        $transportRate = (int) $slip->transport_rate;
        $mealRate = (int) $slip->meal_rate;
        $ump = (int) $slip->ump;

        // Penerimaan
        $transportTotal = $transportRate * $present;
        $mealTotal = $mealRate * $present;
        $totalIncome = $baseSalary + $transportTotal + $mealTotal;

        // Potongan (dibayar karyawan)
        $bpjsKesehatanEmployee = (int) round($baseSalary * 0.01);
        $jhtEmployee = (int) round($ump * 0.02);
        $jpnEmployee = (int) round($ump * 0.01);
        $pph21 = max(0, (int) ($slip->pph21 ?? 0));
        $kasbonDeduction = $this->getPendingKasbonTotal($slip);

        // Iuran dibayar perusahaan (informasi pada slip)
        $bpjsKesehatanCompany = (int) round($ump * 0.04);
        $jhtCompany = (int) round($ump * 0.037);
        $jkkCompany = (int) round($ump * 0.0024);
        $jkmCompany = (int) round($ump * 0.003);

        $totalDeduction = $bpjsKesehatanEmployee + $jhtEmployee + $jpnEmployee + $pph21 + $kasbonDeduction;

        $slip->work_days = max(0, $daysInMonth - $libur);
        $slip->present_days = $present;
        $slip->permission_days = $permission;
        $slip->sick_days = $sick;
        $slip->leave_days = $leave;
        $slip->absent_days = $absent;
        $slip->libur_days = $libur;
        $slip->daily_rate = 0;
        $slip->salary_deduction = 0;
        $slip->transport_total = $transportTotal;
        $slip->meal_total = $mealTotal;
        $slip->total_income = $totalIncome;
        $slip->bpjs_kesehatan_employee = $bpjsKesehatanEmployee;
        $slip->jht_employee = $jhtEmployee;
        $slip->jpn_employee = $jpnEmployee;
        $slip->pph21 = $pph21;
        $slip->kasbon_deduction = $kasbonDeduction;
        $slip->total_deduction = $totalDeduction;
        $slip->bpjs_kesehatan_company = $bpjsKesehatanCompany;
        $slip->jht_company = $jhtCompany;
        $slip->jkk_company = $jkkCompany;
        $slip->jkm_company = $jkmCompany;
        $slip->net_salary = max(0, $totalIncome - $totalDeduction);

        return $slip->save();
    }

    /**
     * Total kasbon personal yang masih pending (belum dipotong payroll
     * dan belum lunas) milik karyawan pada periode slip.
     */
    public function getPendingKasbonTotal(SalarySlip $slip): int
    {
        return (int) Kasbon::personal()
            ->pending()
            ->notPaid()
            ->forPeriod($slip->period_month, $slip->period_year)
            ->where('employee_id', $slip->employee_code)
            ->where('created_by', auth()->id())
            ->sum('remaining_amount');
    }

    /**
     * Membayar beberapa slip gaji sekaligus.
     *
     * @param  array<int, int>  $ids
     */
    public function bulkPay(array $ids, string $paymentDate): array
    {
        if (empty($ids)) {
            return ['success' => false, 'message' => 'Tidak ada slip yang dipilih.', 'count' => 0];
        }

        try {
            $count = DB::transaction(function () use ($ids, $paymentDate) {
                $slips = SalarySlip::where('created_by', auth()->id())
                    ->whereIn('id', $ids)
                    ->where('status', 'draft')
                    ->get();

                foreach ($slips as $slip) {
                    $slip->status = 'paid';
                    $slip->payment_date = Carbon::parse($paymentDate);
                    $slip->save();
                }

                return $slips->count();
            });

            return ['success' => true, 'message' => "Berhasil membayar {$count} slip gaji!", 'count' => $count];
        } catch (\Throwable $e) {
            Log::error('Bulk pay slip gaji gagal: '.$e->getMessage());

            return ['success' => false, 'message' => 'Terjadi kesalahan saat membayar slip gaji. Data tidak disimpan.', 'count' => 0];
        }
    }

    /**
     * Menghapus slip gaji terpilih (draft maupun paid — dibayar bisa dihapus
     * untuk memperbaiki data lalu dibuat ulang).
     *
     * @param  array<int, int>  $ids
     */
    public function deleteSlips(array $ids): array
    {
        if (empty($ids)) {
            return ['success' => false, 'message' => 'Tidak ada slip yang dipilih.', 'count' => 0];
        }

        $deleted = SalarySlip::where('created_by', auth()->id())
            ->whereIn('id', $ids)
            ->delete();

        return ['success' => true, 'message' => "Berhasil menghapus {$deleted} slip gaji.", 'count' => $deleted];
    }

    /**
     * Snapshot petinggi untuk blok tanda tangan slip.
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
     * Snapshot petinggi untuk cetakan PDF sebuah slip.
     *
     * @return array<string, array<string, mixed>|null>
     */
    public function getSlipSignatures(?SalarySlip $slip): array
    {
        if ($slip && is_array($slip->signatures)) {
            return $slip->signatures;
        }

        return [
            'disetujui' => null,
            'diperiksa' => null,
            'dibuat' => null,
        ];
    }
}
