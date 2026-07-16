<?php

namespace App\Services\Sdm;

use App\Models\Sdm\Kasbon;
use App\Models\Sdm\Employee;
use App\Models\Sdm\Division;
use App\Services\InputNormalizer;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for managing cash advance (kasbon) business logic.
 *
 * Handles kasbon listing, creation, updating, deletion,
 * attendance-based max validation, and code generation.
 *
 * All business rules related to kasbon are centralized here.
 */
class KasbonService
{
    /**
     * Get paginated list of kasbons with employee relation.
     *
     * Supports filtering by search (code/notes/employee name), month, year,
     * status, and type. Results are sorted by newest kasbon_date first.
     *
     * @param  string|null  $search   Search keyword (kasbon code, notes, or employee name)
     * @param  int|null     $month    Filter by period month
     * @param  int|null     $year     Filter by period year
     * @param  string|null  $status   Filter by status (pending/deducted)
     * @param  string|null  $type     Filter by type (personal/team)
     * @param  int          $perPage  Number of records per page
     * @return LengthAwarePaginator
     */
    public function getPaginatedKasbons(
        ?string $search,
        ?int $month,
        ?int $year,
        ?string $status,
        ?string $type,
        int $perPage = 10
    ): LengthAwarePaginator {
        return Kasbon::with('employee')
            ->when($search, function ($query, $search) {
                $escapedSearch = $this->escapeLikePattern($search);
                $query->where(function ($q) use ($escapedSearch) {
                    $q->where('kasbon_code', 'like', "%{$escapedSearch}%")
                        ->orWhere('notes', 'like', "%{$escapedSearch}%")
                        ->orWhereHas('employee', function ($empQuery) use ($escapedSearch) {
                            $empQuery->where('name', 'like', "%{$escapedSearch}%");
                        });
                });
            })
            ->when($month, fn($query, $month) => $query->whereMonth('period_start_date', $month))
            ->when($year, fn($query, $year) => $query->whereYear('period_start_date', $year))
            ->when($status, fn($query, $status) => $query->where('status', $status))
            ->when($type, fn($query, $type) => $query->where('kasbon_type', $type))
            ->latest('kasbon_date')
            ->latest('created_at')
            ->paginate($perPage)
            ->appends(request()->only(['search', 'month', 'year', 'status', 'type']));
    }

    /**
     * Get all employees for dropdown selects.
     *
     * Only fetches the columns needed for the dropdown
     * to avoid over-fetching.
     *
     * @return Collection<int, Employee>
     */
    public function getAllEmployees(): Collection
    {
        return Employee::orderBy('name')->get(['employee_code', 'name']);
    }

    /**
     * Get all divisions for dropdown selects.
     *
     * @return Collection<int, Division>
     */
    public function getAllDivisions(): Collection
    {
        return Division::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Store a new kasbon record.
     *
     * Business Logic:
     * - Generate unique kasbon code (KSB001, KSB002, ...)
     * - Auto-detect week_number from period_start_date
     * - For personal kasbon: validate attendance-based max limit
     * - For team kasbon: set employee_id to null
     * - For personal kasbon: set division to null
     * - Default status is 'pending'
     *
     * @param  array{kasbon_type: string, employee_id: string|null, division: string|null, amount: int, kasbon_date: string, period_month: int, period_year: int, period_start_date: string, period_end_date: string, notes: string|null}  $data  Validated input data
     * @return Kasbon  The created kasbon record
     *
     * @throws \Illuminate\Validation\ValidationException  If attendance-based validation fails
     */
    public function storeKasbon(array $data): Kasbon
    {
        $data['amount'] = InputNormalizer::normalizeCurrency($data['amount'] ?? 0);
        $data['kasbon_code'] = Kasbon::generateKasbonCode();
        $data['status'] = 'pending';
        $data['week_number'] = Carbon::parse($data['period_start_date'])->weekOfMonth;

        if ($data['kasbon_type'] === 'team') {
            $data['employee_id'] = null;
        } else {
            $data['division'] = null;
        }

        return Kasbon::create($data);
    }

    /**
     * Validate attendance-based kasbon limit for personal kasbon.
     *
     * Checks:
     * 1. Whether the payroll for this period is already paid
     * 2. Whether the employee has attendance records
     * 3. Whether the requested amount exceeds the max allowed
     *
     * @param  string         $employeeCode    Employee code
     * @param  string         $periodStartDate Period start date (Y-m-d)
     * @param  string         $kasbonDate      Kasbon date (Y-m-d)
     * @param  int            $amount          Requested kasbon amount
     * @return array{valid: bool, message: string}  Validation result
     */
    public function validatePersonalKasbonLimit(
        string $employeeCode,
        string $periodStartDate,
        string $kasbonDate,
        int $amount
    ): array {
        $employee = Employee::find($employeeCode);

        if (!$employee) {
            return ['valid' => false, 'message' => 'Karyawan tidak ditemukan'];
        }

        $periodStartDateCarbon = Carbon::parse($periodStartDate);

        if ($employee->isPayrollPaidByStartDate($periodStartDateCarbon)) {
            return [
                'valid' => false,
                'message' => sprintf(
                    'Tidak dapat melakukan kasbon! Payroll periode %s sudah dibayar (status: paid). Kasbon hanya bisa dilakukan untuk minggu yang belum dibayar.',
                    $periodStartDateCarbon->format('d M Y')
                ),
            ];
        }

        $daysWorked = $employee->getAttendanceUpToDate($periodStartDateCarbon, $kasbonDate);
        $maxKasbon = $employee->getMaxKasbonUpToDate($periodStartDateCarbon, $kasbonDate);

        if ($daysWorked == 0) {
            $periodEndDate = Carbon::parse(request('period_end_date'));
            return [
                'valid' => false,
                'message' => sprintf(
                    'Tidak dapat melakukan kasbon! %s belum memiliki catatan kehadiran periode %s - %s. Kasbon hanya bisa dilakukan setelah karyawan hadir bekerja.',
                    $employee->name,
                    $periodStartDateCarbon->format('d M'),
                    $periodEndDate->format('d M Y')
                ),
            ];
        }

        if ($amount > $maxKasbon) {
            return [
                'valid' => false,
                'message' => sprintf(
                    'Jumlah kasbon Rp %s melebihi batas maksimal! Berdasarkan kehadiran %d hari kerja (gaji harian Rp %s), maksimal kasbon adalah Rp %s',
                    number_format($amount, 0, ',', '.'),
                    $daysWorked,
                    number_format($employee->daily_wage, 0, ',', '.'),
                    number_format($maxKasbon, 0, ',', '.')
                ),
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Validate attendance-based kasbon limit for personal kasbon (update).
     *
     * Uses canTakeKasbon method for validation.
     *
     * @param  string         $employeeCode    Employee code
     * @param  string         $periodStartDate Period start date (Y-m-d)
     * @param  string         $kasbonDate      Kasbon date (Y-m-d)
     * @param  int            $amount          Requested kasbon amount
     * @return array{valid: bool, message: string}  Validation result
     */
    public function validatePersonalKasbonUpdate(
        string $employeeCode,
        string $periodStartDate,
        string $kasbonDate,
        int $amount
    ): array {
        $employee = Employee::find($employeeCode);

        if (!$employee) {
            return ['valid' => false, 'message' => 'Karyawan tidak ditemukan'];
        }

        $periodStartDateCarbon = Carbon::parse($periodStartDate);
        $daysWorked = $employee->getAttendanceUpToDate($periodStartDateCarbon, $kasbonDate);
        $maxKasbon = $employee->getMaxKasbonUpToDate($periodStartDateCarbon, $kasbonDate);
        $dailyWage = $employee->daily_wage ?? $employee->base_salary;

        if (!$employee->canTakeKasbon($amount, $periodStartDateCarbon, $kasbonDate)) {
            return [
                'valid' => false,
                'message' => sprintf(
                    'Kasbon melebihi batas maksimal! %s hanya masuk %d hari pada periode ini sampai tanggal %s. Maksimal kasbon: Rp %s (Rp %s × %d hari)',
                    $employee->name,
                    $daysWorked,
                    Carbon::parse($kasbonDate)->format('d/m/Y'),
                    number_format($maxKasbon, 0, ',', '.'),
                    number_format($dailyWage, 0, ',', '.'),
                    $daysWorked
                ),
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Update an existing kasbon record.
     *
     * Business Logic:
     * - Only pending kasbons can be updated
     * - For personal kasbon: validate attendance-based max limit
     * - For team kasbon: set employee_id to null
     * - For personal kasbon: set division to null
     *
     * @param  Kasbon  $kasbon  The kasbon model instance to update
     * @param  array   $data    Validated update data
     * @return bool
     */
    public function updateKasbon(Kasbon $kasbon, array $data): bool
    {
        $data['amount'] = InputNormalizer::normalizeCurrency($data['amount'] ?? 0);

        if ($data['kasbon_type'] === 'team') {
            $data['employee_id'] = null;
        } else {
            $data['division'] = null;
        }

        return $kasbon->update($data);
    }

    /**
     * Bulk delete kasbon records by their codes.
     *
     * Business Logic:
     * - Only pending kasbons can be deleted
     * - Deducted kasbons are skipped
     *
     * @param  array<int, string>  $kasbonCodes  Array of kasbon codes to delete
     * @return array{deleted: int, skipped: int}  Count of deleted and skipped records
     */
    public function deleteSelectedKasbons(array $kasbonCodes): array
    {
        $pendingKasbons = Kasbon::whereIn('kasbon_code', $kasbonCodes)->pending()->get();

        $deleted = $pendingKasbons->count();
        $skipped = count($kasbonCodes) - $deleted;

        if ($deleted > 0) {
            Kasbon::whereIn('kasbon_code', $pendingKasbons->pluck('kasbon_code'))->delete();
        }

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }

    /**
     * Get total kasbon for a specific employee and period.
     *
     * @param  string         $employeeCode    Employee code
     * @param  string         $periodStartDate Period start date
     * @return int  Total pending kasbon amount
     */
    public function getTotalForEmployee(string $employeeCode, string $periodStartDate): int
    {
        return Kasbon::getTotalForEmployee($employeeCode, $periodStartDate);
    }

    /**
     * Get total team kasbon for a specific period.
     *
     * @param  string  $periodStartDate  Period start date
     * @return int     Total pending team kasbon amount
     */
    public function getTotalTeamKasbon(string $periodStartDate): int
    {
        return Kasbon::getTotalTeamKasbon($periodStartDate);
    }

    /**
     * Check maximum kasbon allowed for an employee based on attendance.
     *
     * Returns comprehensive info including employee name, days worked,
     * daily wage, max kasbon, and whether payroll is already paid.
     *
     * @param  string  $employeeCode    Employee code
     * @param  string  $periodStartDate Period start date (Y-m-d)
     * @param  string  $kasbonDate      Kasbon date (Y-m-d)
     * @return array{success: bool, employee_name: string, days_worked: int, daily_wage: int, max_kasbon: int, payroll_paid: bool, no_attendance: bool, max_kasbon_formatted: string, message: string}
     */
    public function checkMaxKasbon(string $employeeCode, string $periodStartDate, string $kasbonDate): array
    {
        $employee = Employee::find($employeeCode);

        if (!$employee) {
            return [
                'success' => false,
                'employee_name' => '',
                'days_worked' => 0,
                'daily_wage' => 0,
                'max_kasbon' => 0,
                'payroll_paid' => false,
                'no_attendance' => false,
                'max_kasbon_formatted' => 'Rp 0',
                'message' => 'Karyawan tidak ditemukan',
            ];
        }

        $periodStartDateCarbon = Carbon::parse($periodStartDate);

        if ($employee->isPayrollPaidByStartDate($periodStartDateCarbon)) {
            return [
                'success' => false,
                'employee_name' => $employee->name,
                'days_worked' => 0,
                'daily_wage' => 0,
                'max_kasbon' => 0,
                'payroll_paid' => true,
                'no_attendance' => false,
                'max_kasbon_formatted' => 'Rp 0',
                'message' => sprintf(
                    'Payroll periode %s sudah dibayar (status: paid). Kasbon hanya bisa dilakukan untuk minggu yang belum dibayar.',
                    $periodStartDateCarbon->format('d M Y')
                ),
            ];
        }

        $daysWorked = $employee->getAttendanceUpToDate($periodStartDateCarbon, $kasbonDate);
        $maxKasbon = $employee->getMaxKasbonUpToDate($periodStartDateCarbon, $kasbonDate);
        $dailyWage = $employee->daily_wage ?? $employee->base_salary;

        if ($daysWorked == 0) {
            return [
                'success' => false,
                'employee_name' => $employee->name,
                'days_worked' => 0,
                'daily_wage' => $dailyWage,
                'max_kasbon' => 0,
                'payroll_paid' => false,
                'no_attendance' => true,
                'max_kasbon_formatted' => 'Rp 0',
                'message' => sprintf(
                    '%s belum memiliki catatan kehadiran pada periode ini. Kasbon hanya bisa dilakukan setelah karyawan hadir bekerja.',
                    $employee->name
                ),
            ];
        }

        return [
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
                Carbon::parse($kasbonDate)->format('d/m/Y'),
                number_format($maxKasbon, 0, ',', '.')
            ),
        ];
    }

    /**
     * Escape special LIKE pattern characters to prevent manipulation.
     *
     * The % and _ characters are wildcard characters in SQL LIKE patterns.
     * This method escapes them so user input is treated as literal text.
     *
     * @param  string  $value  Raw search input
     * @return string  Escaped string safe for LIKE queries
     */
    private function escapeLikePattern(string $value): string
    {
        return addcslashes($value, '%_');
    }
}
