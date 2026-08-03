<?php

namespace App\Services\Sdm;

use App\Models\Sdm\Division;
use App\Models\Sdm\Employee;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
     * Logika:
     * - Pencarian (nama/kode) dibungkus closure + grup WHERE agar OR antar
     *   kolom tidak mengganggu kondisi lain.
     * - Diurutkan created_at terbaru; kode karyawan dipakai sebagai primary key
     *   bisnis (employee_code), bukan id numerik.
     *
     * @param  string|null  $search
     * @param  int          $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
  public function getPaginatedEmployees(?string $search, int $perPage = 15): LengthAwarePaginator
{
    return Employee::where('created_by', auth()->id())
        ->when($search, function ($query, $search) {
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
     * Logika: hasil di-cache 24 jam di key 'sdm:divisions:dropdown:{userId}'
     * (key yang sama dipakai DivisionService saat flush) — cache di-flush saat
     * CRUD divisi. Fallback query langsung jika cache bermasalah.
     *
     * @return \Illuminate\Support\Collection
     */
   public function getAllDivisions(): Collection
{
    $userId = auth()->id();

    try {
        return Cache::remember(
            'sdm:divisions:dropdown:' . $userId,
            now()->addHours(24),
            function () use ($userId) {
                return Division::where('created_by', $userId)
                    ->orderBy('name')
                    ->get();
            }
        );
    } catch (\Exception $e) {
        Log::warning(
            'Cache read failed for sdm:divisions:dropdown:' . $userId . ': ' .
            $e->getMessage()
        );

        return Division::where('created_by', $userId)
            ->orderBy('name')
            ->get();
    }
}

    /**
     * Membuat karyawan baru dengan kode karyawan yang dihasilkan secara otomatis.
     *
     * Logika:
     * - employee_code di-generate di sisi server (Employee::generateEmployeeCode())
     *   sehingga user tidak bisa memasukkan kode sembarangan/duplikat.
     * - created_by di-set dari user login. Pemanggil wajib memanggil flushCache()
     *   agar dropdown karyawan tidak basi.
     *
     * @param  array  $data  Data karyawan yang sudah divalidasi
     * @return \App\Models\Sdm\Employee
     */
    public function createEmployee(array $data): Employee
    {
        $data['employee_code'] = Employee::generateEmployeeCode();
        $data['created_by'] = auth()->id();
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

    public function flushCache(): void
    {
        try {
            Cache::forget('sdm:employees:dropdown:' . auth()->id());
        } catch (\Exception $e) {
            Log::warning('Cache DELETE error [sdm:employees:dropdown]: ' . $e->getMessage());
        }
    }
}
