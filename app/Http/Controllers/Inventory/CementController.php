<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreCementRequest;
use App\Http\Requests\Inventory\UpdateCementRequest;
use App\Services\Inventory\CementService;
use App\Exports\Inventory\CementExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk mengelola Data Semen.
 *
 * Controller ini hanya menangani request dan response HTTP.
 * Business logic didelegasikan ke CementService.
 */
class CementController extends Controller
{
    public function __construct(
        private readonly CementService $cementService
    ) {}

    /**
     * Menampilkan daftar Data Semen dengan paginasi dan pencarian.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $cements = $this->cementService->getPaginatedSearch($request->input('search'));

        return view('pages.inventory.cement', compact('cements'));
    }

    /**
     * Menyimpan Data Semen baru.
     *
     * @param  StoreCementRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreCementRequest $request)
    {
        $this->cementService->store($request->validated());

        return redirect()->back()->with('success', 'Data berhasil ditambahkan!');
    }

    /**
     * Memperbarui Data Semen yang sudah ada.
     *
     * @param  UpdateCementRequest  $request
     * @param  string               $no
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateCementRequest $request, string $no)
    {
        $cement = $this->cementService->findById($no);

        if (!$cement) {
            abort(404);
        }

        $this->cementService->update($cement, $request->validated());

        return redirect()->back()->with('success', 'Data berhasil diupdate!');
    }

    /**
     * Menghapus beberapa Data Semen sekaligus (bulk delete).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request)
    {
        $nos = $request->input('selected_items', []);

        if (empty($nos)) {
            return back()->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        $deletedCount = $this->cementService->destroySelected($nos);

        return back()->with('success', "{$deletedCount} data terpilih berhasil dihapus.");
    }

    /**
     * Export Data Semen ke format PDF.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf()
    {
        $cements = $this->cementService->getAll();

        $pdf = Pdf::loadView('exports.inventory.cement-pdf', compact('cements'));

        return $pdf->download('Data_Semen_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Data Semen ke format Excel.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel()
    {
        return Excel::download(new CementExport, 'Data_Semen_' . date('Y-m-d') . '.xlsx');
    }
}
