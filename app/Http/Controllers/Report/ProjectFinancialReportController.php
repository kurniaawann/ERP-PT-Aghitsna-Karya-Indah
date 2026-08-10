<?php

namespace App\Http\Controllers\Report;

use App\Exports\Report\ProjectFinancialReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreProjectFinancialReportItemRequest;
use App\Http\Requests\Report\UpdateProjectFinancialReportItemRequest;
use App\Models\Finance\ProjectRecap;
use App\Models\Report\ProjectFinancialReportItem;
use App\Services\Report\ProjectFinancialReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk Laporan Keuangan Proyek.
 *
 * Laporan dibuat per Rekap Proyek (1:1) dan diakses lewat tombol
 * "Laporan Keuangan" di tabel Rekap Proyek. Halaman utama menampilkan
 * daftar "Bon" (uang masuk/uang keluar) dengan upload bukti pembayaran,
 * plus ekspor PDF/Excel mengikuti desain Rekap Pengeluaran.
 *
 * Business logic didelegasikan ke ProjectFinancialReportService.
 */
class ProjectFinancialReportController extends Controller
{
    public function __construct(
        private ProjectFinancialReportService $service
    ) {}

    /**
     * Cek kepemilikan rekap proyek.
     *
     * Hanya pemilik rekap atau superadmin yang boleh membuka laporannya.
     */
    private function authorizeRecap(ProjectRecap $recap): void
    {
        abort_if(
            $recap->created_by !== auth()->id() && auth()->user()->role !== 'superadmin',
            403
        );
    }

    /**
     * Menampilkan halaman daftar Laporan Keuangan Proyek.
     *
     * Menampilkan seluruh Rekap Proyek beserta status laporannya. Superadmin
     * melihat semua, user lain hanya miliknya. Tombol "Buka Laporan" masuk
     * ke halaman detail per rekap.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $recaps = ProjectRecap::query()
            ->with(['financialReport.items'])
            ->when(auth()->user()->role !== 'superadmin', function ($query) {
                $query->where('created_by', auth()->id());
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('pages.report.project-financial-report-index', compact('recaps'));
    }

    /**
     * Menampilkan halaman Laporan Keuangan Proyek untuk sebuah rekap.
     *
     * Laporan dibuat otomatis (auto-create) jika belum ada.
     *
     * @return \Illuminate\View\View
     */
    public function show(ProjectRecap $projectRecap)
    {
        $this->authorizeRecap($projectRecap);

        $report = $this->service->getOrCreateForRecap($projectRecap);

        $items = $this->service->getItems($report);
        $categories = $this->service->getProjectFinanceCategories();
        $totals = $this->service->getGrandTotals($items);

        return view('pages.report.project-financial-report', [
            'recap' => $projectRecap,
            'report' => $report,
            'items' => $items,
            'categories' => $categories,
            'totals' => $totals,
        ]);
    }

    /**
     * Menyimpan item "Bon" baru pada laporan keuangan proyek.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeItem(StoreProjectFinancialReportItemRequest $request, ProjectRecap $projectRecap)
    {
        $this->authorizeRecap($projectRecap);

        DB::beginTransaction();
        try {
            $report = $this->service->getOrCreateForRecap($projectRecap);

            $this->service->createItem($report, $request->validated(), $request->file('proof_file'));

            DB::commit();

            return redirect()->route('project-financial-report.show', $projectRecap)
                ->with('success', 'Data transaksi berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Project Financial Report storeItem failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.')->withInput();
        }
    }

    /**
     * Mengupdate item "Bon" pada laporan keuangan proyek.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateItem(UpdateProjectFinancialReportItemRequest $request, ProjectRecap $projectRecap, ProjectFinancialReportItem $item)
    {
        $this->authorizeRecap($projectRecap);

        DB::beginTransaction();
        try {
            $this->service->updateItem($item, $request->validated(), $request->file('proof_file'));

            DB::commit();

            return redirect()->route('project-financial-report.show', $projectRecap)
                ->with('success', 'Data transaksi berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Project Financial Report updateItem failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat mengupdate data. Silakan coba lagi.');
        }
    }

    /**
     * Hapus beberapa item "Bon" sekaligus (bulk delete).
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request, ProjectRecap $projectRecap)
    {
        $this->authorizeRecap($projectRecap);

        $selectedIds = $request->input('selected_items', []);

        if (empty($selectedIds)) {
            return back()->with('error', 'Tidak ada data yang dipilih!');
        }

        DB::beginTransaction();
        try {
            $deletedCount = $this->service->bulkDeleteItems($selectedIds);

            DB::commit();

            return redirect()->route('project-financial-report.show', $projectRecap)
                ->with('success', "Berhasil menghapus {$deletedCount} data transaksi.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Project Financial Report destroySelected failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
        }
    }

    /**
     * Export Laporan Keuangan Proyek ke PDF.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf(ProjectRecap $projectRecap)
    {
        $this->authorizeRecap($projectRecap);

        $report = $this->service->getOrCreateForRecap($projectRecap);
        $items = $this->service->getItems($report);
        $totals = $this->service->getGrandTotals($items);

        $pdf = Pdf::loadView('exports.report.project-financial-report-pdf', [
            'recap' => $projectRecap,
            'report' => $report,
            'items' => $items,
            'totals' => $totals,
        ])->setPaper('a4', 'landscape');

        $filename = 'Laporan_Keuangan_'.$projectRecap->id.'_'.date('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export Laporan Keuangan Proyek ke Excel.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(ProjectRecap $projectRecap)
    {
        $this->authorizeRecap($projectRecap);

        $report = $this->service->getOrCreateForRecap($projectRecap);
        $items = $this->service->getItems($report);
        $totals = $this->service->getGrandTotals($items);

        $filename = 'Laporan_Keuangan_'.$projectRecap->id.'_'.date('Y-m-d').'.xlsx';

        return Excel::download(
            new ProjectFinancialReportExport($projectRecap, $items, $totals),
            $filename
        );
    }
}
