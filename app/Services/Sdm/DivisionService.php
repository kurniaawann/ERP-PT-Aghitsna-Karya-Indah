<?php

namespace App\Services\Sdm;

use App\Models\Sdm\Division;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service for managing division business logic.
 *
 * Handles division listing, creation, updating, deletion,
 * and all business logic that does not belong in the model or controller.
 */
class DivisionService
{
    /**
     * Get paginated list of divisions with employee count and optional search.
     *
     * Uses withCount to avoid N+1 queries when displaying employee counts.
     * Search is scoped to name and description fields with proper grouping.
     *
     * @param  string|null  $search
     * @param  int          $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedDivisions(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return Division::withCount('employees')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * Create a new division.
     *
     * @param  array  $data  Validated division data (name, description)
     * @return Division
     */
    public function createDivision(array $data): Division
    {
        return Division::create($data);
    }

    /**
     * Update an existing division.
     *
     * @param  Division  $division
     * @param  array     $data  Validated division data (name, description)
     * @return bool
     */
    public function updateDivision(Division $division, array $data): bool
    {
        return $division->update($data);
    }

    /**
     * Check if any of the given divisions have associated employees.
     *
     * Returns the names of divisions that still have employees,
     * allowing the caller to display a user-friendly error message.
     *
     * @param  array<int>  $ids  Division IDs to check
     * @return array<string>  Names of divisions that have employees
     */
    public function getDivisionsWithEmployees(array $ids): array
    {
        return Division::whereIn('id', $ids)
            ->whereHas('employees')
            ->pluck('name')
            ->toArray();
    }

    /**
     * Delete divisions by their IDs.
     *
     * @param  array<int>  $ids
     * @return int  Number of deleted records
     */
    public function deleteDivisions(array $ids): int
    {
        return Division::whereIn('id', $ids)->delete();
    }
}
