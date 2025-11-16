<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Attendance;
use App\Models\Sdm\Employee;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $overtimes = Attendance::where('status', 'lembur')
            ->with('employee')
            ->latest('attendance_date')
            ->paginate(10);
        $employees = Employee::active()->get();
        return view('pages.sdm.overtime', compact('overtimes', 'employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $data['status'] = 'lembur';
        $data['overtime_total'] = $request->overtime_hours * $request->overtime_rate;

        Attendance::create($data);

        return redirect()->route('overtime.index')->with('success', 'Data lembur berhasil ditambahkan!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Attendance $overtime)
    {
        $data = $request->all();
        $data['overtime_total'] = $request->overtime_hours * $request->overtime_rate;

        $overtime->update($data);

        return redirect()->route('overtime.index')->with('success', 'Data lembur berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('overtime.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        Attendance::whereIn('id', $ids)->where('status', 'lembur')->delete();

        return redirect()->route('overtime.index')->with('success', 'Data lembur berhasil dihapus!');
    }
}
