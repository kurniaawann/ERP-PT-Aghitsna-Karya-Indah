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
            ->paginate(10);

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
        // Ambil semua input dari form dan simpan ke variable $data
        // all() mengembalikan array associative dengan semua field dari form
        // Field: employee_id, attendance_date, overtime_hours, overtime_rate, notes
        $data = $request->all();

        // Set status = 'lembur' untuk identifikasi di tabel attendances
        // Ini penting karena tabel attendances shared untuk absensi regular dan lembur
        $data['status'] = 'lembur';

        // Hitung total uang lembur: jam lembur × rate per jam
        // overtime_hours dari input (misal: 4 jam)
        // overtime_rate dari input (misal: Rp 50.000)
        // overtime_total = 4 × 50000 = Rp 200.000
        $data['overtime_total'] = $request->overtime_hours * $request->overtime_rate;

        // Insert data lembur ke tabel attendances
        // create() akan insert record baru dan return model instance
        Attendance::create($data);

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
