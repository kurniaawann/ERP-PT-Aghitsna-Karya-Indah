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
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            // Return stock for items that were from stock
            $oldItems = is_string($salesReport->items) ? json_decode($salesReport->items, true) : $salesReport->items;
            foreach ($oldItems as $oldItem) {
                if (!empty($oldItem['from_stock']) && !empty($oldItem['id_item'])) {
                    $stockItem = Items::where('id_item', $oldItem['id_item'])->first();
                    if ($stockItem) {
                        $stockItem->quantity += $oldItem['quantity'];
                        $stockItem->save();
                    }
                }
            }

            // Process new items
            $newItems = $validated['items'];
            foreach ($newItems as &$item) {
                if (!empty($item['from_stock']) && !empty($item['id_item'])) {
                    $stockItem = Items::lockForUpdate()->where('id_item', $item['id_item'])->first();

                    if ($stockItem && $stockItem->quantity < $item['quantity']) {
                        DB::rollBack();
                        return back()
                            ->with('error', 'Stok barang "' . $item['name_item'] . '" tidak cukup! Anda membutuhkan ' . $item['quantity'] . ' unit, tetapi stok yang tersedia hanya ' . $stockItem->quantity . ' unit.')
                            ->withInput();
                    }

                    if ($stockItem) {
                        $stockItem->quantity -= $item['quantity'];
                        $stockItem->save();
                    }
                }

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
}
