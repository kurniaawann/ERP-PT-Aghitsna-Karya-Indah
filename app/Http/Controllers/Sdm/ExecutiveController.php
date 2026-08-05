<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sdm\StoreExecutiveRequest;
use App\Http\Requests\Sdm\UpdateExecutiveRequest;
use App\Models\Sdm\Executive;
use App\Services\Sdm\ExecutiveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller untuk mengelola data petinggi (executive).
 *
 * Menangani permintaan dan respons HTTP untuk operasi CRUD petinggi,
 * termasuk unggah gambar tanda tangan. Logika bisnis didelegasikan
 * ke ExecutiveService.
 */
class ExecutiveController extends Controller
{
    /**
     * Instance layanan petinggi.
     *
     * @var ExecutiveService
     */
    protected ExecutiveService $executiveService;

    /**
     * Membuat instance controller baru.
     *
     * @param  ExecutiveService  $executiveService
     */
    public function __construct(ExecutiveService $executiveService)
    {
        $this->executiveService = $executiveService;
    }

    /**
     * Menampilkan daftar petinggi dengan paginasi dan pencarian opsional.
     *
     * @param  Request  $request
     * @return View
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $executives = $this->executiveService->getPaginatedExecutives($search);

        return view('pages.sdm.executive', compact('executives', 'search'));
    }

    /**
     * Menyimpan data petinggi baru.
     *
     * @param  StoreExecutiveRequest  $request
     * @return RedirectResponse
     */
    public function store(StoreExecutiveRequest $request): RedirectResponse
    {
        $result = $this->executiveService->createExecutive(
            $request->validated(),
            $request->file('signature_image')
        );

        return redirect()->route('executive.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Memperbarui data petinggi yang ditentukan.
     *
     * @param  UpdateExecutiveRequest  $request
     * @param  Executive               $executive
     * @return RedirectResponse
     */
    public function update(UpdateExecutiveRequest $request, Executive $executive): RedirectResponse
    {
        $result = $this->executiveService->updateExecutive(
            $executive,
            $request->validated(),
            $request->file('signature_image')
        );

        return redirect()->route('executive.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Menghapus data petinggi yang ditentukan secara massal.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        $result = $this->executiveService->deleteExecutives($ids);

        return redirect()->route('executive.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
