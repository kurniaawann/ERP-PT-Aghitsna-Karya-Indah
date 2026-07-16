<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sdm\StoreAttendanceRequest;
use App\Http\Requests\Sdm\UpdateAttendanceRequest;
use App\Models\Sdm\Attendance;
use App\Services\Sdm\AttendanceService;
use Illuminate\Http\Request;

/**
 * Controller for managing employee attendance records.
 *
 * Handles listing, bulk creation, single update, and bulk deletion
 * of attendance records. All business logic is delegated to AttendanceService.
 */
class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    /**
     * Display a paginated list of attendance records.
     *
     * @param  Request  $request  Incoming request with optional 'search' query parameter
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $attendances = $this->attendanceService->getPaginatedAttendances($search);
        $employees = $this->attendanceService->getAllEmployees();
        $existingAttendance = $this->attendanceService->getExistingAttendance();

        return view('pages.sdm.attendance', compact('attendances', 'employees', 'search', 'existingAttendance'));
    }

    /**
     * Store bulk attendance records for multiple employees across a date range.
     *
     * Validates input via StoreAttendanceRequest, checks for duplicates,
     * and creates attendance records if no duplicates exist.
     *
     * @param  StoreAttendanceRequest  $request  Validated store request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreAttendanceRequest $request)
    {
        $employeeIds = $request->validated('employee_ids');
        $startDate = \Illuminate\Support\Carbon::parse($request->start_date);
        $endDate = \Illuminate\Support\Carbon::parse($request->end_date);

        $duplicates = $this->attendanceService->findDuplicates($employeeIds, $startDate, $endDate);

        if (count($duplicates) > 0) {
            $errorMessage = $this->attendanceService->buildDuplicateErrorMessage($duplicates);
            return back()->with('error', $errorMessage);
        }

        $totalInserted = $this->attendanceService->bulkCreate(
            $employeeIds,
            $startDate,
            $endDate,
            $request->validated('status'),
            $request->validated('notes')
        );

        $message = $this->attendanceService->buildBulkCreateMessage(
            $totalInserted,
            count($employeeIds),
            $startDate,
            $endDate
        );

        return redirect()->route('attendance.index')->with('success', $message);
    }

    /**
     * Update a single attendance record.
     *
     * Uses Route Model Binding to resolve the Attendance instance.
     * Validates input via UpdateAttendanceRequest.
     *
     * @param  UpdateAttendanceRequest  $request    Validated update request
     * @param  Attendance               $attendance Attendance model instance from route binding
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        $this->attendanceService->updateAttendance($attendance, $request->validated());

        return redirect()->route('attendance.index')->with('success', 'Data absensi berhasil diperbarui!');
    }

    /**
     * Bulk delete attendance records by their IDs.
     *
     * @param  Request  $request  Request containing 'ids' array
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('attendance.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        $this->attendanceService->deleteAttendances($ids);

        return redirect()->route('attendance.index')->with('success', 'Data absensi berhasil dihapus!');
    }
}
