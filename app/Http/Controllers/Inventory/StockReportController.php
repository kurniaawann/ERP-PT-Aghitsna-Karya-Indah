<?php

namespace App\Http\Controllers\Inventory;

use App\Exports\Inventory\StockReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockReportIndexRequest;
use App\Models\Inventory\Items;
use App\Services\Inventory\StockReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk menangani Laporan Stok Barang.
 *
 * Menyediakan endpoint untuk:
 * - Menampilkan laporan stok dengan filter periode dan barang
 * - Menyediakan data barang untuk dropdown infinite scroll (AJAX)
 */
class StockReportController extends Controller
{
    public function __construct(
        private StockReportService $stockReportService
    ) {}

    /**
     * Menampilkan halaman Laporan Stok Barang.
     *
     * Method ini:
     * 1. Memvalidasi input filter (tanggal mulai, tanggal akhir, item_id)
     * 2. Menghasilkan data laporan via StockReportService
     * 3. Menghitung summary (total stok awal, masuk, keluar, retur, akhir, nilai)
     * 4. Melakukan manual pagination untuk tabel
     * 5. Mengambil data barang terpilih untuk dropdown (jika ada filter item)
     *
     * @param  StockReportIndexRequest  $request  Request dengan parameter filter yang sudah divalidasi
     * @return \Illuminate\View\View
     */
    public function index(StockReportIndexRequest $request)
    {
        $validated = $request->validated();

        // Default: laporan bulan ini
        $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? Carbon::now()->toDateString();

        // Generate report (Collection)
        $reportData = $this->stockReportService->generateReport(
            $startDate,
            $endDate,
            $validated['item_id'] ?? null
        );

        // Get summary (tetap berdasarkan semua data, bukan hanya halaman)
        $summary = $this->stockReportService->getSummary($reportData);

        // Pagination manual untuk tampilan tabel
        $perPage = (int) ($request->input('per_page') ?: 10);
        $perPage = max(1, min($perPage, 100));

        $page = (int) ($request->input('page') ?: 1);
        $page = max(1, $page);

        $total = $reportData->count();
        $pageItems = $reportData->slice(($page - 1) * $perPage, $perPage)->values();

        $reportPaginator = new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        // Ambil data item kecil saja untuk placeholder (selected item).
        // Dropdown infinite scroll akan load via AJAX.
        $items = collect();
        if (!empty($validated['item_id'])) {
            $selected = Items::where('id_item', $validated['item_id'])->first();
            if ($selected) {
                $items = collect([$selected]);
            }
        }

        return view('pages.inventory.stock-report', [
            'reportData' => $reportPaginator,
            'summary' => $summary,
            'items' => $items,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedItemId' => $validated['item_id'] ?? null,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Endpoint JSON untuk dropdown infinite scroll pemilihan barang.
     *
     * Mengembalikan data barang secara parsial (paginated) dengan pencarian
     * berdasarkan ID barang atau nama barang. Digunakan oleh JavaScript
     * dropdown di halaman Laporan Stok.
     *
     * @param  Request  $request  Request AJAX dengan parameter search, page, limit
     * @return JsonResponse
     */
    public function itemsDropdown(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);

        $page = max(1, $page);
        $limit = max(1, min($limit, 25));

        $query = Items::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id_item', 'like', '%' . $search . '%')
                    ->orWhere('name_item', 'like', '%' . $search . '%');
            });
        }

        $total = (clone $query)->count();

        $items = $query
            ->orderBy('name_item')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get(['id_item', 'name_item']);

        $hasMore = (($page - 1) * $limit + $items->count()) < $total;

        return response()->json([
            'data' => $items->map(fn($it) => [
                'id_item' => $it->id_item,
                'name_item' => $it->name_item,
            ])->values(),
            'page' => $page,
            'limit' => $limit,
            'hasMore' => $hasMore,
            'total' => $total,
        ]);
    }

    /**
     * Export laporan stok ke PDF.
     */
    public function exportPdf(StockReportIndexRequest $request)
    {
        $validated = $request->validated();
        $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? Carbon::now()->toDateString();

        $data = $this->stockReportService->buildExportData($startDate, $endDate, $validated['item_id'] ?? null);

        $pdf = Pdf::loadView('exports.inventory.stock-report-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('Laporan_Stok_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export laporan stok ke Excel.
     */
    public function exportExcel(StockReportIndexRequest $request)
    {
        $validated = $request->validated();
        $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? Carbon::now()->toDateString();

        $data = $this->stockReportService->buildExportData($startDate, $endDate, $validated['item_id'] ?? null);

        return Excel::download(
            new StockReportExport($data['reportData'], $data['summary'], $data['periodTitle'], $data['startDate'], $data['endDate']),
            'Laporan_Stok_' . date('Y-m-d') . '.xlsx'
        );
    }
}
