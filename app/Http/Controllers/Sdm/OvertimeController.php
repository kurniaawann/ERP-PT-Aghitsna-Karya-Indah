<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sdm\StoreOvertimeRequest;
use App\Http\Requests\Sdm\UpdateOvertimeRequest;
use App\Models\Sdm\Attendance;
use App\Services\Sdm\OvertimeService;
use Illuminate\Http\Request;

/**
 * Controller for managing employee overtime records.
 *
 * Handles listing, creation, update, and bulk deletion of overtime records.
 * All business logic is delegated to OvertimeService.
 * Overtime data is stored in the attendances table with status = 'lembur'.
 */
class OvertimeController extends Controller
{
    public function __construct(
        private readonly OvertimeService $overtimeService
    ) {}

    /**
     * Display a paginated list of overtime records.
     *
     * Provides data for the overtime index page including:
     * - Paginated overtime records (status = 'lembur')
     * - Employee list for the add/edit forms (searchable select)
     * - Existing attendance data for client-side duplicate validation
     *
     * @param  Request  $request  Incoming request with optional 'search' query parameter
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $overtimes = $this->overtimeService->getPaginatedOvertimes($search);
        $employees = $this->overtimeService->getAllEmployees();
        $existingAttendance = $this->overtimeService->getExistingAttendance();

        return view('pages.sdm.overtime', compact('overtimes', 'employees', 'search', 'existingAttendance'));
    }

    /**
     * Store a new overtime record.
     *
     * Validates input via StoreOvertimeRequest, then delegates to OvertimeService.
     * The service handles the create-or-update logic: if an attendance record
     * already exists for the same employee + date, it updates it with overtime data.
     * Otherwise, it creates a new record with status 'lembur'.
     *
     * @param  StoreOvertimeRequest  $request  Validated store request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreOvertimeRequest $request)
    {
        $this->overtimeService->storeOvertime($request->validated());

        return redirect()->route('overtime.index')->with('success', 'Data lembur berhasil ditambahkan!');
    }

    /**
     * Update an existing overtime record.
     *
     * Uses Route Model Binding to resolve the Attendance instance.
     * Validates input via UpdateOvertimeRequest.
     * The service recalculates overtime_total server-side.
     *
     * @param  UpdateOvertimeRequest  $request   Validated update request
     * @param  Attendance             $overtime  Attendance model instance from route binding
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateOvertimeRequest $request, Attendance $overtime)
    {
        $this->overtimeService->updateOvertime($overtime, $request->validated());

        return redirect()->route('overtime.index')->with('success', 'Data lembur berhasil diperbarui!');
    }

    /**
     * Bulk delete overtime records by their IDs.
     *
     * @param  Request  $request  Request containing 'ids' array of attendance IDs
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('overtime.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        $this->overtimeService->deleteOvertimes($ids);

        return redirect()->route('overtime.index')->with('success', 'Data lembur berhasil dihapus!');
    }
}
