<?php

namespace App\Services\Sdm;

use App\Models\Sdm\Division;
use App\Models\Sdm\Employee;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Service untuk mengelola bisnis logika karyawan.
 *
 * Menangani daftar karyawan, pembuatan, pembaruan, penghapusan, dan
 * semua bisnis logika yang bukan bagian dari model atau controller.
 */
class EmployeeService
{
    /**
     * Mendapatkan daftar karyawan dengan paginasi dan pencarian opsional.
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
     * Mendapatkan semua divisi yang diurutkan berdasarkan nama.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllDivisions(): Collection
    {
        return Division::orderBy('name')->get();
    }

    /**
     * Membuat karyawan baru dengan kode karyawan yang dihasilkan secara otomatis.
     *
     * @param  array  $data  Data karyawan yang sudah divalidasi
     * @return \App\Models\Sdm\Employee
     */
    public function createEmployee(array $data): Employee
    {
        $data['employee_code'] = Employee::generateEmployeeCode();
        return Employee::create($data);
    }

    /**
     * Memperbarui karyawan yang sudah ada.
     *
     * @param  \App\Models\Sdm\Employee  $employee
     * @param  array  $data  Data karyawan yang sudah divalidasi
     * @return bool
     */
    public function updateEmployee(Employee $employee, array $data): bool
    {
        return $employee->update($data);
    }

    /**
     * Menghapus karyawan berdasarkan kode karyawannya.
     *
     * @param  array  $employeeCodes
     * @return int  Jumlah data yang dihapus
     */
    public function deleteEmployees(array $employeeCodes): int
    {
        if (empty($employeeCodes)) {
            return 0;
        }

        return Employee::whereIn('employee_code', $employeeCodes)->delete();
    }
}
