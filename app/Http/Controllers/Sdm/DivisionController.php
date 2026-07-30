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
 * Controller untuk mengelola data divisi.
 *
 * Menangani permintaan dan respons HTTP untuk operasi CRUD divisi.
 * Logika bisnis didelegasikan ke DivisionService.
 */
class DivisionController extends Controller
{
    /**
     * Instance layanan divisi.
     *
     * @var DivisionService
     */
    protected DivisionService $divisionService;

    /**
     * Membuat instance controller baru.
     *
     * @param  DivisionService  $divisionService
     */
    public function __construct(DivisionService $divisionService)
    {
        $this->divisionService = $divisionService;
    }

    /**
     * Menampilkan daftar divisi dengan paginasi dan pencarian opsional.
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
     * Menyimpan data divisi baru.
     *
     * @param  StoreDivisionRequest  $request
     * @return RedirectResponse
     */
    public function store(StoreDivisionRequest $request): RedirectResponse
    {
        $this->divisionService->createDivision($request->validated());
        $this->divisionService->flushCache();

        return redirect()->route('division.index')
            ->with('success', 'Data divisi berhasil ditambahkan!');
    }

    /**
     * Memperbarui data divisi yang ditentukan.
     *
     * @param  UpdateDivisionRequest  $request
     * @param  Division               $division
     * @return RedirectResponse
     */
    public function update(UpdateDivisionRequest $request, Division $division): RedirectResponse
    {
        $this->divisionService->updateDivision($division, $request->validated());
        $this->divisionService->flushCache();

        return redirect()->route('division.index')
            ->with('success', 'Data divisi berhasil diperbarui!');
    }

    /**
     * Menghapus divisi yang ditentukan secara massal.
     *
     * Memeriksa apakah ada divisi yang dipilih masih memiliki karyawan sebelum penghapusan.
     * Mengembalikan pesan kesalahan yang mencantumkan divisi yang tidak dapat dihapus.
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
        $this->divisionService->flushCache();

        return redirect()->route('division.index')
            ->with('success', 'Data divisi berhasil dihapus!');
    }
}
