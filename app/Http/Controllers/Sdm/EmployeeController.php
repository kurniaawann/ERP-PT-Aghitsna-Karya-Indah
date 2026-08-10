<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sdm\StoreEmployeeRequest;
use App\Http\Requests\Sdm\UpdateEmployeeRequest;
use App\Models\Finance\ProjectRecap;
use App\Models\Sdm\Employee;
use App\Services\Sdm\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller untuk mengelola data karyawan.
 *
 * Menangani permintaan dan respons HTTP untuk operasi CRUD karyawan.
 * Logika bisnis didelegasikan ke EmployeeService.
 */
class EmployeeController extends Controller
{
    /**
     * Instance layanan karyawan.
     *
     * @var EmployeeService
     */
    protected EmployeeService $employeeService;

    /**
     * Membuat instance controller baru.
     *
     * @param  EmployeeService  $employeeService
     */
    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    /**
     * Menampilkan daftar karyawan dengan paginasi dan pencarian opsional.
     *
     * @param  Request  $request
     * @return View
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $employees = $this->employeeService->getPaginatedEmployees($search);
        $divisions = $this->employeeService->getAllDivisions();

        return view('pages.sdm.employee', compact('employees', 'search', 'divisions'));
    }

    /**
     * Endpoint JSON untuk dropdown infinite scroll pemilihan proyek.
     *
     * Mengambil nama proyek dari Rekap Proyek (ProjectRecap) milik user login
     * secara parsial (paginated) dengan pencarian berdasarkan nama proyek.
     *
     * @param  Request  $request  Request AJAX dengan parameter search, page, limit
     * @return \Illuminate\Http\JsonResponse
     */
    public function projectsDropdown(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);

        $page = max(1, $page);
        $limit = max(1, min($limit, 25));

        $query = ProjectRecap::where('created_by', auth()->id());

        if ($search !== '') {
            $query->where('project_name', 'like', '%' . $search . '%');
        }

        $total = (clone $query)->count();

        $projects = $query
            ->orderBy('project_name')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get(['project_name']);

        $hasMore = (($page - 1) * $limit + $projects->count()) < $total;

        return response()->json([
            'data' => $projects->map(fn ($p) => ['project_name' => $p->project_name])->values(),
            'page' => $page,
            'limit' => $limit,
            'hasMore' => $hasMore,
            'total' => $total,
        ]);
    }

    /**
     * Menyimpan data karyawan baru.
     *
     * employee_code dibuat secara otomatis oleh service.
     * daily_wage sudah dinormalisasi oleh form request.
     *
     * @param  StoreEmployeeRequest  $request
     * @return RedirectResponse
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $this->employeeService->createEmployee($request->validated());
        $this->employeeService->flushCache();

        return redirect()->route('employee.index')
            ->with('success', 'Data karyawan berhasil ditambahkan!');
    }

    /**
     * Memperbarui data karyawan yang ditentukan.
     *
     * @param  UpdateEmployeeRequest  $request
     * @param  \App\Models\Sdm\Employee  $employee
     * @return RedirectResponse
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->employeeService->updateEmployee($employee, $request->validated());
        $this->employeeService->flushCache();

        return redirect()->route('employee.index')
            ->with('success', 'Data karyawan berhasil diperbarui!');
    }

    /**
     * Menghapus karyawan yang ditentukan secara massal.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('employee.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        $this->employeeService->deleteEmployees($ids);
        $this->employeeService->flushCache();

        return redirect()->route('employee.index')
            ->with('success', 'Data karyawan berhasil dihapus!');
    }
}
