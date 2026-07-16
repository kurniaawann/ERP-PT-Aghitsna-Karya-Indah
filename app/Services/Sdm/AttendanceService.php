<?php

namespace App\Services\Sdm;

use App\Models\Sdm\Attendance;
use App\Models\Sdm\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * Service for managing attendance business logic.
 *
 * Handles attendance listing, bulk creation, updating, deletion,
 * duplicate detection, and all business rules related to employee attendance.
 */
class AttendanceService
{
    /**
     * Get paginated list of attendances with search and eager loading.
     *
     * @param  string|null  $search     Search keyword (employee name, code, or date)
     * @param  int          $perPage    Number of records per page
     * @return LengthAwarePaginator
     */
    public function getPaginatedAttendances(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return Attendance::with('employee')
            ->when($search, function ($query, $search) {
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                })
                    ->orWhere('attendance_date', 'like', "%{$search}%");
            })
            ->latest('attendance_date')
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * Get all employees ordered by name for form selects.
     *
     * @return Collection<int, Employee>
     */
    public function getAllEmployees(): Collection
    {
        return Employee::orderBy('name')->get(['employee_code', 'name']);
    }

    /**
     * Get existing attendance grouped by employee_id for client-side duplicate validation.
     *
     * Returns an associative array: ['EMP001' => ['2025-01-01', '2025-01-02'], ...]
     *
     * @return array<string, array<int, string>>
     */
    public function getExistingAttendance(): array
    {
        return Attendance::select('employee_id', 'attendance_date')
            ->get()
            ->groupBy('employee_id')
            ->map(function ($items) {
                return $items->pluck('attendance_date')
                    ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
                    ->toArray();
            })
            ->toArray();
    }

    /**
     * Find duplicate attendance records for given employees and date range.
     *
     * Performs a single query to fetch all existing records in the range,
     * then checks each employee+date combination against the results.
     *
     * @param  array<int, string>  $employeeIds  Array of employee_code values
     * @param  Carbon              $startDate    Start date (inclusive)
     * @param  Carbon              $endDate      End date (inclusive)
     * @return array<int, string>  Array of human-readable duplicate descriptions
     */
    public function findDuplicates(array $employeeIds, Carbon $startDate, Carbon $endDate): array
    {
        $existingRecords = Attendance::whereIn('employee_id', $employeeIds)
            ->whereBetween('attendance_date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ])
            ->get(['id', 'employee_id', 'attendance_date', 'status'])
            ->keyBy(fn ($record) => $record->employee_id . '_' . Carbon::parse($record->attendance_date)->format('Y-m-d'));

        $duplicates = [];
        $employeeNames = Employee::whereIn('employee_code', $employeeIds)
            ->pluck('name', 'employee_code')
            ->toArray();

        foreach ($employeeIds as $employeeId) {
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                $key = $employeeId . '_' . $currentDate->format('Y-m-d');

                if (isset($existingRecords[$key])) {
                    $record = $existingRecords[$key];
                    $duplicates[] = sprintf(
                        '%s pada tanggal %s (Status: %s)',
                        $employeeNames[$employeeId] ?? $employeeId,
                        $currentDate->format('d-m-Y'),
                        $record->status
                    );
                }

                $currentDate->addDay();
            }
        }

        return $duplicates;
    }

    /**
     * Bulk create attendance records for multiple employees across a date range.
     *
     * @param  array<int, string>  $employeeIds  Array of employee_code values
     * @param  Carbon              $startDate    Start date (inclusive)
     * @param  Carbon              $endDate      End date (inclusive)
     * @param  string              $status       Attendance status (hadir|izin|sakit|cuti)
     * @param  string|null         $notes        Optional notes
     * @return int                 Number of records inserted
     */
    public function bulkCreate(array $employeeIds, Carbon $startDate, Carbon $endDate, string $status, ?string $notes): int
    {
        $totalInserted = 0;

        foreach ($employeeIds as $employeeId) {
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                Attendance::create([
                    'employee_id' => $employeeId,
                    'attendance_date' => $currentDate->format('Y-m-d'),
                    'status' => $status,
                    'notes' => $notes,
                ]);
                $totalInserted++;
                $currentDate->addDay();
            }
        }

        return $totalInserted;
    }

    /**
     * Update a single attendance record.
     *
     * @param  Attendance  $attendance  The attendance model instance
     * @param  array       $data        Validated update data
     * @return bool
     */
    public function updateAttendance(Attendance $attendance, array $data): bool
    {
        return $attendance->update($data);
    }

    /**
     * Delete attendance records by their IDs.
     *
     * @param  array<int, int>  $ids  Array of attendance IDs
     * @return int                    Number of deleted records
     */
    public function deleteAttendances(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        return Attendance::whereIn('id', $ids)->delete();
    }

    /**
     * Build a human-readable success message for bulk creation.
     *
     * @param  int     $totalInserted  Number of records inserted
     * @param  int     $employeeCount  Number of employees
     * @param  Carbon  $startDate      Start date
     * @param  Carbon  $endDate        End date
     * @return string
     */
    public function buildBulkCreateMessage(int $totalInserted, int $employeeCount, Carbon $startDate, Carbon $endDate): string
    {
        $totalDays = $startDate->diffInDays($endDate) + 1;

        return sprintf(
            'Berhasil menambahkan %d record absensi untuk %d karyawan selama %d hari (%s s/d %s).',
            $totalInserted,
            $employeeCount,
            $totalDays,
            $startDate->format('d-m-Y'),
            $endDate->format('d-m-Y')
        );
    }

    /**
     * Build a human-readable error message for duplicate records.
     *
     * Limits display to the first 5 duplicates and adds a count of remaining items.
     *
     * @param  array<int, string>  $duplicates  Array of duplicate descriptions
     * @return string
     */
    public function buildDuplicateErrorMessage(array $duplicates): string
    {
        $errorMessage = 'Karyawan berikut sudah memiliki absensi: ';
        $displayDuplicates = array_slice($duplicates, 0, 5);
        $errorMessage .= implode('; ', $displayDuplicates);

        if (count($duplicates) > 5) {
            $errorMessage .= sprintf(' dan %d lainnya', count($duplicates) - 5);
        }

        $errorMessage .= '. Silakan hapus atau edit data yang sudah ada.';

        return $errorMessage;
    }
}
