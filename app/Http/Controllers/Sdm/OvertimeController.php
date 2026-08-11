<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sdm\StoreOvertimeRequest;
use App\Http\Requests\Sdm\UpdateOvertimeRequest;
use App\Models\Sdm\Attendance;
use App\Services\Sdm\OvertimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Controller untuk mengelola data lembur karyawan.
 *
 * Menangani penampilan daftar, pembuatan, pembaruan, dan penghapusan massal data lembur.
 * Seluruh logika bisnis didelegasikan ke OvertimeService.
 * Data lembur disimpan di tabel attendances dengan status = 'lembur'.
 */
class OvertimeController extends Controller
{
    public function __construct(
        private readonly OvertimeService $overtimeService
    ) {}

    /**
     * Menampilkan daftar data lembur dengan paginasi.
     *
     * Menyediakan data untuk halaman indeks lembur meliputi:
     * - Data lembur berpaginasi (status = 'lembur')
     * - Daftar karyawan untuk formulir tambah/edit (select yang dapat dicari)
     * - Data absensi yang sudah ada untuk validasi duplikat di sisi klien
     *
     * @param  Request  $request  Permintaan masuk dengan parameter query 'search' opsional
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $overtimes = $this->overtimeService->getPaginatedOvertimes($search);
        $employees = $this->overtimeService->getAllEmployees();
        $existingAttendance = $this->overtimeService->getExistingAttendance();

        return view('pages.sdm.overtime', compact('overtimes', 'employees', 'search', 'existingAttendance'));
    }

    /**
     * Menyimpan data lembur baru.
     *
     * Memvalidasi input melalui StoreOvertimeRequest, kemudian mendelegasikan ke OvertimeService.
     * Sebelum menyimpan, dipastikan karyawan sudah memiliki absensi dengan
     * status 'hadir' pada tanggal tersebut (lembur hanya untuk yang hadir).
     * Service menangani logika buat-atau-perbarui: jika data absensi sudah ada
     * untuk karyawan + tanggal yang sama, maka akan diperbarui dengan data lembur.
     * Jika tidak, maka akan dibuat data baru dengan status 'lembur'.
     *
     * @param  StoreOvertimeRequest  $request  Permintaan penyimpanan yang sudah divalidasi
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreOvertimeRequest $request)
    {
        if (!$this->overtimeService->hasHadirAttendance($request->employee_id, $request->attendance_date)) {
            return back()->with('error', 'Karyawan belum memiliki absensi dengan status Hadir pada tanggal tersebut. Lembur hanya bisa ditambahkan jika karyawan sudah absen Hadir.');
        }

        try {
            $this->overtimeService->storeOvertime($request->validated());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('overtime.index')->with('success', 'Data lembur berhasil ditambahkan!');
    }

    /**
     * Memperbarui data lembur yang sudah ada.
     *
     * Menggunakan Route Model Binding untuk menyelesaikan instance Attendance.
     * Memvalidasi input melalui UpdateOvertimeRequest.
     * Service menghitung ulang total_lembur di sisi server.
     *
     * @param  UpdateOvertimeRequest  $request   Permintaan pembaruan yang sudah divalidasi
     * @param  Attendance             $overtime  Instance model Attendance dari route binding
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateOvertimeRequest $request, Attendance $overtime)
    {
        $dateChanged = $overtime->employee_id !== $request->employee_id
            || Carbon::parse($overtime->attendance_date)->format('Y-m-d') !== $request->attendance_date;

        if ($dateChanged && !$this->overtimeService->hasHadirAttendance($request->employee_id, $request->attendance_date)) {
            return back()->with('error', 'Karyawan belum memiliki absensi dengan status Hadir pada tanggal tersebut. Lembur hanya bisa dipindahkan ke tanggal di mana karyawan sudah absen Hadir.');
        }

        try {
            $this->overtimeService->updateOvertime($overtime, $request->validated());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('overtime.index')->with('success', 'Data lembur berhasil diperbarui!');
    }

    /**
     * Menghapus data lembur secara massal berdasarkan ID.
     *
     * @param  Request  $request  Permintaan yang berisi array 'ids' berisi ID absensi
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('overtime.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        try {
            $this->overtimeService->deleteOvertimes($ids);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('overtime.index')->with('success', 'Data lembur berhasil dihapus!');
    }
}
