<?php

namespace App\Http\Controllers\Report;

use App\Exports\Report\ProjectFinancialReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreProjectFinancialReportItemRequest;
use App\Http\Requests\Report\UpdateProjectFinancialReportRequest;
use App\Models\Finance\ProjectRecap;
use App\Services\Finance\RecapProyekService;
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

        // Pastikan kategori UANG_MASUK (modul project_finance) tersedia untuk
        // user yang login, sehingga muncul di dropdown modal tambah/edit.
        $this->service->resolveIncomeCategory();

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
     * Menyimpan satu atau banyak item "Bon" baru pada laporan keuangan proyek.
     *
     * Form tambah memakai struktur dinamis sehingga bisa mengirim beberapa
     * transaksi sekaligus (array `items`).
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

            $items = $request->input('items', []);
            $proofFiles = $request->file('items', []);

            $this->service->createItems($report, $items, $proofFiles);

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
     * Menyimpan hasil edit gabungan Rekap Proyek beserta transaksi "Bon"
     * Laporan Keuangan Proyek-nya (satu form, satu design).
     *
     * 1. Data Rekap Proyek (nama, lokasi, total RAB) diupdate.
     * 2. Transaksi disinkronkan: item existing diupdate, transaksi baru
     *    dibuat, dan blok yang dihapus user ikut dihapus datanya.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateProjectFinancialReportRequest $request, ProjectRecap $projectRecap)
    {
        $this->authorizeRecap($projectRecap);

        DB::beginTransaction();
        try {
            // 1. Update data Rekap Proyek (tanpa ganti file design).
            app(RecapProyekService::class)->updateRecap(
                $projectRecap,
                $request->safe()->only(['project_name', 'location', 'total_rab']),
                null
            );

            // 2. Sinkronkan transaksi "Bon".
            //
            // Selalu dijalankan meski `items` kosong (saat semua blok kategori
            // dihapus user), agar item existing yang tidak dikirim ikut terhapus
            // oleh syncItems. Laporan hanya dibuat bila belum ada & masih ada
            // transaksi yang dikirim.
            $items = $request->input('items', []);
            $report = $projectRecap->financialReport;

            if ($report || ! empty($items)) {
                $report = $this->service->getOrCreateForRecap($projectRecap);

                $this->service->syncItems($report, $items, $request->file('items', []));
            }

            DB::commit();

            return redirect()->route('project-financial-report.index')
                ->with('success', 'Data rekap proyek dan transaksi berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Project Financial Report update failed', [
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
