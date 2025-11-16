<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Attendance;
use App\Models\Sdm\Employee;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $attendances = Attendance::with('employee')
            ->when($search, function ($query, $search) {
                return $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                })->orWhere('attendance_date', 'like', "%{$search}%");
            })
            ->latest('attendance_date')
            ->paginate(10);

        $employees = Employee::all()->sortBy('name');
        return view('pages.sdm.attendance', compact('attendances', 'employees', 'search'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $employeeIds = $request->input('employee_ids', []);

        foreach ($employeeIds as $employeeId) {
            Attendance::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'attendance_date' => $request->attendance_date,
                ],
                [
                    'status' => $request->status,
                    'notes' => $request->notes,
                ]
            );
        }

        return redirect()->route('attendance.index')->with('success', 'Data absensi berhasil ditambahkan!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Attendance $attendance)
    {
        $attendance->update($request->all());

        return redirect()->route('attendance.index')->with('success', 'Data absensi berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('attendance.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        Attendance::whereIn('id', $ids)->delete();

        return redirect()->route('attendance.index')->with('success', 'Data absensi berhasil dihapus!');
    }
}
