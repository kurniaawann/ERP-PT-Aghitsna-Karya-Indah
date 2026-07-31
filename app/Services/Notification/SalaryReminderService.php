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
     * Logika: filter (month, year, status, search) dan pagination sepenuhnya
     * didelegasikan ke repository->search(). Query terpusat di repository
     * agar filter tabel reminder tidak terduplikasi di beberapa tempat.
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
     * Logika: tiga hitungan terpisah (total/draft/paid) dengan filter yang
     * sama; tidak ada saling kurang sehingga tidak perlu guard seperti modul
     * invoice reminder.
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
     * Logika:
     * - Data attendance teragregasi diambil sekali oleh repository (batch,
     *   anti N+1): karyawan dengan absensi tanggal 1-28, dikelompokkan per
     *   employee+bulan+tahun.
     * - map() me-resolve objek employee dari relasi eager-loaded; jika karyawan
     *   sudah dihapus (employee null) → item dijadikan null lalu dibuang oleh
     *   ->filter().
     * - week_number dihitung dari tanggal pertama absensi: ceil(day / 7).
     *   CATATAN: ini pendekatan perkiraan (day 1-7 → minggu 1, dst.), bukan
     *   sistem minggu Senin-Sabtu dari PayrollService.
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
     * Logika: fallback ke tahun berjalan bila filter kosong, sehingga halaman
     * selalu menampilkan data periode berjalan tanpa parameter.
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
     * Logika: fallback ke bulan berjalan bila filter kosong.
     *
     * @param  array  $filters  Parameter filter dari request
     * @return int
     */
    public function getDefaultMonth(array $filters): int
    {
        return !empty($filters['month']) ? (int) $filters['month'] : (int) date('m');
    }
}
