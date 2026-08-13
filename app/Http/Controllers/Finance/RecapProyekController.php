<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreRecapProyekRequest;
use App\Http\Requests\Finance\UpdateRecapProyekRequest;
use App\Models\Finance\ProjectRecap;
use App\Services\Finance\RecapProyekService;
use App\Services\Report\ProjectFinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller untuk Rekap Proyek (standalone).
 *
 * Modul mandiri untuk mengelola rekap proyek dengan input manual:
 * - No (ID auto-generate format RP-00001)
 * - Nama Proyek
 * - Total RAB
 * - File design (unggahan)
 *
 * Business logic didelegasikan ke RecapProyekService.
 */
class RecapProyekController extends Controller
{
    public function __construct(
        protected RecapProyekService $service
    ) {}

    /**
     * Menampilkan daftar rekap proyek dengan pagination.
     *
     * @param  \Illuminate\Http\Request  $request  Filter: search
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $recaps = $this->service->buildIndexQuery($request)
            ->paginate(10)
            ->appends($request->all());

        return view('pages.finance.project-recaps', compact('recaps'));
    }

    /**
     * Menyimpan rekap proyek baru dari input manual user.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRecapProyekRequest $request)
    {
        DB::beginTransaction();
        try {
            $recap = $this->service->createRecap($request->validated(), $request->file('design_file'));

            // Konsep: Laporan Keuangan Proyek berdiri sendiri dan dibuat
            // otomatis saat Rekap Proyek dibuat (1 rekap = 1 laporan).
            app(ProjectFinancialReportService::class)->getOrCreateForRecap($recap);

            DB::commit();

            return redirect()->route('recap-proyek.index')
                ->with('success', 'Data rekap proyek berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Recap Proyek store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.')->withInput();
        }
    }

    /**
     * Mengupdate rekap proyek yang sudah ada.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRecapProyekRequest $request, ProjectRecap $projectRecap)
    {
        DB::beginTransaction();
        try {
            $this->service->updateRecap($projectRecap, $request->validated(), $request->file('design_file'));

            DB::commit();

            return redirect()->route('recap-proyek.index')
                ->with('success', 'Data rekap proyek berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Recap Proyek update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat mengupdate data. Silakan coba lagi.');
        }
    }

    /**
     * Hapus beberapa rekap proyek sekaligus (bulk delete).
     *
     * Rekap yang masih dipakai data lain (payroll, kasbon, atau karyawan)
     * tidak bisa dihapus agar tidak meninggalkan data yatim. Laporan Keuangan
     * Proyek milik rekap ikut terhapus (cascade) sehingga tidak memblokir.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request)
    {
        $selectedIds = $request->input('selected_recaps', []);

        if (empty($selectedIds)) {
            return back()->with('error', 'Tidak ada data yang dipilih!');
        }

        // Guard: rekap yang masih dipakai data lain tidak boleh dihapus
        $usedIds = $this->service->findUsedRecapIds($selectedIds);

        if (! empty($usedIds)) {
            $usedNames = $this->service->getRecapLabels($usedIds);
            $msg = "Rekap proyek berikut tidak dapat dihapus karena masih digunakan pada data lain (payroll, kasbon, atau karyawan): {$usedNames}. Silakan hapus atau ubah data yang menggunakan proyek ini terlebih dahulu.";

            return back()->with('error', $msg);
        }

        DB::beginTransaction();
        try {
            $deletedCount = $this->service->bulkDelete($selectedIds);

            DB::commit();

            return redirect()->route('recap-proyek.index')
                ->with('success', "Berhasil menghapus {$deletedCount} data rekap proyek.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Recap Proyek destroySelected failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
        }
    }
}
