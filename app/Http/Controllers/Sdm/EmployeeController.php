<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        // Ambil keyword pencarian dari request (untuk filter nama atau kode karyawan)
        $search = $request->input('search');

        // Mulai query untuk mengambil data karyawan dengan filter pencarian
        $employees = Employee::when($search, function ($query, $search) {
            // when() menjalankan closure hanya jika $search tidak null/empty
            // Cari berdasarkan nama karyawan dengan LIKE (partial match)
            return $query->where('name', 'like', "%{$search}%")
                // ATAU cari berdasarkan employee_code dengan LIKE (partial match)
                ->orWhere('employee_code', 'like', "%{$search}%");
        })
            // Urutkan berdasarkan created_at descending (data terbaru di atas)
            ->latest('created_at')
            // Pagination 10 data per halaman
            ->paginate(10);

        // Return view dengan data employees (karyawan + pagination) dan search (untuk maintain keyword)
        return view('pages.sdm.employee', compact('employees', 'search'));
    }

    public function store(Request $request)
    {
        // Ambil semua input dari form dan simpan ke variable $data
        // all() mengembalikan array associative dengan semua field dari form
        $data = $request->all();

        // Auto-generate kode karyawan menggunakan method generateEmployeeCode() dari model Employee
        // Format kode: EMP001, EMP002, EMP003, dst (prefix EMP + nomor urut 3 digit)
        // Method ini ada di Model Employee, berfungsi untuk generate kode otomatis berdasarkan data terakhir
        $data['employee_code'] = Employee::generateEmployeeCode();

        // Convert daily_wage to null if empty
        if (empty($data['daily_wage'])) {
            $data['daily_wage'] = null;
        }

        // Insert data karyawan ke database
        // create() akan insert record baru ke tabel employees dan return model instance
        Employee::create($data);

        // Redirect ke halaman index employee dengan flash message sukses
        return redirect()->route('employee.index')->with('success', 'Data karyawan berhasil ditambahkan!');
    }

    public function update(Request $request, Employee $employee)
    {
        // Parameter $employee sudah otomatis di-inject oleh Laravel Route Model Binding
        // Laravel otomatis mencari Employee by ID dari route parameter
        $data = $request->all();

        // Convert daily_wage to null if empty
        if (empty($data['daily_wage'])) {
            $data['daily_wage'] = null;
        }

        // Update semua field dari request ke model employee
        // all() mengambil semua input dari form edit
        // Note: employee_code tidak akan berubah karena tidak ada di form edit (sebagai identifier unik)
        $employee->update($data);

        // Redirect ke halaman index employee dengan flash message sukses
        return redirect()->route('employee.index')->with('success', 'Data karyawan berhasil diperbarui!');
    }

    public function destroy(Request $request)
    {
        // Ambil array employee_code dari input dengan nama 'ids' (dari checkbox selection)
        // ids berisi array employee_code (bukan ID auto-increment), misal: ['EMP001', 'EMP002']
        $ids = $request->input('ids');

        // Validasi: cek apakah $ids kosong (empty() return true jika null, [], atau '')
        if (empty($ids)) {
            // Redirect ke halaman index dengan flash message error
            return redirect()->route('employee.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Hapus karyawan berdasarkan employee_code (bukan ID auto-increment)
        // whereIn('employee_code', $ids) akan match semua record dengan employee_code di dalam array
        // delete() akan menghapus record tersebut dari database
        // Note: Menggunakan employee_code sebagai identifier untuk delete (lebih aman dari sisi business logic)
        Employee::whereIn('employee_code', $ids)->delete();

        // Redirect ke halaman index dengan flash message sukses
        return redirect()->route('employee.index')->with('success', 'Data karyawan berhasil dihapus!');
    }
}
