<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sdm\StoreDivisionRequest;
use App\Http\Requests\Sdm\UpdateDivisionRequest;
use App\Models\Sdm\Division;
use App\Services\Sdm\DivisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for managing division data.
 *
 * Handles HTTP requests and responses for division CRUD operations.
 * Business logic is delegated to DivisionService.
 */
class DivisionController extends Controller
{
    /**
     * The division service instance.
     *
     * @var DivisionService
     */
    protected DivisionService $divisionService;

    /**
     * Create a new controller instance.
     *
     * @param  DivisionService  $divisionService
     */
    public function __construct(DivisionService $divisionService)
    {
        $this->divisionService = $divisionService;
    }

    /**
     * Display a paginated list of divisions with optional search.
     *
     * @param  Request  $request
     * @return View
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $divisions = $this->divisionService->getPaginatedDivisions($search);

        return view('pages.sdm.division', compact('divisions', 'search'));
    }

    /**
     * Store a newly created division in storage.
     *
     * @param  StoreDivisionRequest  $request
     * @return RedirectResponse
     */
    public function store(StoreDivisionRequest $request): RedirectResponse
    {
        $this->divisionService->createDivision($request->validated());

        return redirect()->route('division.index')
            ->with('success', 'Data divisi berhasil ditambahkan!');
    }

    /**
     * Update the specified division in storage.
     *
     * @param  UpdateDivisionRequest  $request
     * @param  Division               $division
     * @return RedirectResponse
     */
    public function update(UpdateDivisionRequest $request, Division $division): RedirectResponse
    {
        $this->divisionService->updateDivision($division, $request->validated());

        return redirect()->route('division.index')
            ->with('success', 'Data divisi berhasil diperbarui!');
    }

    /**
     * Remove the specified divisions from storage (bulk delete).
     *
     * Checks if any selected division still has employees before deletion.
     * Returns an error message listing the divisions that cannot be deleted.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('division.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        $divisionsWithEmployees = $this->divisionService->getDivisionsWithEmployees($ids);

        if (!empty($divisionsWithEmployees)) {
            return redirect()->route('division.index')
                ->with('error', 'Divisi ' . implode(', ', $divisionsWithEmployees) . ' masih memiliki karyawan!');
        }

        $this->divisionService->deleteDivisions($ids);

        return redirect()->route('division.index')
            ->with('success', 'Data divisi berhasil dihapus!');
    }
}
