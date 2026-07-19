<?php

namespace App\Services\Notification;

use App\Repositories\Notification\SalaryReminderRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Service untuk mengelola business logic Salary Reminder.
 *
 * Service ini bertanggung jawab atas pengambilan data reminder gaji,
 * statistik ringkasan, dan attendance reminders.
 * Business logic tidak boleh berada langsung di Controller.
 */
class SalaryReminderService
{
    public function __construct(
        private readonly SalaryReminderRepository $repository
    ) {}

    /**
     * Mendapatkan daftar salary reminder dengan filter dan paginasi.
     *
     * @param  array  $filters  Parameter filter dari request (month, year, status, search)
     * @return LengthAwarePaginator
     */
    public function getPaginatedReminders(array $filters): LengthAwarePaginator
    {
        return $this->repository->search($filters);
    }

    /**
     * Mendapatkan statistik ringkasan berdasarkan filter yang diterapkan.
     *
     * @param  array  $filters  Parameter filter dari request
     * @return array  ['total' => int, 'draft' => int, 'paid' => int]
     */
    public function getSummaryStats(array $filters): array
    {
        return [
            'total' => $this->repository->countByFilters($filters),
            'draft' => $this->repository->countByStatus('draft', $filters),
            'paid' => $this->repository->countByStatus('paid', $filters),
        ];
    }

    /**
     * Mendapatkan data attendance reminders dengan data employee yang sudah di-resolve.
     *
     * Attendance reminders menampilkan karyawan yang sudah absen minggu 1-4
     * namun payroll belum dibuatkan.
     *
     * @param  int  $month  Bulan filter (0 = semua bulan)
     * @param  int  $year   Tahun filter
     * @return Collection  Koleksi objek attendance reminder
     */
    public function getAttendanceReminders(int $month, int $year): Collection
    {
        $attendanceData = $this->repository->getAttendanceReminders($month, $year);

        return $attendanceData->map(function ($attendance) {
            // Resolve employee dari eager-loaded relationship
            $employee = $attendance->employee;

            if (!$employee) {
                return null;
            }

            // Hitung minggu berdasarkan tanggal pertama absensi
            $dayOfMonth = Carbon::parse($attendance->first_date)->day;
            $week = (int) ceil($dayOfMonth / 7);

            return (object) [
                'employee_id' => $attendance->employee_id,
                'employee_name' => $employee->name,
                'period_month' => $attendance->month,
                'period_year' => $attendance->year,
                'week_number' => $week,
                'first_attendance_date' => Carbon::parse($attendance->first_date),
                'last_attendance_date' => Carbon::parse($attendance->last_date),
                'employee' => $employee,
            ];
        })->filter()->values();
    }

    /**
     * Mendapatkan nilai default tahun dari filter.
     *
     * @param  array  $filters  Parameter filter dari request
     * @return int
     */
    public function getDefaultYear(array $filters): int
    {
        return !empty($filters['year']) ? (int) $filters['year'] : (int) date('Y');
    }

    /**
     * Mendapatkan nilai default bulan dari filter.
     *
     * @param  array  $filters  Parameter filter dari request
     * @return int
     */
    public function getDefaultMonth(array $filters): int
    {
        return !empty($filters['month']) ? (int) $filters['month'] : (int) date('m');
    }
}
