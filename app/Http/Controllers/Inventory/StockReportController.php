<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Items;
use App\Services\StockReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

        // Generate report
        $reportData = $this->stockReportService->generateReport(
            $startDate,
            $endDate,
            $validated['item_id'] ?? null
        );

        // Get summary
        $summary = $this->stockReportService->getSummary($reportData);

        // Get all items for filter dropdown
        $items = Items::orderBy('name_item')->get();

        return view('pages.inventory.stock-report', [
            'reportData' => $reportData,
            'summary' => $summary,
            'items' => $items,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedItemId' => $validated['item_id'] ?? null,
        ]);
    }
}
