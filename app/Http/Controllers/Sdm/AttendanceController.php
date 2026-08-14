<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sdm\StoreAttendanceRequest;
use App\Http\Requests\Sdm\UpdateAttendanceRequest;
use App\Models\Sdm\Attendance;
use App\Services\Sdm\AttendanceService;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

/**
 * Controller untuk mengelola data absensi karyawan.
 *
 * Menangani penampilan daftar, pembuatan massal, pembaruan tunggal,
 * dan penghapusan massal data absensi. Seluruh logika bisnis didelegasikan ke AttendanceService.
 */
class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    /**
     * Menampilkan daftar data absensi dengan paginasi.
     *
     * @param  Request  $request  Permintaan masuk dengan parameter query 'search', 'month', 'year', dan 'week_number' opsional
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month') ? (int) $request->input('month') : null;
        $year = $request->input('year') ? (int) $request->input('year') : null;
        $weekNumber = $request->input('week_number') ? (int) $request->input('week_number') : null;

        $attendances = $this->attendanceService->getPaginatedAttendances($search, $month, $year, $weekNumber);
        $employees = $this->attendanceService->getAllEmployees();
        $existingAttendance = $this->attendanceService->getExistingAttendance();

        return view('pages.sdm.attendance', compact('attendances', 'employees', 'search', 'existingAttendance', 'month', 'year', 'weekNumber'));
    }

    /**
     * Menyimpan data absensi massal untuk beberapa karyawan dalam rentang tanggal.
     *
     * Memvalidasi input melalui StoreAttendanceRequest, memeriksa duplikat,
     * dan membuat data absensi jika tidak ada duplikat.
     *
     * @param  StoreAttendanceRequest  $request  Permintaan penyimpanan yang sudah divalidasi
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreAttendanceRequest $request)
    {
        $employeeIds = $request->validated('employee_ids');
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $duplicates = $this->attendanceService->findDuplicates($employeeIds, $startDate, $endDate);

        if (count($duplicates) > 0) {
            $errorMessage = $this->attendanceService->buildDuplicateErrorMessage($duplicates);
            return back()->with('error', $errorMessage);
        }

        try {
            $totalInserted = $this->attendanceService->bulkCreate(
                $employeeIds,
                $startDate,
                $endDate,
                $request->validated('status'),
                $request->validated('notes')
            );
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = $this->attendanceService->buildBulkCreateMessage(
            $totalInserted,
            count($employeeIds),
            $startDate,
            $endDate
        );

        return redirect()->route('attendance.index')->with('success', $message);
    }

    /**
     * Memperbarui satu data absensi.
     *
     * Menggunakan Route Model Binding untuk menyelesaikan instance Attendance.
     * Memvalidasi input melalui UpdateAttendanceRequest.
     *
     * @param  UpdateAttendanceRequest  $request    Permintaan pembaruan yang sudah divalidasi
     * @param  Attendance               $attendance Instance model Attendance dari route binding
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        try {
            $this->attendanceService->updateAttendance($attendance, $request->validated());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('attendance.index')->with('success', 'Data absensi berhasil diperbarui!');
    }

    /**
     * Menghapus data absensi secara massal berdasarkan ID.
     *
     * @param  Request  $request  Permintaan yang berisi array 'ids'
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('attendance.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        try {
            $this->attendanceService->deleteAttendances($ids);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('attendance.index')->with('success', 'Data absensi berhasil dihapus!');
    }
}
