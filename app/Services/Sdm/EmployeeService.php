<?php

namespace App\Services\Sdm;

use App\Models\Sdm\Division;
use App\Models\Sdm\Employee;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
     * Mendapatkan daftar karyawan dengan paginasi, pencarian, dan filter
     * proyek opsional.
     *
     * Logika:
     * - Pencarian (nama/kode) dibungkus closure + grup WHERE agar OR antar
     *   kolom tidak mengganggu kondisi lain.
     * - Filter proyek (project_name) memakai where opsional; nilai kosong
     *   diabaikan.
     * - Diurutkan created_at terbaru; kode karyawan dipakai sebagai primary key
     *   bisnis (employee_code), bukan id numerik.
     */
    public function getPaginatedEmployees(?string $search, ?string $projectName = null, int $perPage = 15): LengthAwarePaginator
    {
        return Employee::where('created_by', auth()->id())
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->when($projectName, function ($query, $projectName) {
                $query->where('project_name', $projectName);
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
     */
    public function getAllDivisions(): Collection
    {
        $userId = auth()->id();

        try {
            return Cache::remember(
                'sdm:divisions:dropdown:'.$userId,
                now()->addHours(24),
                function () use ($userId) {
                    return Division::where('created_by', $userId)
                        ->orderBy('name')
                        ->get();
                }
            );
        } catch (\Exception $e) {
            Log::warning(
                'Cache read failed for sdm:divisions:dropdown:'.$userId.': '.
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
     */
    public function createEmployee(array $data): Employee
    {
        $data['employee_code'] = Employee::generateEmployeeCode();
        $data['created_by'] = auth()->id();
        $data['employment_type'] = $data['employment_type'] ?? 'harian';

        return Employee::create($data);
    }

    /**
     * Membuat banyak karyawan sekaligus.
     *
     * Setiap data karyawan diproses satu per satu lewat createEmployee()
     * sehingga kode karyawan di-generate otomatis dan unik untuk masing-masing.
     *
     * @param  array  $employees  Array data karyawan yang sudah divalidasi
     * @return int Jumlah karyawan yang berhasil dibuat
     */
    public function createEmployees(array $employees): int
    {
        $created = 0;

        foreach ($employees as $employeeData) {
            $this->createEmployee($employeeData);
            $created++;
        }

        return $created;
    }

    /**
     * Memperbarui karyawan yang sudah ada.
     *
     * @param  array  $data  Data karyawan yang sudah divalidasi
     */
    public function updateEmployee(Employee $employee, array $data): bool
    {
        return $employee->update($data);
    }

    /**
     * Menghapus karyawan berdasarkan kode karyawannya.
     *
     * Hanya karyawan milik user login yang dihapus. Karyawan yang masih
     * memiliki data payroll atau kasbon TIDAK bisa dihapus, karena FK
     * payrolls.employee_id dan kasbons.employee_id bercascade delete — payroll
     * (termasuk yang sudah dibayar) dan kasbon akan hilang diam-diam tanpa
     * me-reconcile baris Laporan Keuangan Proyek. Guard ini konsisten dengan
     * pola hapus RAB/rekap proyek.
     *
     * @return int Jumlah data yang dihapus
     *
     * @throws \DomainException Bila karyawan masih dipakai payroll atau kasbon
     */
    public function deleteEmployees(array $employeeCodes): int
    {
        if (empty($employeeCodes)) {
            return 0;
        }

        $employees = Employee::where('created_by', auth()->id())
            ->whereIn('employee_code', $employeeCodes)
            ->get();

        if ($employees->isEmpty()) {
            return 0;
        }

        $blocked = [];

        foreach ($employees as $employee) {
            $hasPayroll = DB::table('payrolls')
                ->where('created_by', $employee->created_by)
                ->where('employee_id', $employee->employee_code)
                ->exists();

            $hasKasbon = DB::table('kasbons')
                ->where('created_by', $employee->created_by)
                ->where('employee_id', $employee->employee_code)
                ->exists();

            if ($hasPayroll || $hasKasbon) {
                $blocked[] = $employee->name ?: $employee->employee_code;
            }
        }

        if (! empty($blocked)) {
            throw new \DomainException(
                'Karyawan berikut tidak dapat dihapus karena masih memiliki data payroll atau kasbon: '.implode(', ', $blocked).'. Hapus atau selesaikan data tersebut terlebih dahulu.'
            );
        }

        return Employee::where('created_by', auth()->id())
            ->whereIn('employee_code', $employeeCodes)
            ->delete();
    }

    public function flushCache(): void
    {
        try {
            Cache::forget('sdm:employees:dropdown:'.auth()->id());
        } catch (\Exception $e) {
            Log::warning('Cache DELETE error [sdm:employees:dropdown]: '.$e->getMessage());
        }
    }
}
