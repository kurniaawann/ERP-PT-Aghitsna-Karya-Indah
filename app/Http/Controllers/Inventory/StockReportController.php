<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Items;
use App\Services\StockReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class StockReportController extends Controller
{
    protected StockReportService $stockReportService;

    public function __construct(StockReportService $stockReportService)
    {
        $this->stockReportService = $stockReportService;
    }

    /**
     * Display stock report
     */
    public function index(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'item_id' => 'nullable|exists:items,id_item',
        ]);

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

        // Pagination (untuk tampilan tabel)
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
     * JSON endpoint untuk dropdown infinite scroll.
     * GET /stock-report/items-dropdown?search=&page=&limit=
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
}
