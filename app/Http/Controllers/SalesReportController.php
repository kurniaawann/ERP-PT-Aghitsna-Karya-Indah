<?php

namespace App\Http\Controllers;

use App\Models\SalesReport;
use App\Models\Items;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SalesReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        $salesReports = SalesReport::query()
            ->when($search, function ($query, $search) {
                return $query->where('id_sales_report', 'like', "%{$search}%")
                    ->orWhere('name_proyek', 'like', "%{$search}%");
            })
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(perPage: 10);

        // Get all items for dropdown
        $items = Items::orderBy('name_item')->get();

        // Calculate grand totals
        $grandTotals = SalesReport::select(
            DB::raw('SUM(total_capital) as grand_total_capital'),
            DB::raw('SUM(total_selling) as grand_total_selling'),
            DB::raw('SUM(total_profit) as grand_total_profit')
        )->first();

        return view('pages.sales-report', compact('salesReports', 'items', 'grandTotals'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'name_proyek' => 'required|string|max:255',
            'items' => 'required|json',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        $items = json_decode($validated['items'], true);

        if (empty($items)) {
            return back()->with('error', 'Minimal harus ada 1 item!')->withInput();
        }

        DB::beginTransaction();
        try {
            // Process each item
            foreach ($items as &$item) {
                // Check if from stock
                if (!empty($item['from_stock']) && !empty($item['id_item'])) {
                    $stockItem = Items::lockForUpdate()->where('id_item', $item['id_item'])->first();

                    if (!$stockItem) {
                        DB::rollBack();
                        return back()->with('error', 'Barang "' . $item['name_item'] . '" tidak ditemukan!')->withInput();
                    }

                    // Check stock availability
                    if ($stockItem->quantity < $item['quantity']) {
                        DB::rollBack();
                        return back()
                            ->with('error', 'Stok Barang Tidak Cukup Silahkan Sesuaikan Dengan Stook Yang Tersedia')
                            ->withInput();
                    }

                    // Reduce stock
                    $stockItem->quantity -= $item['quantity'];
                    $stockItem->save();

                    // Use item prices
                    $item['capital_price'] = $stockItem->capital_price;
                    $item['selling_price'] = $stockItem->selling_price;
                } else {
                    // Validate: Harga modal harus lebih kecil dari harga jual (untuk barang BUKAN dari stock)
                    $capitalPrice = $item['capital_price'] ?? 0;
                    $sellingPrice = $item['selling_price'] ?? 0;

                    if ($capitalPrice >= $sellingPrice) {
                        DB::rollBack();
                        return back()
                            // ->with('error', 'Harga modal barang "' . $item['name_item'] . '" harus lebih kecil dari harga jual!')
                            ->with('error', "Harga modal harus lebih kecil dari harga jual untuk item")
                            ->withInput();
                    }
                }

                // Calculate item profit
                $item['profit'] = ($item['selling_price'] - $item['capital_price']) * $item['quantity'];
            }

            // Generate ID
            $validated['id_sales_report'] = $this->generateSalesReportId();
            $validated['items'] = json_encode($items);
            $validated['status'] = 'Belum Lunas';

            // Calculate totals before creating
            $totalCapital = 0;
            $totalSelling = 0;
            foreach ($items as $item) {
                $totalCapital += ($item['capital_price'] ?? 0) * ($item['quantity'] ?? 0);
                $totalSelling += ($item['selling_price'] ?? 0) * ($item['quantity'] ?? 0);
            }
            $validated['total_capital'] = $totalCapital;
            $validated['total_selling'] = $totalSelling;
            $validated['total_profit'] = $totalSelling - $totalCapital;

            // Create sales report
            $salesReport = SalesReport::create($validated);

            DB::commit();
            return redirect()->route('sales-report.index')
                ->with('success', 'Data laporan penjualan berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $salesReport = SalesReport::findOrFail($id);

        // Check if already paid (lunas)
        if ($salesReport->isLunas()) {
            return back()->with('error', 'Data yang sudah lunas tidak dapat diubah!');
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'name_proyek' => 'required|string|max:255',
            'items' => 'required|array',
            'items.*.name_item' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.capital_price' => 'required|integer|min:0',
            'items.*.selling_price' => 'required|integer|min:0',
            'items.*.from_stock' => 'nullable|in:true,false,1,0',
            'items.*.id_item' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // DEBUG: Log data yang diterima
        \Log::info('=== EDIT SALES REPORT DEBUG ===');
        \Log::info('Old Items: ' . json_encode($salesReport->items));
        \Log::info('New Items from Request: ' . json_encode($validated['items']));

        DB::beginTransaction();
        try {
            $oldItems = is_string($salesReport->items) ? json_decode($salesReport->items, true) : $salesReport->items;
            $newItems = $validated['items'];

            // Create a map to track stock changes per item
            $stockChanges = []; // [id_item => delta_quantity]

            // Step 1: Calculate stock to RETURN from old items
            foreach ($oldItems as $oldItem) {
                $isOldFromStock = isset($oldItem['from_stock']) && ($oldItem['from_stock'] === true || $oldItem['from_stock'] === 'true');

                if ($isOldFromStock && !empty($oldItem['id_item'])) {
                    $itemId = $oldItem['id_item'];
                    // Tambahkan quantity yang akan dikembalikan (positif)
                    if (!isset($stockChanges[$itemId])) {
                        $stockChanges[$itemId] = 0;
                    }
                    $stockChanges[$itemId] += $oldItem['quantity'];
                    \Log::info("RETURN: Item {$itemId} +{$oldItem['quantity']}, Total: {$stockChanges[$itemId]}");
                }
            }

            // Step 2: Calculate stock to REDUCE from new items
            foreach ($newItems as &$item) {
                $isNewFromStock = isset($item['from_stock']) && ($item['from_stock'] === true || $item['from_stock'] === 'true');

                if ($isNewFromStock && !empty($item['id_item'])) {
                    $itemId = $item['id_item'];
                    // Kurangi quantity yang akan diambil (negatif)
                    if (!isset($stockChanges[$itemId])) {
                        $stockChanges[$itemId] = 0;
                    }
                    $stockChanges[$itemId] -= $item['quantity'];
                    \Log::info("REDUCE: Item {$itemId} -{$item['quantity']}, Total: {$stockChanges[$itemId]}");
                } else {
                    // Item tidak dari stock
                    $item['from_stock'] = false;
                    $item['id_item'] = null;
                }
            }

            // Step 3: Validate and apply stock changes
            foreach ($stockChanges as $itemId => $delta) {
                $stockItem = Items::lockForUpdate()->where('id_item', $itemId)->first();

                if (!$stockItem) {
                    DB::rollBack();
                    return back()->with('error', 'Barang dengan ID "' . $itemId . '" tidak ditemukan!')->withInput();
                }

                // Calculate new stock: current + delta
                // delta bisa positif (dikembalikan lebih banyak) atau negatif (diambil lebih banyak)
                $newStock = $stockItem->quantity + $delta;
                \Log::info("APPLY: Item {$itemId} ({$stockItem->name_item}) - Current: {$stockItem->quantity}, Delta: {$delta}, New: {$newStock}");

                // Validate: new stock cannot be negative
                if ($newStock < 0) {
                    // Hitung berapa stock yang kurang
                    $shortage = abs($newStock);
                    DB::rollBack();
                    return back()
                        ->with('error', 'Stok barang "' . $stockItem->name_item . '" tidak cukup! Stock tersedia saat ini: ' . $stockItem->quantity . ' unit. Anda kekurangan ' . $shortage . ' unit.')
                        ->withInput();
                }

                // Apply stock change
                $stockItem->quantity = $newStock;
                $stockItem->save();
                \Log::info("SAVED: Item {$itemId} stock updated to {$newStock}");
            }

            // Step 4: Update item details for items from stock
            foreach ($newItems as &$item) {
                $isNewFromStock = isset($item['from_stock']) && ($item['from_stock'] === true || $item['from_stock'] === 'true');

                if ($isNewFromStock && !empty($item['id_item'])) {
                    $stockItem = Items::where('id_item', $item['id_item'])->first();

                    if ($stockItem) {
                        // Use item prices from stock
                        $item['capital_price'] = $stockItem->capital_price;
                        $item['selling_price'] = $stockItem->selling_price;
                    }

                    // Store as boolean true
                    $item['from_stock'] = true;
                } else {
                    // Validate: Harga modal harus lebih kecil dari harga jual (untuk barang BUKAN dari stock)
                    $capitalPrice = $item['capital_price'] ?? 0;
                    $sellingPrice = $item['selling_price'] ?? 0;

                    if ($capitalPrice >= $sellingPrice) {
                        DB::rollBack();
                        return back()
                            ->with('error', 'Harga modal barang "' . $item['name_item'] . '" harus lebih kecil dari harga jual!')
                            ->withInput();
                    }
                }

                // Calculate item profit
                $item['profit'] = ($item['selling_price'] - $item['capital_price']) * $item['quantity'];
            }

            $salesReport->date = $validated['date'];
            $salesReport->name_proyek = $validated['name_proyek'];
            $salesReport->items = json_encode($newItems);
            $salesReport->calculateTotals();
            $salesReport->save();

            DB::commit();
            return redirect()->route('sales-report.index')
                ->with('success', 'Data laporan penjualan berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Update status to lunas
     */
    public function updateStatus(Request $request, $id)
    {
        $salesReport = SalesReport::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Belum Lunas,Lunas'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $validated = $validator->validated();

        try {
            $salesReport->update(['status' => $validated['status']]);
            return redirect()->route('sales-report.index')
                ->with('success', 'Status berhasil diupdate menjadi ' . $validated['status'] . '!');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Delete selected items (bulk delete).
     */
    public function destroySelected(Request $request)
    {
        $selectedIds = $request->input('selected_sales[]', []);

        if (empty($selectedIds)) {
            return back()->with('error', 'Tidak ada data yang dipilih!');
        }

        DB::beginTransaction();
        try {
            $salesReports = SalesReport::whereIn('id_sales_report', $selectedIds)->get();
            $deletedCount = 0;

            foreach ($salesReports as $salesReport) {
                // Return stock for items that were from stock
                $items = is_string($salesReport->items) ? json_decode($salesReport->items, true) : $salesReport->items;
                foreach ($items as $item) {
                    if (!empty($item['from_stock']) && !empty($item['id_item'])) {
                        $stockItem = Items::lockForUpdate()->where('id_item', $item['id_item'])->first();
                        if ($stockItem) {
                            $stockItem->quantity += $item['quantity'];
                            $stockItem->save();
                        }
                    }
                }

                $salesReport->delete();
                $deletedCount++;
            }

            DB::commit();

            return redirect()->route('sales-report.index')
                ->with('success', "Berhasil menghapus {$deletedCount} data.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Generate unique sales report ID
     */
    private function generateSalesReportId()
    {
        // Get the last sales report ordered by ID (not created_at)
        $lastSalesReport = SalesReport::orderBy('id_sales_report', 'desc')->first();

        if ($lastSalesReport) {
            // Extract number from ID (e.g., "SR-00002" -> 2)
            $lastNumber = intval(substr($lastSalesReport->id_sales_report, 3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        // Generate new ID with zero padding
        $newId = 'SR-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);

        // Double-check for uniqueness (in case of race condition)
        while (SalesReport::where('id_sales_report', $newId)->exists()) {
            $newNumber++;
            $newId = 'SR-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
        }

        return $newId;
    }

    /**
     * Export sales report to Excel
     */
    public function exportExcel(Request $request)
    {
        $query = SalesReport::query()->with([]);

        // Apply search filter if exists
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name_proyek', 'like', '%' . $search . '%');
        }

        $salesReports = $query->orderBy('date', 'desc')->get();

        $monthYear = \Carbon\Carbon::now()->locale('id')->translatedFormat('F Y');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\SalesReportExport($salesReports, $monthYear),
            'Laporan_Penjualan_' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export sales report to PDF
     */
    public function exportPdf(Request $request)
    {
        $query = SalesReport::query();

        // Apply search filter if exists
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name_proyek', 'like', '%' . $search . '%');
        }

        $salesReports = $query->orderBy('date', 'desc')->get();

        // Calculate grand totals
        $grandTotalCapital = 0;
        $grandTotalSelling = 0;
        $grandTotalProfit = 0;

        foreach ($salesReports as $sale) {
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

        $monthYear = \Carbon\Carbon::now()->locale('id')->translatedFormat('F Y');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.sales-report-pdf', [
            'salesReports' => $salesReports,
            'monthYear' => $monthYear,
            'grandTotalCapital' => $grandTotalCapital,
            'grandTotalSelling' => $grandTotalSelling,
            'grandTotalProfit' => $grandTotalProfit,
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('Laporan_Penjualan_' . date('Y-m-d') . '.pdf');
    }
}
