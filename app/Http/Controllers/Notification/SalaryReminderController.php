<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Services\Notification\SalaryReminderService;
use Illuminate\Http\Request;

/**
 * Controller untuk mengelola halaman Reminder Gaji Karyawan.
 *
 * Controller ini hanya menangani request dan response HTTP.
 * Business logic didelegasikan ke SalaryReminderService.
 * Halaman ini bersifat read-only (hanya menampilkan data).
 */
class SalaryReminderController extends Controller
{
    public function __construct(
        private readonly SalaryReminderService $service
    ) {}

    /**
     * Menampilkan halaman laporan reminder gaji karyawan.
     *
     * Menampilkan dua jenis data:
     * 1. Salary Reminder - data dari tabel salary_reminders (payroll sudah dibuat)
     * 2. Attendance Reminder - karyawan yang sudah absen minggu 1-4 tapi payroll belum dibuat
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Ambil parameter filter
        $filters = $request->only(['month', 'year', 'status', 'search']);

        // Mendapatkan data salary reminder dengan paginasi
        $reminders = $this->service->getPaginatedReminders($filters);

        // Mendapatkan statistik ringkasan
        $stats = $this->service->getSummaryStats($filters);

        // Mendapatkan attendance reminders (karyawan tanpa payroll)
        $filterMonth = $this->service->getDefaultMonth($filters);
        $filterYear = $this->service->getDefaultYear($filters);
        $attendanceReminders = $this->service->getAttendanceReminders($filterMonth, $filterYear);

        return view('pages.notification.salary-reminder', [
            'reminders' => $reminders,
            'totalReminders' => $stats['total'],
            'totalDraft' => $stats['draft'],
            'totalPaid' => $stats['paid'],
            'attendanceReminders' => $attendanceReminders,
        ]);
    }
}
