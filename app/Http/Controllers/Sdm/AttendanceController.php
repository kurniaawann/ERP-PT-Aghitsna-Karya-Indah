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
        // Ambil array employee_ids dari request (default empty array jika tidak ada)
        $employeeIds = $request->input('employee_ids', []);
        // Parse start_date dari string menjadi Carbon instance untuk manipulasi tanggal
        $startDate = \Carbon\Carbon::parse($request->start_date);
        // Parse end_date dari string menjadi Carbon instance
        $endDate = \Carbon\Carbon::parse($request->end_date);

        // TAHAP 1: Validasi duplikasi - cek semua kombinasi karyawan + tanggal
        // Inisialisasi array kosong untuk menampung info duplikat
        $duplicates = [];
        // Loop setiap karyawan yang dipilih dari checkbox
        foreach ($employeeIds as $employeeId) {
            // Buat copy dari startDate untuk iterasi tanggal (agar startDate asli tidak berubah)
            $currentDate = $startDate->copy();
            // Loop setiap tanggal dari start_date sampai end_date (inclusive)
            // lte() = less than or equal (<=)
            while ($currentDate->lte($endDate)) {
                // Cek di database apakah kombinasi employee_id + attendance_date sudah ada
                // where('employee_id', $employeeId) = filter by karyawan
                // where('attendance_date', ...) = filter by tanggal
                // first() = ambil record pertama atau null jika tidak ada
                $existing = Attendance::where('employee_id', $employeeId)
                    ->where('attendance_date', $currentDate->format('Y-m-d'))
                    ->first();

                // Jika data sudah ada (existing bukan null)
                if ($existing) {
                    // Ambil data karyawan dari database berdasarkan employee_code
                    $employee = Employee::where('employee_code', $employeeId)->first();
                    // Buat pesan error detail dengan sprintf (formatting string)
                    // Format: "Nama Karyawan pada tanggal DD-MM-YYYY (Status: Hadir)"
                    $duplicates[] = sprintf(
                        '%s pada tanggal %s (Status: %s)',
                        $employee->name ?? $employeeId, // Gunakan nama karyawan, atau employee_code jika nama null
                        $currentDate->format('d-m-Y'), // Format tanggal ke DD-MM-YYYY
                        $existing->status // Status absensi yang sudah ada
                    );
                }
                // Tambah 1 hari ke currentDate untuk iterasi berikutnya
                $currentDate->addDay();
            }
        }

        // TAHAP 2: Jika ada duplikat, tolak semua insert dan kembalikan dengan error
        // count($duplicates) > 0 berarti ada minimal 1 duplikat
        if (count($duplicates) > 0) {
            // Inisialisasi pesan error
            $errorMessage = 'Karyawan berikut sudah memiliki absensi: ';

            // Batasi tampilan error maksimal 5 item pertama (agar pesan tidak terlalu panjang)
            // array_slice($duplicates, 0, 5) mengambil index 0-4 (5 item pertama)
            $displayDuplicates = array_slice($duplicates, 0, 5);
            // implode('; ', $array) menggabungkan array menjadi string dengan separator '; '
            $errorMessage .= implode('; ', $displayDuplicates);

            // Jika total duplikat lebih dari 5, tampilkan info "dan X lainnya"
            if (count($duplicates) > 5) {
                // sprintf untuk formatting string dengan placeholder %d (integer)
                $errorMessage .= sprintf(' dan %d lainnya', count($duplicates) - 5);
            }

            // Tambahkan petunjuk untuk user
            $errorMessage .= '. Silakan hapus atau edit data yang sudah ada.';

            // Redirect kembali ke halaman sebelumnya dengan flash message error
            return back()->with('error', $errorMessage);
        }

        // TAHAP 3: Jika tidak ada duplikat, lakukan bulk insert
        // Inisialisasi counter untuk menghitung jumlah data yang berhasil disimpan
        $totalInserted = 0;
        // Loop setiap karyawan yang dipilih
        foreach ($employeeIds as $employeeId) {
            // Buat copy dari startDate untuk iterasi tanggal
            $currentDate = $startDate->copy();
            // Loop setiap tanggal dari start_date sampai end_date
            while ($currentDate->lte($endDate)) {
                // Insert data absensi ke database
                Attendance::create([
                    // ID karyawan (employee_code)
                    'employee_id' => $employeeId,
                    // Tanggal absensi (format Y-m-d untuk database)
                    'attendance_date' => $currentDate->format('Y-m-d'),
                    // Status absensi dari form (Hadir/Sakit/Izin/Alfa/Cuti)
                    'status' => $request->status,
                    // Catatan tambahan dari form (bisa null)
                    'notes' => $request->notes,
                ]);
                // Increment counter setiap kali insert berhasil
                $totalInserted++;
                // Tambah 1 hari untuk iterasi berikutnya
                $currentDate->addDay();
            }
        }

        // Buat pesan sukses dengan informasi detail menggunakan sprintf
        // diffInDays() menghitung selisih hari antara start dan end date, +1 karena inclusive
        $totalDays = $startDate->diffInDays($endDate) + 1;
        // sprintf dengan placeholder: %d untuk integer, %s untuk string
        $message = sprintf(
            'Berhasil menambahkan %d record absensi untuk %d karyawan selama %d hari (%s s/d %s).',
            $totalInserted, // Jumlah record yang diinsert
            count($employeeIds), // Jumlah karyawan
            $totalDays, // Jumlah hari (range)
            $startDate->format('d-m-Y'), // Tanggal mulai (format DD-MM-YYYY)
            $endDate->format('d-m-Y') // Tanggal akhir (format DD-MM-YYYY)
        );

        // Redirect ke halaman index attendance dengan flash message sukses
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
