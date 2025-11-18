<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     $employees = Employee::latest()->paginate(10);
    //     return view('pages.sdm.employee', compact('employees'));
    // }
    public function index(Request $request)
    {
        $search = $request->input('search');

        $employees = Employee::when($search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%");
        })
            ->latest()
            ->paginate(10);

        return view('pages.sdm.employee', compact('employees', 'search'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();

        // Auto-generate employee code
        $data['employee_code'] = Employee::generateEmployeeCode();

        Employee::create($data);

        return redirect()->route('employee.index')->with('success', 'Data karyawan berhasil ditambahkan!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $employee->update($request->all());

        return redirect()->route('employee.index')->with('success', 'Data karyawan berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('employee.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        Employee::whereIn('employee_code', $ids)->delete();

        return redirect()->route('employee.index')->with('success', 'Data karyawan berhasil dihapus!');
    }
}
