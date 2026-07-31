<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrasi\StoreCashOutProofRequest;
use App\Http\Requests\Administrasi\UpdateCashOutProofRequest;
use App\Models\Administrasi\CashOutProof;
use App\Services\CashOutProofService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Controller untuk modul Bukti Kas Keluar (Cash Out Proof).
 *
 * Controller ini hanya menangani Request dan Response.
 * Seluruh business logic telah dipindahkan ke CashOutProofService.
 */
class CashOutProofController extends Controller
{
    /**
     * Konstruktor - dependency injection CashOutProofService.
     *
     * @param  CashOutProofService  $service  Service layer untuk modul bukti kas keluar
     */
    public function __construct(
        private readonly CashOutProofService $service
    ) {}

    /**
     * Menampilkan daftar bukti kas keluar dengan filter pencarian dan paginasi.
     *
     * @param  Request  $request  Request HTTP (search parameter)
     * @return \Illuminate\View\View Halaman daftar bukti kas keluar
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $cashOuts = $this->service->getPaginated($search);

        return view('pages.administrasi.cash-out-proof', compact('cashOuts', 'search'));
    }

    /**
     * Menyimpan data bukti kas keluar baru ke database.
     *
     * @param  StoreCashOutProofRequest  $request  Request yang sudah divalidasi
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses
     */
    public function store(StoreCashOutProofRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('cash-out-proof.index')
            ->with('success', 'Bukti kas keluar berhasil ditambahkan!');
    }

    /**
     * Memperbarui data bukti kas keluar yang sudah ada.
     *
     * @param  UpdateCashOutProofRequest  $request  Request yang sudah divalidasi
     * @param  CashOutProof  $cashOutProof  Model bukti kas keluar (route model binding by bkk_no)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses
     */
    public function update(UpdateCashOutProofRequest $request, CashOutProof $cashOutProof)
    {
        $this->service->update($cashOutProof, $request->validated());

        return redirect()
            ->route('cash-out-proof.index')
            ->with('success', 'Bukti kas keluar berhasil diperbarui!');
    }

    /**
     * Menghapus beberapa data bukti kas keluar sekaligus (bulk delete).
     *
     * @param  Request  $request  Request HTTP (ids parameter berisi array bkk_no)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses/error
     */
    public function destroySelected(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()
                ->route('cash-out-proof.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        $deletedCount = $this->service->destroySelected($ids);

        return redirect()
            ->route('cash-out-proof.index')
            ->with('success', "{$deletedCount} data terpilih berhasil dihapus.");
    }

    /**
     * Mengekspor seluruh data bukti kas keluar ke format PDF.
     *
     * @param  Request  $request  Request HTTP (search parameter untuk filter)
     * @return \Symfony\Component\HttpFoundation\Response Response PDF download
     */
    public function exportPdfAll(Request $request)
    {
        $search = $request->input('search');
        $cashOuts = $this->service->getAllForExport($search);

        return $this->generatePdfResponse($cashOuts);
    }

    /**
     * Mengekspor data bukti kas keluar yang dipilih ke format PDF.
     *
     * @param  Request  $request  Request HTTP (ids parameter berisi array bkk_no)
     * @return \Symfony\Component\HttpFoundation\Response Response PDF download
     */
    public function exportPdfSelected(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()
                ->route('cash-out-proof.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        $cashOuts = $this->service->getByIds($ids);

        return $this->generatePdfResponse($cashOuts);
    }

    /**
     * Membuat response PDF dari koleksi data bukti kas keluar.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $cashOuts  Koleksi data bukti kas keluar
     * @return \Symfony\Component\HttpFoundation\Response Response PDF download
     */
    private function generatePdfResponse($cashOuts)
    {
        $pdf = Pdf::loadView('exports.administrasi.cash-out-proof-pdf', compact('cashOuts'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('Bukti_Kas_Keluar_'.date('Y-m-d').'.pdf');
    }
}
