<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Attendance;
use App\Models\Sdm\Employee;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        // Ambil keyword pencarian dari request (untuk filter nama karyawan, kode, atau tanggal)
        $search = $request->input('search');

        // Mulai query untuk mengambil data absensi
        $attendances = Attendance::with('employee') // Eager load relasi employee untuk efisiensi (hindari N+1 problem)
            // Filter berdasarkan pencarian jika parameter $search ada
            ->when($search, function ($query, $search) {
                // Filter dengan whereHas untuk search dalam relasi employee
                return $query->whereHas('employee', function ($q) use ($search) {
                    // Cari di kolom name karyawan dengan LIKE (partial match)
                    $q->where('name', 'like', "%{$search}%")
                        // ATAU cari di kolom employee_code dengan LIKE (partial match)
                        ->orWhere('employee_code', 'like', "%{$search}%");
                })
                    // ATAU cari di kolom attendance_date dengan LIKE (partial match untuk format tanggal)
                    ->orWhere('attendance_date', 'like', "%{$search}%");
            })
            // Urutkan berdasarkan attendance_date descending (tanggal terbaru di atas)
            ->latest('attendance_date')
            // Jika tanggal sama, urutkan berdasarkan created_at descending (yang dibuat terakhir di atas)
            ->latest('created_at')
            // Pagination 10 data per halaman
            ->paginate(15);

        // Ambil semua data karyawan dari database
        // all() mengambil semua record tanpa filter
        // sortBy('name') mengurutkan collection berdasarkan nama karyawan (untuk dropdown form)
        $employees = Employee::all()->sortBy('name');

        // Ambil data absensi yang sudah ada untuk validasi duplikat di frontend
        // Pilih hanya kolom employee_id dan attendance_date untuk efisiensi
        $existingAttendance = Attendance::select('employee_id', 'attendance_date')
            // Ambil semua data absensi
            ->get()
            // Group collection berdasarkan employee_id
            // Hasilnya: ['EMP001' => [record1, record2], 'EMP002' => [record3, record4]]
            ->groupBy('employee_id')
            // Transform setiap group karyawan menjadi array tanggal
            ->map(function ($items) {
                // Ambil hanya field attendance_date dari setiap item
                return $items->pluck('attendance_date')
                    // Transform setiap tanggal ke format Y-m-d menggunakan Carbon
                    ->map(function ($date) {
                    // Parse tanggal dengan Carbon dan format ke Y-m-d (misal: 2025-01-01)
                    return \Carbon\Carbon::parse($date)->format('Y-m-d');
                })
                    // Convert collection ke array biasa
                    ->toArray();
            });

        // Return view dengan data attendances (absensi + pagination), employees (untuk dropdown),
        // search (untuk maintain keyword di input), dan existingAttendance (untuk validasi duplikat)
        return view('pages.sdm.attendance', compact('attendances', 'employees', 'search', 'existingAttendance'));
    }

    public function store(Request $request)
    {
        $employeeIds = $request->input('employee_ids', []);
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);

        $totalInserted = 0;
        $totalSkipped = 0;
        $skippedDetails = [];

        foreach ($employeeIds as $employeeId) {
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $exists = Attendance::where('employee_id', $employeeId)
                    ->where('attendance_date', $currentDate->format('Y-m-d'))
                    ->exists();

                if ($exists) {
                    if ($totalSkipped < 5) {
                        $employee = Employee::where('employee_code', $employeeId)->first();
                        $skippedDetails[] = sprintf(
                            '%s pada tanggal %s',
                            $employee->name ?? $employeeId,
                            $currentDate->format('d-m-Y')
                        );
                    }
                    $totalSkipped++;
                } else {
                    Attendance::create([
                        'employee_id' => $employeeId,
                        'attendance_date' => $currentDate->format('Y-m-d'),
                        'status' => $request->status,
                        'notes' => $request->notes,
                    ]);
                    $totalInserted++;
                }

                $currentDate->addDay();
            }
        }

        $totalDays = $startDate->diffInDays($endDate) + 1;

        if ($totalInserted > 0 && $totalSkipped > 0) {
            $message = sprintf(
                'Berhasil menambahkan %d record absensi. %d record dilewati karena sudah ada.',
                $totalInserted,
                $totalSkipped
            );
            if (count($skippedDetails) > 0) {
                $message .= ' (' . implode('; ', $skippedDetails) . ')';
                if ($totalSkipped > 5) {
                    $message .= sprintf(' dan %d lainnya', $totalSkipped - 5);
                }
            }
        } elseif ($totalInserted > 0) {
            $message = sprintf(
                'Berhasil menambahkan %d record absensi untuk %d karyawan selama %d hari (%s s/d %s).',
                $totalInserted,
                count($employeeIds),
                $totalDays,
                $startDate->format('d-m-Y'),
                $endDate->format('d-m-Y')
            );
        } else {
            $message = 'Tidak ada data absensi baru yang ditambahkan. Semua data sudah ada sebelumnya.';
        }

        return redirect()->route('attendance.index')->with('success', $message);
    }

    public function update(Request $request, Attendance $attendance)
    {
        // Parameter $attendance sudah otomatis di-inject oleh Laravel Route Model Binding
        // Laravel otomatis mencari Attendance by ID dari route parameter dan memasukkannya ke variable ini
        // Update semua field yang ada di request ke model attendance
        // all() mengambil semua input dari form
        $attendance->update($request->all());

        // Redirect ke halaman index attendance dengan flash message sukses
        return redirect()->route('attendance.index')->with('success', 'Data absensi berhasil diperbarui!');
    }

    public function destroy(Request $request)
    {
        // Ambil array ID dari input dengan nama 'ids' (dari checkbox selection)
        // input('ids') mengembalikan array ID yang dipilih, atau null jika tidak ada
        $ids = $request->input('ids');

        // Validasi: cek apakah $ids kosong (empty() return true jika null, [], atau '')
        if (empty($ids)) {
            // Redirect ke halaman index dengan flash message error
            return redirect()->route('attendance.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Hapus semua data absensi yang ID-nya ada dalam array $ids
        // whereIn('id', $ids) akan match semua record dengan id di dalam array
        // delete() akan menghapus record tersebut dari database
        Attendance::whereIn('id', $ids)->delete();

        // Redirect ke halaman index dengan flash message sukses
        return redirect()->route('attendance.index')->with('success', 'Data absensi berhasil dihapus!');
    }
}
