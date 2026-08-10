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
     * melihat semua, user lain hanya miliknya. Mendukung filter pencarian
     * (nama proyek / lokasi) dan filter bulan-tahun berdasarkan tanggal
     * pembuatan rekap. Tombol "Detail" pada setiap baris membuka modal berisi
     * tabel transaksi "Bon" proyek tersebut.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $recaps = ProjectRecap::query()
            ->with(['financialReport.items.category'])
            ->when(auth()->user()->role !== 'superadmin', function ($query) {
                $query->where('created_by', auth()->id());
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('project_name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('month'), fn ($query) => $query->whereMonth('created_at', $request->month))
            ->when($request->filled('year'), fn ($query) => $query->whereYear('created_at', $request->year))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $categories = $this->service->getProjectFinanceCategories();

        $rekapOptions = ProjectRecap::query()
            ->when(auth()->user()->role !== 'superadmin', function ($query) {
                $query->where('created_by', auth()->id());
            })
            ->orderByDesc('created_at')
            ->get(['id', 'project_name', 'location']);

        return view('pages.report.project-financial-report', compact('recaps', 'categories', 'rekapOptions'));
    }

    /**
     * Menyimpan item "Bon" baru pada laporan keuangan proyek.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeItem(StoreProjectFinancialReportItemRequest $request)
    {
        $projectRecap = ProjectRecap::findOrFail($request->input('project_recap_id'));

        $this->authorizeRecap($projectRecap);

        DB::beginTransaction();
        try {
            $report = $this->service->getOrCreateForRecap($projectRecap);

            $this->service->createItem($report, $request->validated(), $request->file('proof_file'));

            DB::commit();

            return redirect()->route('project-financial-report.index')
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

            return redirect()->route('project-financial-report.index')
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

            return redirect()->route('project-financial-report.index')
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
