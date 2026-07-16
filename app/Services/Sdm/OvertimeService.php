<?php

namespace App\Services\Sdm;

use App\Models\Sdm\Attendance;
use App\Models\Sdm\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * Service for managing overtime business logic.
 *
 * Handles overtime listing, creation, updating, deletion,
 * duplicate detection, and all business rules related to employee overtime.
 *
 * Overtime data is stored in the attendances table with status = 'lembur'.
 */
class OvertimeService
{
    /**
     * Get paginated list of overtime records with search and eager loading.
     *
     * Fetches only attendance records with status 'lembur', eager-loads
     * the employee relation to avoid N+1 queries, and applies optional
     * search filter on employee name or code.
     *
     * @param  string|null  $search   Search keyword (employee name or code)
     * @param  int          $perPage  Number of records per page
     * @return LengthAwarePaginator
     */
    public function getPaginatedOvertimes(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return Attendance::where('status', 'lembur')
            ->with('employee')
            ->when($search, function ($query, $search) {
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->latest('attendance_date')
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * Get all employees ordered by name for dropdown selects.
     *
     * Only fetches the columns needed for the searchable select component
     * (employee_code and name) to avoid over-fetching.
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
     * Returns an associative array structured as:
     * ['EMP001' => ['2025-01-01' => ['id' => 1, 'status' => 'hadir'], ...], ...]
     *
     * This data is used by the frontend JavaScript to prevent:
     * - Duplicate overtime records (same employee + same date)
     * - Adding overtime for employees with status izin/sakit/cuti
     *
     * @return array<string, array<string, array{id: int, status: string}>>
     */
    public function getExistingAttendance(): array
    {
        return Attendance::select('employee_id', 'attendance_date', 'id', 'status')
            ->get()
            ->groupBy('employee_id')
            ->map(function ($items) {
                return $items->mapWithKeys(function ($item) {
                    return [
                        Carbon::parse($item->attendance_date)->format('Y-m-d') => [
                            'id' => $item->id,
                            'status' => $item->status,
                        ],
                    ];
                });
            })
            ->toArray();
    }

    /**
     * Store a new overtime record or update an existing attendance record.
     *
     * Business Logic:
     * - If an attendance record already exists for the same employee + date,
     *   update it with overtime data (change status to 'lembur').
     * - If no record exists, create a new attendance with status 'lembur'.
     * - overtime_total is always calculated server-side: hours × rate.
     *
     * @param  array{employee_id: string, attendance_date: string, overtime_hours: float, overtime_rate: int, notes: string|null}  $data  Validated input data
     * @return Attendance  The created or updated attendance record
     */
    public function storeOvertime(array $data): Attendance
    {
        $overtimeTotal = (float) $data['overtime_hours'] * (int) $data['overtime_rate'];

        $existingAttendance = Attendance::where('employee_id', $data['employee_id'])
            ->where('attendance_date', $data['attendance_date'])
            ->first();

        if ($existingAttendance) {
            $existingAttendance->update([
                'status' => 'lembur',
                'overtime_hours' => (float) $data['overtime_hours'],
                'overtime_rate' => (int) $data['overtime_rate'],
                'overtime_total' => (int) $overtimeTotal,
                'notes' => $data['notes'] ?? null,
            ]);

            return $existingAttendance;
        }

        return Attendance::create([
            'employee_id' => $data['employee_id'],
            'attendance_date' => $data['attendance_date'],
            'status' => 'lembur',
            'overtime_hours' => (float) $data['overtime_hours'],
            'overtime_rate' => (int) $data['overtime_rate'],
            'overtime_total' => (int) $overtimeTotal,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Update an overtime record with recalculated total.
     *
     * overtime_total is always recalculated server-side to prevent
     * tampering with the total value from the client.
     *
     * @param  Attendance  $overtime  The attendance model instance to update
     * @param  array       $data      Validated update data
     * @return bool
     */
    public function updateOvertime(Attendance $overtime, array $data): bool
    {
        $data['overtime_total'] = (float) $data['overtime_hours'] * (int) $data['overtime_rate'];

        return $overtime->update($data);
    }

    /**
     * Delete overtime records by their IDs.
     *
     * @param  array<int, int>  $ids  Array of attendance IDs to delete
     * @return int                    Number of deleted records
     */
    public function deleteOvertimes(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        return Attendance::whereIn('id', $ids)->delete();
    }
}
