<?php

namespace App\Repositories\Notification;

use App\Models\Notification\SalaryReminder;
use App\Models\Sdm\Employee;
use App\Models\Sdm\Payroll;
use App\Models\Sdm\Attendance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repository untuk akses data Salary Reminder.
 *
 * Menangani query database terkait data pengingat gaji karyawan
 * menggunakan Eloquent dan query builder.
 * Scope pencarian delegasi ke Model scope untuk menghindari duplikasi.
 */
class SalaryReminderRepository
{
    /**
     * Mencari data salary reminder dengan paginasi dan filter.
     *
     * @param  array  $filters  Parameter filter (month, year, status, search)
     * @return LengthAwarePaginator
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = SalaryReminder::with(['employee', 'payroll'])
            ->where('created_by', auth()->id());

        // Filter berdasarkan bulan
        if (!empty($filters['month'])) {
            $query->where('period_month', $filters['month']);
        }

        // Filter berdasarkan tahun (default: tahun saat ini)
        $year = !empty($filters['year']) ? $filters['year'] : date('Y');
        $query->where('period_year', $year);

        // Filter berdasarkan status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Pencarian berdasarkan nama karyawan atau employee_id
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('employee_id', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($subq) use ($search) {
                        $subq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $query->orderBy('created_at', 'desc');

        return $query->paginate(10)->appends($filters);
    }

    /**
     * Menghitung total reminder berdasarkan filter yang diterapkan.
     *
     * @param  array  $filters  Parameter filter yang sama dengan search()
     * @return int
     */
    public function countByFilters(array $filters): int
    {
        return SalaryReminder::with([])
            ->where('created_by', auth()->id())
            ->when(!empty($filters['month']), fn ($q) => $q->where('period_month', $filters['month']))
            ->when(true, fn ($q) => $q->where('period_year', $filters['year'] ?? date('Y')))
            ->when(!empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where('employee_id', 'like', "%{$search}%")
                    ->orWhereHas('employee', fn ($subq) => $subq->where('name', 'like', "%{$search}%"));
            })
            ->count();
    }

    /**
     * Menghitung jumlah reminder berdasarkan status (draft/paid).
     *
     * @param  string      $status   Status yang dihitung ('draft' atau 'paid')
     * @param  array       $filters  Parameter filter yang sama
     * @return int
     */
    public function countByStatus(string $status, array $filters): int
    {
        return SalaryReminder::query()
            ->where('created_by', auth()->id())
            ->when(!empty($filters['month']), fn ($q) => $q->where('period_month', $filters['month']))
            ->when(true, fn ($q) => $q->where('period_year', $filters['year'] ?? date('Y')))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where('employee_id', 'like', "%{$search}%")
                    ->orWhereHas('employee', fn ($subq) => $subq->where('name', 'like', "%{$search}%"));
            })
            ->where('status', $status)
            ->count();
    }

    /**
     * Mendapatkan data attendance reminders untuk karyawan yang sudah absen
     * minggu 1-4 namun payroll belum dibuat.
     *
     * Menggunakan batch query untuk menghindari N+1:
     * 1. Ambil semua attendance teragregasi dalam 1 query
     * 2. Ambil semua employee_id yang sudah punya payroll dalam 1 query
     * 3. Filter di PHP tanpa query tambahan
     *
     * @param  int  $month  Bulan filter (0 = semua bulan)
     * @param  int  $year   Tahun filter
     * @return Collection
     */
    public function getAttendanceReminders(int $month, int $year): Collection
    {
        // 1. Ambil data attendance teragregasi (1 query)
        $attendanceData = Attendance::with('employee')
            ->selectRaw('
                employee_id,
                MONTH(attendance_date) as month,
                YEAR(attendance_date) as year,
                MIN(attendance_date) as first_date,
                MAX(attendance_date) as last_date,
                COUNT(*) as total_attendance
            ')
            ->whereRaw('DAY(attendance_date) BETWEEN 1 AND 28')
            ->whereRaw('YEAR(attendance_date) = ?', [$year])
            ->when($month > 0, fn ($q) => $q->whereRaw('MONTH(attendance_date) = ?', [$month]))
            ->groupByRaw('employee_id, MONTH(attendance_date), YEAR(attendance_date)')
            ->get();

        if ($attendanceData->isEmpty()) {
            return collect();
        }

        // 2. Ambil semua kombinasi employee_id + bulan + tahun yang sudah punya payroll (1 query)
        $employeeIds = $attendanceData->pluck('employee_id')->unique()->toArray();
        $months = $attendanceData->pluck('month')->unique()->toArray();

        $existingPayrollKeys = Payroll::whereIn('employee_id', $employeeIds)
            ->where('period_year', $year)
            ->whereIn('period_month', $months)
            ->where('created_by', auth()->id())
            ->select('employee_id', 'period_month', 'period_year')
            ->get()
            ->map(fn ($p) => $p->employee_id . '-' . $p->period_month . '-' . $p->period_year)
            ->toArray();

        // 3. Filter di PHP - tidak ada query tambahan
        return $attendanceData->filter(function ($attendance) use ($existingPayrollKeys) {
            $key = $attendance->employee_id . '-' . $attendance->month . '-' . $attendance->year;
            return !in_array($key, $existingPayrollKeys);
        })->values();
    }
}
