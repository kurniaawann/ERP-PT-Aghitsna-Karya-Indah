<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Employee;
use Illuminate\Http\Request;

/**
 * Controller untuk mengelola data karyawan.
 * 
 * Menangani CRUD (Create, Read, Update, Delete) untuk master data karyawan.
 * Termasuk auto-generate kode karyawan dengan format EMP001, EMP002, dst.
 */
class EmployeeController extends Controller
{
    /**
     * Menampilkan halaman daftar karyawan dengan fitur pencarian.
     * 
     * Fitur:
     * - Pencarian berdasarkan nama atau kode karyawan
     * - Pagination 10 data per halaman
     * - Sorting berdasarkan data terbaru (created_at)
     */
    public function index(Request $request)
    {
        // Ambil keyword pencarian dari request
        $search = $request->input('search');

        // Query data karyawan dengan filter pencarian
        $employees = Employee::when($search, function ($query, $search) {
            // Cari berdasarkan nama atau kode karyawan
            return $query->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%");
        })
            ->latest('created_at') // Urutkan berdasarkan data terbaru
            ->paginate(10);

        return view('pages.sdm.employee', compact('employees', 'search'));
    }


    /**
     * Menyimpan data karyawan baru ke database.
     * 
     * Proses:
     * 1. Ambil semua data dari form
     * 2. Generate kode karyawan otomatis (EMP001, EMP002, dst)
     * 3. Simpan ke database
     * 
     * Catatan:
     * - Kode karyawan di-generate otomatis oleh sistem
     * - Format kode: EMP + nomor urut 3 digit (EMP001, EMP002, ...)
     */
    public function store(Request $request)
    {
        // Ambil semua data dari request
        $data = $request->all();

        // Auto-generate kode karyawan (EMP001, EMP002, dst)
        $data['employee_code'] = Employee::generateEmployeeCode();

        // Simpan data karyawan ke database
        Employee::create($data);

        return redirect()->route('employee.index')->with('success', 'Data karyawan berhasil ditambahkan!');
    }

    /**
     * Mengupdate data karyawan yang sudah ada.
     * 
     * Proses:
     * - Update semua field yang dikirim dari form edit
     * - Employee code tidak bisa diubah (sebagai identifier unik)
     * 
     * Catatan: Route model binding otomatis mencari employee by ID
     */
    public function update(Request $request, Employee $employee)
    {
        // Update data karyawan dengan semua input dari form
        $employee->update($request->all());

        return redirect()->route('employee.index')->with('success', 'Data karyawan berhasil diperbarui!');
    }

    /**
     * Menghapus data karyawan secara bulk (multiple selection).
     * 
     * Proses:
     * 1. Ambil array employee_code yang dipilih dari checkbox
     * 2. Validasi apakah ada data yang dipilih
     * 3. Hapus data berdasarkan employee_code
     * 
     * Catatan:
     * - Menggunakan employee_code sebagai identifier (bukan ID)
     * - Bulk delete untuk efisiensi (hapus banyak data sekaligus)
     */
    public function destroy(Request $request)
    {
        // Ambil array employee_code dari checkbox
        $ids = $request->input('ids');

        // Validasi: pastikan ada data yang dipilih
        if (empty($ids)) {
            return redirect()->route('employee.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Hapus karyawan berdasarkan employee_code
        Employee::whereIn('employee_code', $ids)->delete();

        return redirect()->route('employee.index')->with('success', 'Data karyawan berhasil dihapus!');
    }
}
