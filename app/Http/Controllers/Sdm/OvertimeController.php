<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Attendance;
use App\Models\Sdm\Employee;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    public function index(Request $request)
    {
        // Ambil keyword pencarian dari request (untuk filter nama atau kode karyawan)
        $search = $request->input('search');

        // Query data lembur dari tabel attendances dengan filter status='lembur'
        // Note: Data lembur disimpan di tabel attendances dengan status khusus 'lembur'
        $overtimes = Attendance::where('status', 'lembur')
            // Eager load relasi employee untuk efisiensi query (hindari N+1 problem)
            ->with('employee')
            // Filter berdasarkan pencarian jika parameter $search ada
            ->when($search, function ($query, $search) {
                // Filter dengan whereHas untuk search dalam relasi employee
                return $query->whereHas('employee', function ($q) use ($search) {
                    // Cari di kolom name karyawan dengan LIKE (partial match)
                    $q->where('name', 'like', "%{$search}%")
                        // ATAU cari di kolom employee_code dengan LIKE (partial match)
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            // Urutkan berdasarkan attendance_date descending (tanggal lembur terbaru di atas)
            ->latest('attendance_date')
            // Jika tanggal sama, urutkan berdasarkan created_at descending (yang dibuat terakhir di atas)
            ->latest('created_at')
            // Pagination 10 data per halaman
            ->paginate(15);

        // Ambil semua karyawan dari database untuk dropdown form
        // all() mengambil semua record tanpa filter
        // sortBy('name') mengurutkan collection berdasarkan nama karyawan (ascending alphabetical)
        $employees = Employee::all()->sortBy('name');

        // Ambil data absensi yang sudah ada untuk validasi duplikat di client-side (frontend)
        // Pilih kolom: employee_id, attendance_date, id, dan status
        $existingAttendance = Attendance::select('employee_id', 'attendance_date', 'id', 'status')
            // Ambil semua data absensi (tidak hanya lembur, tapi semua status)
            ->get()
            // Group collection berdasarkan employee_id
            // Hasilnya: ['EMP001' => collection1, 'EMP002' => collection2]
            ->groupBy('employee_id')
            // Transform setiap group karyawan menjadi mapping tanggal => {id, status}
            ->map(function ($items) {
                // mapWithKeys untuk custom key-value mapping
                return $items->mapWithKeys(function ($item) {
                    // Key = tanggal format Y-m-d (misal: '2025-01-01')
                    // Value = array associative dengan id dan status
                    return [
                        \Carbon\Carbon::parse($item->attendance_date)->format('Y-m-d') => [
                            'id' => $item->id, // ID attendance untuk update/delete
                            'status' => $item->status // Status untuk display (Hadir/Lembur/Sakit/dll)
                        ]
                    ];
                });
            });

        // Return view dengan data: overtimes (data lembur + pagination), employees (dropdown),
        // search (maintain keyword), existingAttendance (validasi duplikat)
        return view('pages.sdm.overtime', compact('overtimes', 'employees', 'search', 'existingAttendance'));
    }

    public function store(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'attendance_date' => 'required|date',
            'overtime_hours' => 'required|numeric|min:0.5|max:24',
            'overtime_rate' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        // Hitung total uang lembur: jam lembur × rate per jam
        $overtimeTotal = (float) $request->overtime_hours * (int) $request->overtime_rate;

        // Cek apakah karyawan sudah punya record di tanggal tersebut (misalnya status "hadir")
        $existingAttendance = Attendance::where('employee_id', $request->employee_id)
            ->where('attendance_date', $request->attendance_date)
            ->first();

        if ($existingAttendance) {
            // Jika sudah ada record (biasanya status "hadir"), UPDATE dengan data lembur
            // Ubah status menjadi "lembur" dan isi kolom overtime
            $existingAttendance->update([
                'status' => 'lembur',
                'overtime_hours' => (float) $request->overtime_hours,
                'overtime_rate' => (int) $request->overtime_rate,
                'overtime_total' => (int) $overtimeTotal,
                'notes' => $request->notes,
            ]);

            // \Log::info('Overtime - Updated existing attendance', [
            //     'employee_id' => $request->employee_id,
            //     'date' => $request->attendance_date,
            //     'overtime_hours' => $request->overtime_hours,
            //     'overtime_rate' => $request->overtime_rate,
            //     'overtime_total' => $overtimeTotal,
            //     'old_status' => $existingAttendance->getOriginal('status'),
            //     'record_id' => $existingAttendance->id,
            // ]);
        } else {
            // Jika belum ada record, CREATE record baru dengan status "lembur"
            $created = Attendance::create([
                'employee_id' => $request->employee_id,
                'attendance_date' => $request->attendance_date,
                'status' => 'lembur',
                'overtime_hours' => (float) $request->overtime_hours,
                'overtime_rate' => (int) $request->overtime_rate,
                'overtime_total' => (int) $overtimeTotal,
                'notes' => $request->notes,
            ]);

            // \Log::info('Overtime - Created new attendance', [
            //     'employee_id' => $request->employee_id,
            //     'date' => $request->attendance_date,
            //     'overtime_hours' => $request->overtime_hours,
            //     'overtime_rate' => $request->overtime_rate,
            //     'overtime_total' => $overtimeTotal,
            //     'record_id' => $created->id,
            // ]);
        }

        // Redirect ke halaman index overtime dengan flash message sukses
        return redirect()->route('overtime.index')->with('success', 'Data lembur berhasil ditambahkan!');
    }

    public function update(Request $request, Attendance $overtime)
    {
        // Parameter $overtime sudah otomatis di-inject oleh Laravel Route Model Binding
        // Laravel otomatis mencari Attendance by ID dari route parameter
        // Ambil semua input dari form edit dan simpan ke variable $data
        $data = $request->all();

        // Recalculate (hitung ulang) total uang lembur berdasarkan data terbaru
        // overtime_hours dari input form (misal: user ubah dari 4 jam menjadi 5 jam)
        // overtime_rate dari input form (misal: user ubah dari Rp 50.000 menjadi Rp 60.000)
        // Contoh: 5 jam × Rp 60.000 = Rp 300.000
        $data['overtime_total'] = $request->overtime_hours * $request->overtime_rate;

        // Update data lembur ke database dengan data terbaru
        // update() akan mengubah record yang sudah ada berdasarkan ID
        $overtime->update($data);

        // Redirect ke halaman index overtime dengan flash message sukses
        return redirect()->route('overtime.index')->with('success', 'Data lembur berhasil diperbarui!');
    }

    public function destroy(Request $request)
    {
        // Ambil array ID dari input dengan nama 'ids' (dari checkbox selection)
        // ids berisi array ID attendance yang dipilih, misal: [1, 5, 10]
        $ids = $request->input('ids');

        // Validasi: cek apakah $ids kosong (empty() return true jika null, [], atau '')
        if (empty($ids)) {
            // Redirect ke halaman index dengan flash message error
            return redirect()->route('overtime.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Hapus data lembur dari tabel attendances berdasarkan ID
        // whereIn('id', $ids) akan match semua record dengan id di dalam array
        // delete() akan menghapus record tersebut dari database
        Attendance::whereIn('id', $ids)->delete();

        // Redirect ke halaman index dengan flash message sukses
        return redirect()->route('overtime.index')->with('success', 'Data lembur berhasil dihapus!');
    }
}
