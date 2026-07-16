<?php

namespace App\Services\Sdm;

use App\Models\Sdm\Division;
use App\Models\Sdm\Employee;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Service for managing employee business logic.
 *
 * Handles employee listing, creation, updating, deletion, and
 * all business logic that does not belong in the model or controller.
 */
class EmployeeService
{
    /**
     * Get paginated list of employees with optional search.
     *
     * @param  string|null  $search
     * @param  int          $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedEmployees(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return Employee::when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        })
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * Get all divisions ordered by name.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllDivisions(): Collection
    {
        return Division::orderBy('name')->get();
    }

    /**
     * Create a new employee with auto-generated employee code.
     *
     * @param  array  $data  Validated employee data
     * @return \App\Models\Sdm\Employee
     */
    public function createEmployee(array $data): Employee
    {
        $data['employee_code'] = Employee::generateEmployeeCode();
        return Employee::create($data);
    }

    /**
     * Update an existing employee.
     *
     * @param  \App\Models\Sdm\Employee  $employee
     * @param  array  $data  Validated employee data
     * @return bool
     */
    public function updateEmployee(Employee $employee, array $data): bool
    {
        return $employee->update($data);
    }

    /**
     * Delete employees by their employee codes.
     *
     * @param  array  $employeeCodes
     * @return int  Number of deleted records
     */
    public function deleteEmployees(array $employeeCodes): int
    {
        if (empty($employeeCodes)) {
            return 0;
        }

        return Employee::whereIn('employee_code', $employeeCodes)->delete();
    }
}
