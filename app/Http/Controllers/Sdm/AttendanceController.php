<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Attendance;
use App\Models\Sdm\Employee;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Menampilkan halaman daftar absensi dengan fitur pencarian dan pagination.
     * 
     * Fitur:
     * - Pencarian berdasarkan nama karyawan, kode karyawan, atau tanggal absensi
     * - Menampilkan data absensi dengan relasi employee
     * - Pagination 10 data per halaman
     * - Sorting berdasarkan tanggal absensi terbaru
     */
    public function index(Request $request)
    {
        // Ambil keyword pencarian dari request
        $search = $request->input('search');

        // Query data absensi dengan relasi employee
        $attendances = Attendance::with('employee')
            ->when($search, function ($query, $search) {
                // Filter berdasarkan nama karyawan, kode karyawan, atau tanggal
                return $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                })->orWhere('attendance_date', 'like', "%{$search}%");
            })
            ->latest('attendance_date') // Urutkan berdasarkan tanggal terbaru
            ->latest('created_at') // Jika tanggal sama, urutkan berdasarkan waktu input
            ->paginate(10);

        // Ambil semua data karyawan dan urutkan berdasarkan nama (untuk dropdown)
        $employees = Employee::all()->sortBy('name');

        // Ambil data absensi yang sudah ada untuk validasi duplikat di frontend
        // Format: ['EMP001' => ['2025-01-01', '2025-01-02'], 'EMP002' => [...]]
        $existingAttendance = Attendance::select('employee_id', 'attendance_date')
            ->get()
            ->groupBy('employee_id') // Group berdasarkan karyawan
            ->map(function ($items) {
                // Mapping tanggal ke format Y-m-d untuk setiap karyawan
                return $items->pluck('attendance_date')->map(function ($date) {
                    return \Carbon\Carbon::parse($date)->format('Y-m-d');
                })->toArray();
            });

        return view('pages.sdm.attendance', compact('attendances', 'employees', 'search', 'existingAttendance'));
    }

    /**
     * Menyimpan data absensi baru secara bulk (banyak karyawan & tanggal sekaligus).
     * 
     * Proses:
     * 1. Validasi input (karyawan, tanggal, status)
     * 2. Cek duplikasi data sebelum insert
     * 3. Jika ada duplikat -> tolak dan tampilkan error
     * 4. Jika tidak ada duplikat -> insert semua data
     * 
     * Fitur:
     * - Bulk insert untuk multiple karyawan
     * - Range tanggal (dari tanggal mulai sampai tanggal akhir)
     * - Validasi duplikasi ketat (tidak boleh ada data yang sama)
     * - Pesan error detail jika ada duplikat
     */
    public function store(Request $request)
    {
        // Validasi input dari form
        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1', // Minimal 1 karyawan harus dipilih
            'employee_ids.*' => 'exists:employees,employee_code', // Kode karyawan harus ada di database
            'start_date' => 'required|date|before_or_equal:today', // Tanggal mulai tidak boleh masa depan
            'end_date' => 'required|date|after_or_equal:start_date|before_or_equal:today', // Tanggal akhir >= start_date
            'status' => 'required|in:Hadir,Sakit,Izin,Alfa,Cuti', // Status harus salah satu dari pilihan
            'notes' => 'nullable|string|max:255', // Catatan opsional, maksimal 255 karakter
        ], [
            // Custom error message dalam bahasa Indonesia
            'start_date.before_or_equal' => 'Tanggal mulai tidak boleh lebih dari hari ini.',
            'end_date.before_or_equal' => 'Tanggal akhir tidak boleh lebih dari hari ini.',
            'end_date.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal mulai.',
        ]);

        // Ambil data dari request
        $employeeIds = $request->input('employee_ids', []);
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);

        // TAHAP 1: Validasi duplikasi - cek semua kombinasi karyawan + tanggal
        // Jika ada yang sudah exist, kumpulkan ke array $duplicates
        $duplicates = [];
        foreach ($employeeIds as $employeeId) {
            $currentDate = $startDate->copy();
            // Loop setiap tanggal dari start_date sampai end_date
            while ($currentDate->lte($endDate)) {
                // Cek apakah kombinasi karyawan + tanggal sudah ada di database
                $existing = Attendance::where('employee_id', $employeeId)
                    ->where('attendance_date', $currentDate->format('Y-m-d'))
                    ->first();

                if ($existing) {
                    // Jika sudah ada, simpan info duplikat untuk ditampilkan ke user
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

        // TAHAP 2: Jika ada duplikat, tolak semua insert dan kembalikan dengan error
        if (count($duplicates) > 0) {
            $errorMessage = 'Karyawan berikut sudah memiliki absensi: ';

            // Batasi tampilan error maksimal 5 item pertama (agar tidak terlalu panjang)
            $displayDuplicates = array_slice($duplicates, 0, 5);
            $errorMessage .= implode('; ', $displayDuplicates);

            // Jika duplikat lebih dari 5, tampilkan jumlahnya saja
            if (count($duplicates) > 5) {
                $errorMessage .= sprintf(' dan %d lainnya', count($duplicates) - 5);
            }

            $errorMessage .= '. Silakan hapus atau edit data yang sudah ada.';

            return back()->with('error', $errorMessage);
        }

        // TAHAP 3: Jika tidak ada duplikat, lakukan bulk insert
        $totalInserted = 0;
        foreach ($employeeIds as $employeeId) {
            $currentDate = $startDate->copy();
            // Loop setiap tanggal dan insert ke database
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

        // Buat pesan sukses dengan informasi detail
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
     * Mengupdate data absensi yang sudah ada.
     * 
     * Proses:
     * - Menerima data absensi dari form edit
     * - Update semua field yang dikirim dari request
     * - Redirect kembali ke halaman index dengan pesan sukses
     * 
     * Catatan: Route model binding otomatis mencari data berdasarkan ID
     */
    public function update(Request $request, Attendance $attendance)
    {
        // Update semua field yang dikirim dari request
        $attendance->update($request->all());

        return redirect()->route('attendance.index')->with('success', 'Data absensi berhasil diperbarui!');
    }

    /**
     * Menghapus data absensi secara bulk (multiple selection).
     * 
     * Proses:
     * 1. Ambil array ID yang dipilih dari checkbox
     * 2. Validasi apakah ada data yang dipilih
     * 3. Hapus semua data berdasarkan ID yang dipilih
     * 
     * Fitur:
     * - Bulk delete (hapus banyak data sekaligus)
     * - Validasi pemilihan data
     */
    public function destroy(Request $request)
    {
        // Ambil array ID dari checkbox yang dipilih
        $ids = $request->input('ids');

        // Validasi: pastikan ada data yang dipilih
        if (empty($ids)) {
            return redirect()->route('attendance.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Hapus semua data absensi berdasarkan ID yang dipilih
        Attendance::whereIn('id', $ids)->delete();

        return redirect()->route('attendance.index')->with('success', 'Data absensi berhasil dihapus!');
    }
}
