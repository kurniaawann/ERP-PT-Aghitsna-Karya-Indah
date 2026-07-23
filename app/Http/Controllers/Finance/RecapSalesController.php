<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreRecapSalesRequest;
use App\Http\Requests\Finance\UpdateRecapSalesRequest;
use App\Models\Report\SalesRecap;
use App\Models\Inventory\Items;
use App\Exports\Report\SalesRecapExport;
use App\Services\Finance\RecapSalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller untuk mengelola Rekap Penjualan/Sales Recap.
 *
 * Tanggung jawab: Request handling, Response, View rendering.
 * Business logic didelegasikan ke RecapSalesService.
 *
 * Fitur:
 * - CRUD sales recap dengan multiple items per sales
 * - Filter berdasarkan bulan, tahun, dan keyword pencarian
 * - Bulk delete dengan stock restoration
 * - Grand totals calculation dengan filtering
 * - Export ke PDF dan Excel
 */
class RecapSalesController extends Controller
{
    public function __construct(
        private RecapSalesService $service
    ) {}

    /**
     * Menampilkan daftar rekap penjualan dengan filter pencarian.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $salesRecaps = $this->service->buildFilteredQuery($request)
            ->orderBy('created_at', 'desc')
            ->orderBy('date', 'desc')
            ->paginate(10);

        $items = Items::orderBy('name_item')->get();

        $grandTotals = $this->service->getGrandTotals($request);

        return view('pages.finance.sales-recaps', compact('salesRecaps', 'items', 'grandTotals'));
    }

    /**
     * Menyimpan rekap penjualan baru.
     *
     * @param  \App\Http\Requests\Finance\StoreRecapSalesRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRecapSalesRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->service->createRecap($request->validated());

            DB::commit();

            return redirect()->route('recap-sales.index')
                ->with('success', 'Data rekap penjualan berhasil ditambahkan!');
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Recap Sales store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.')->withInput();
        }
    }

    /**
     * Mengupdate rekap penjualan.
     *
     * @param  \App\Http\Requests\Finance\UpdateRecapSalesRequest $request
     * @param  string                                            $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRecapSalesRequest $request, $id)
    {
        $salesRecap = SalesRecap::findOrFail($id);

        if ($salesRecap->isLunas()) {
            return back()->with('error', 'Data yang sudah lunas tidak dapat diubah!');
        }

        DB::beginTransaction();
        try {
            $this->service->updateRecap($salesRecap, $request->validated());

            DB::commit();

            return redirect()->route('recap-sales.index')
                ->with('success', 'Data rekap penjualan berhasil diupdate!');
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Recap Sales update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat mengupdate data. Silakan coba lagi.')->withInput();
        }
    }

    /**
     * Update status rekap penjualan.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  string                   $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'in:Belum Lunas,Lunas'],
        ]);

        $salesRecap = SalesRecap::findOrFail($id);

        try {
            $salesRecap->update(['status' => $request->status]);

            return redirect()->route('recap-sales.index')
                ->with('success', 'Status berhasil diupdate menjadi ' . $request->status . '!');
        } catch (\Exception $e) {
            Log::error('Recap Sales updateStatus failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat mengupdate status. Silakan coba lagi.');
        }
    }

    /**
     * Hapus beberapa rekap penjualan sekaligus (bulk delete).
     *
     * Mendukung response AJAX dan redirect biasa.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroySelected(Request $request)
    {
        $selectedIds = $request->input('selected_sales', []);
        $isAjax = $request->ajax();

        if (empty($selectedIds)) {
            $msg = 'Tidak ada data yang dipilih!';
            return $isAjax
                ? response()->json(['success' => false, 'message' => $msg])
                : back()->with('error', $msg);
        }

        DB::beginTransaction();
        try {
            $deletedCount = $this->service->bulkDelete($selectedIds);

            DB::commit();

            $msg = "Berhasil menghapus {$deletedCount} data penjualan.";
            return $isAjax
                ? response()->json(['success' => true, 'message' => $msg])
                : redirect()->route('recap-sales.index')->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Recap Sales destroySelected failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $msg = 'Terjadi kesalahan saat menghapus data. Silakan coba lagi.';
            return $isAjax
                ? response()->json(['success' => false, 'message' => $msg])
                : back()->with('error', $msg);
        }
    }

    /**
     * Export rekap penjualan ke Excel.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        $salesRecaps = $this->buildExportQuery($request)->get();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new SalesRecapExport($salesRecaps, $request->month, $request->year),
            'Rekap_Penjualan_' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export rekap penjualan ke PDF.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf(Request $request)
    {
        $salesRecaps = $this->buildExportQuery($request)->get();

        // Hitung grand totals
        $grandTotalCapital = 0;
        $grandTotalSelling = 0;

        foreach ($salesRecaps as $sale) {
            $items = is_string($sale->items) ? json_decode($sale->items, true) : $sale->items;
            foreach ($items as $item) {
                $qty = $item['quantity'] ?? 0;
                $capital = $item['capital_price'] ?? 0;
                $selling = $item['selling_price'] ?? 0;
                $grandTotalCapital += $capital * $qty;
                $grandTotalSelling += $selling * $qty;
            }
        }
        $grandTotalProfit = $grandTotalSelling - $grandTotalCapital;

        $monthYear = $this->buildMonthYearLabel($request, $salesRecaps);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.finance.sales-report-pdf', [
            'salesRecaps' => $salesRecaps,
            'monthYear' => $monthYear,
            'grandTotalCapital' => $grandTotalCapital,
            'grandTotalSelling' => $grandTotalSelling,
            'grandTotalProfit' => $grandTotalProfit,
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('Rekap_Penjualan_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Membangun query untuk export (Excel/PDF) dengan filter yang sama.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildExportQuery(Request $request)
    {
        return $this->service->buildFilteredQuery($request)
            ->orderBy('date', 'desc');
    }

    /**
     * Membangun label bulan/tahun untuk header PDF/Excel.
     *
     * @param  \Illuminate\Http\Request                          $request
     * @param  \Illuminate\Database\Eloquent\Collection          $salesRecaps
     * @return string
     */
    private function buildMonthYearLabel(Request $request, $salesRecaps): string
    {
        $month = $request->month;
        $year = $request->year;

        if (empty($month) && empty($year)) {
            $latestDate = $salesRecaps->sortByDesc('date')->first()?->date;
            if ($latestDate) {
                return \Carbon\Carbon::parse($latestDate)->locale('id')->translatedFormat('F Y');
            }
            return \Carbon\Carbon::now()->locale('id')->translatedFormat('F Y');
        }

        if (!empty($month) && empty($year)) {
            $latestDate = $salesRecaps->sortByDesc('date')->first()?->date;
            $year = $latestDate ? \Carbon\Carbon::parse($latestDate)->year : \Carbon\Carbon::now()->year;
            $monthName = \Carbon\Carbon::create()->month($month)->locale('id')->translatedFormat('F');
            return $monthName . ' ' . $year;
        }

        if (empty($month) && !empty($year)) {
            return 'TAHUN ' . $year;
        }

        $monthName = \Carbon\Carbon::create()->month($month)->locale('id')->translatedFormat('F');
        return $monthName . ' ' . $year;
    }
}
