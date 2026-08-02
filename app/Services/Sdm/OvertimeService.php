<?php

namespace App\Services\Sdm;

use App\Models\Sdm\Attendance;
use App\Models\Sdm\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk mengelola bisnis logika lembur.
 *
 * Menangani daftar lembur, pembuatan, pembaruan, penghapusan,
 * deteksi duplikat, dan semua aturan bisnis terkait lembur karyawan.
 *
 * Data lembur disimpan di tabel absensi dengan status = 'lembur'.
 */
class OvertimeService
{
    /**
     * Mendapatkan daftar data lembur dengan paginasi, pencarian, dan eager loading.
     *
     * Hanya mengambil data absensi dengan status 'lembur', eager-loading
     * relasi karyawan untuk menghindari query N+1, dan menerapkan filter
     * pencarian opsional pada nama atau kode karyawan.
     *
     * Logika:
     * - Lembur TIDAK punya tabel sendiri — tersimpan di tabel absensi dengan
     *   status = 'lembur'. Filter `status` di sini adalah sumber kebenarannya.
     * - Berbeda dari AttendanceService, pencarian hanya ke relasi employee
     *   (nama/kode), tidak mencocokkan tanggal — data lembur diidentifikasi
     *   lewat karyawan, bukan lewat tanggal.
     *
     * @param  string|null  $search   Kata kunci pencarian (nama atau kode karyawan)
     * @param  int          $perPage  Jumlah data per halaman
     * @return LengthAwarePaginator
     */
    public function getPaginatedOvertimes(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return Attendance::where('status', 'lembur')
            ->where('created_by', auth()->id())
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
     * Mendapatkan semua karyawan yang diurutkan berdasarkan nama untuk dropdown pilihan.
     *
     * Hanya mengambil kolom yang dibutuhkan untuk komponen pilihan yang dapat dicari
     * (employee_code dan name) agar tidak mengambil data yang berlebihan.
     *
     * @return Collection<int, Employee>
     */
    public function getAllEmployees(): Collection
    {
        $userId = auth()->id();

        try {
            return Cache::remember(
                'sdm:employees:dropdown:' . $userId,
                now()->addHours(24),
                function () use ($userId) {
                    return Employee::where('created_by', $userId)
                        ->orderBy('name')
                        ->get(['employee_code', 'name']);
                }
            );
        } catch (\Exception $e) {
            Log::warning(
                'Cache read failed for sdm:employees:dropdown:' . $userId . ': ' .
                $e->getMessage()
            );

            return Employee::where('created_by', $userId)
                ->orderBy('name')
                ->get(['employee_code', 'name']);
        }
    }

    /**
     * Mendapatkan data absensi yang sudah ada, dikelompokkan berdasarkan employee_id
     * untuk validasi duplikat di sisi klien.
     *
     * Mengembalikan array asosiatif dengan struktur:
     * ['EMP001' => ['2025-01-01' => ['id' => 1, 'status' => 'hadir'], ...], ...]
     *
     * Data ini digunakan oleh JavaScript frontend untuk mencegah:
     * - Data lembur duplikat (karyawan yang sama + tanggal yang sama)
     * - Penambahan lembur untuk karyawan dengan status izin/sakit/cuti
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
     * Menyimpan data lembur baru atau memperbarui data absensi yang sudah ada.
     *
     * Bisnis Logika:
     * - Jika data absensi sudah ada untuk karyawan + tanggal yang sama,
     *   perbarui dengan data lembur (ubah status menjadi 'lembur').
     * - Jika tidak ada data, buat absensi baru dengan status 'lembur'.
     * - overtime_total selalu dihitung di sisi server: jam × tarif.
     *
     * @param  array{employee_id: string, attendance_date: string, overtime_hours: float, overtime_rate: int, notes: string|null}  $data  Data input yang sudah divalidasi
     * @return Attendance  Data absensi yang dibuat atau diperbarui
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
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Memperbarui data lembur dengan menghitung ulang total.
     *
     * Logika: overtime_total selalu dihitung ulang di sisi server (jam × tarif)
     * dari input yang baru — nilai total yang dikirim frontend diabaikan untuk
     * mencegah manipulasi nominal.
     *
     * @param  Attendance  $overtime  Instance model absensi yang akan diperbarui
     * @param  array       $data      Data pembaruan yang sudah divalidasi
     * @return bool
     */
    public function updateOvertime(Attendance $overtime, array $data): bool
    {
        $data['overtime_total'] = (float) $data['overtime_hours'] * (int) $data['overtime_rate'];

        return $overtime->update($data);
    }

    /**
     * Menghapus data lembur berdasarkan ID-nya.
     *
     * @param  array<int, int>  $ids  Array ID absensi yang akan dihapus
     * @return int                    Jumlah data yang dihapus
     */
    public function deleteOvertimes(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        return Attendance::whereIn('id', $ids)->delete();
    }
}
