<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Attendance;
use App\Models\Sdm\Employee;
use Illuminate\Http\Request;

/**
 * Controller untuk mengelola data lembur karyawan.
 * 
 * Catatan Penting:
 * - Data lembur disimpan dalam tabel attendances dengan status = 'lembur'
 * - Lembur biasanya terjadi di hari Minggu (hari libur)
 * - Rate lembur bisa berbeda-beda (default Rp 50.000/jam, tapi bisa custom)
 * - Perhitungan total lembur: overtime_hours × overtime_rate
 */
class OvertimeController extends Controller
{
    /**
     * Menampilkan daftar data lembur dengan fitur pencarian dan validasi duplikat.
     * 
     * Fitur:
     * - Filter hanya attendance dengan status='lembur'
     * - Pencarian berdasarkan nama atau kode karyawan (search dalam relasi)
     * - Eager loading relasi employee untuk efisiensi query
     * - Sorting berdasarkan tanggal absensi dan waktu input
     * - Kirim data karyawan untuk dropdown form
     * - Kirim existing attendance untuk validasi duplikat di frontend
     */
    public function index(Request $request)
    {
        // Ambil keyword pencarian dari request
        $search = $request->input('search');

        // Query data lembur dengan filter dan relasi
        $overtimes = Attendance::where('status', 'lembur')
            ->with('employee') // Load relasi employee untuk efisiensi
            ->when($search, function ($query, $search) {
                // Cari berdasarkan nama atau kode karyawan (search dalam relasi)
                return $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->latest('attendance_date') // Urutkan berdasarkan tanggal absensi terbaru
            ->latest('created_at') // Jika tanggal sama, urutkan berdasarkan waktu input
            ->paginate(10);

        // Ambil semua karyawan untuk dropdown form
        $employees = Employee::all()->sortBy('name');

        // Ambil data absensi yang sudah ada untuk validasi duplikat di client-side
        // Struktur: { employee_id: { 'YYYY-MM-DD': { id, status } } }
        $existingAttendance = Attendance::select('employee_id', 'attendance_date', 'id', 'status')
            ->get()
            ->groupBy('employee_id') // Group by karyawan
            ->map(function ($items) {
                return $items->mapWithKeys(function ($item) {
                    // Mapping: tanggal => { id, status }
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
     * Menyimpan data lembur baru ke database.
     * 
     * Proses Perhitungan:
     * 1. Ambil semua data dari form (employee_id, attendance_date, overtime_hours, overtime_rate)
     * 2. Set status = 'lembur' untuk identifikasi di tabel attendances
     * 3. Hitung overtime_total = overtime_hours × overtime_rate
     * 4. Simpan ke tabel attendances
     * 
     * Catatan:
     * - Rate lembur bisa berbeda-beda per input (flexible)
     * - Default biasanya Rp 50.000/jam, tapi user bisa input custom
     */
    public function store(Request $request)
    {
        // Ambil semua data dari form
        $data = $request->all();
        
        // Set status = 'lembur' untuk identifikasi
        $data['status'] = 'lembur';
        
        // Hitung total lembur: jam lembur × rate lembur
        $data['overtime_total'] = $request->overtime_hours * $request->overtime_rate;

        // Simpan data lembur ke tabel attendances
        Attendance::create($data);

        return redirect()->route('overtime.index')->with('success', 'Data lembur berhasil ditambahkan!');
    }

    /**
     * Mengupdate data lembur yang sudah ada.
     * 
     * Proses:
     * 1. Terima model Attendance dari route model binding
     * 2. Ambil data update dari form
     * 3. Recalculate overtime_total berdasarkan overtime_hours dan overtime_rate terbaru
     * 4. Update data ke database
     * 
     * Catatan: Route model binding otomatis mencari Attendance by ID
     */
    public function update(Request $request, Attendance $overtime)
    {
        // Ambil data dari form
        $data = $request->all();
        
        // Recalculate: jam lembur × rate lembur
        $data['overtime_total'] = $request->overtime_hours * $request->overtime_rate;

        // Update data lembur
        $overtime->update($data);

        return redirect()->route('overtime.index')->with('success', 'Data lembur berhasil diperbarui!');
    }

    /**
     * Menghapus data lembur secara bulk (multiple selection).
     * 
     * Proses:
     * 1. Ambil array ID yang dipilih dari checkbox
     * 2. Validasi apakah ada data yang dipilih
     * 3. Hapus data dari tabel attendances
     * 
     * Catatan: Bulk delete untuk efisiensi (hapus banyak data sekaligus)
     */
    public function destroy(Request $request)
    {
        // Ambil array ID dari checkbox
        $ids = $request->input('ids');

        // Validasi: pastikan ada data yang dipilih
        if (empty($ids)) {
            return redirect()->route('overtime.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Hapus data lembur by ID
        Attendance::whereIn('id', $ids)->delete();

        return redirect()->route('overtime.index')->with('success', 'Data lembur berhasil dihapus!');
    }
}
