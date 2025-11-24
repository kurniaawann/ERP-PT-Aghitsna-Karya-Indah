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
            ->latest('created_at')
            ->paginate(10);

        $employees = Employee::all()->sortBy('name');

        // Get existing attendance data untuk validasi duplikat di client-side
        $existingAttendance = Attendance::select('employee_id', 'attendance_date')
            ->get()
            ->groupBy('employee_id')
            ->map(function ($items) {
                return $items->pluck('attendance_date')->map(function ($date) {
                    return \Carbon\Carbon::parse($date)->format('Y-m-d');
                })->toArray();
            });

        return view('pages.sdm.attendance', compact('attendances', 'employees', 'search', 'existingAttendance'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,employee_code',
            'start_date' => 'required|date|before_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date|before_or_equal:today',
            'status' => 'required|in:Hadir,Sakit,Izin,Alfa,Cuti',
            'notes' => 'nullable|string|max:255',
        ], [
            'start_date.before_or_equal' => 'Tanggal mulai tidak boleh lebih dari hari ini.',
            'end_date.before_or_equal' => 'Tanggal akhir tidak boleh lebih dari hari ini.',
            'end_date.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal mulai.',
        ]);

        $employeeIds = $request->input('employee_ids', []);
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);

        // VALIDASI: Cek dulu apakah ada duplikat SEBELUM insert
        $duplicates = [];
        foreach ($employeeIds as $employeeId) {
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $existing = Attendance::where('employee_id', $employeeId)
                    ->where('attendance_date', $currentDate->format('Y-m-d'))
                    ->first();

                if ($existing) {
                    $employee = \App\Models\Sdm\Employee::where('employee_code', $employeeId)->first();
                    $duplicates[] = sprintf(
                        '%s pada tanggal %s (Status: %s)',
                        $employee->name ?? $employeeId,
                        $currentDate->format('d-m-Y'),
                        $existing->status
                    );
                }
                $currentDate->addDay();
            }
        }

        // Jika ada duplikat, TOLAK TOTAL dan tampilkan error
        if (count($duplicates) > 0) {
            $errorMessage = 'Karyawan berikut sudah memiliki absensi: ';

            // Batasi tampilan maksimal 5 duplikat pertama
            $displayDuplicates = array_slice($duplicates, 0, 5);
            $errorMessage .= implode('; ', $displayDuplicates);

            if (count($duplicates) > 5) {
                $errorMessage .= sprintf(' dan %d lainnya', count($duplicates) - 5);
            }

            $errorMessage .= '. Silakan hapus atau edit data yang sudah ada.';

            return back()->with('error', $errorMessage);
        }

        // Jika tidak ada duplikat, lanjutkan INSERT
        $totalInserted = 0;
        foreach ($employeeIds as $employeeId) {
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                Attendance::create([
                    'employee_id' => $employeeId,
                    'attendance_date' => $currentDate->format('Y-m-d'),
                    'status' => $request->status,
                    'notes' => $request->notes,
                ]);
                $totalInserted++;
                $currentDate->addDay();
            }
        }

        // Hitung jumlah hari
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $message = sprintf(
            'Berhasil menambahkan %d record absensi untuk %d karyawan selama %d hari (%s s/d %s).',
            $totalInserted,
            count($employeeIds),
            $totalDays,
            $startDate->format('d-m-Y'),
            $endDate->format('d-m-Y')
        );

        return redirect()->route('attendance.index')->with('success', $message);
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
