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
    public function index(Request $request)
    {
        $search = $request->input('search');

        $overtimes = Attendance::where('status', 'lembur')
            ->with('employee')
            ->when($search, function ($query, $search) {
                return $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->latest('attendance_date')
            ->latest('created_at')
            ->paginate(10);

        $employees = Employee::all()->sortBy('name');

        // Get existing attendance data untuk validasi duplikat di client-side
        $existingAttendance = Attendance::select('employee_id', 'attendance_date', 'id', 'status')
            ->get()
            ->groupBy('employee_id')
            ->map(function ($items) {
                return $items->mapWithKeys(function ($item) {
                    return [
                        \Carbon\Carbon::parse($item->attendance_date)->format('Y-m-d') => [
                            'id' => $item->id,
                            'status' => $item->status
                        ]
                    ];
                });
            });

        return view('pages.sdm.overtime', compact('overtimes', 'employees', 'search', 'existingAttendance'));
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
