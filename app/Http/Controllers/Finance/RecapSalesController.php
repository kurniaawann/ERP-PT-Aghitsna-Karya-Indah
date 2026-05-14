<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Report\SalesRecap;
use App\Models\Inventory\Items;
use App\Exports\Report\SalesRecapExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller untuk mengelola rekap penjualan/sales recap.
 * 
 * Fitur Utama:
 * - CRUD sales recap dengan multiple items per sales
 * - Filter berdasarkan bulan, tahun, dan keyword pencarian
 * - Integrasi dengan inventory (stock deduction saat create/update)
 * - Perhitungan otomatis profit (total_selling - total_capital)
 * - Bulk delete dengan stock restoration (kembalikan stock saat delete)
 * - Grand totals calculation dengan filtering
 * - Export ke PDF dan Excel
 * - DB Transaction untuk ensure consistency stock management
 * 
 * Field Sales Recap:
 * - id_sales_recap (auto-generated), date, name_proyek
 * - items_data (JSON: array of { id_item, quantity, capital_price, selling_price })
 * - total_capital, total_selling, profit
 * - Stock management: kurangi stock saat create/update, kembalikan saat delete
 * 
 * Catatan Penting:
 * - Menggunakan DB transaction untuk stock management
 * - Validasi stock availability sebelum create/update
 * - Bulk update/restore stock saat delete multiple reports
 */
class RecapSalesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $month = $request->get('month');
        $year = $request->get('year');

        $salesRecaps = SalesRecap::query()
            ->when($search, function ($query, $search) {
                return $query->where('id_sales_recap', 'like', "%{$search}%")
                    ->orWhere('name_proyek', 'like', "%{$search}%");
            })
            ->when($month, function ($query, $month) {
                return $query->whereMonth('date', $month);
            })
            ->when($year, function ($query, $year) {
                return $query->whereYear('date', $year);
            })
            ->orderBy('created_at', 'desc')  // Data yang baru dibuat muncul di atas
            ->orderBy('date', 'desc')        // Jika created_at sama, urutkan berdasarkan date
            ->paginate(perPage: 10);

        // Get all items for dropdown
        $items = Items::orderBy('name_item')->get();

        // Calculate grand totals with filters
        $grandTotals = SalesRecap::query()
            ->when($month, function ($query, $month) {
                return $query->whereMonth('date', $month);
            })
            ->when($year, function ($query, $year) {
                return $query->whereYear('date', $year);
            })
            ->select(
                DB::raw('SUM(total_capital) as grand_total_capital'),
                DB::raw('SUM(total_selling) as grand_total_selling'),
                DB::raw('SUM(total_profit) as grand_total_profit')
            )->first();

        return view('pages.finance.recap-sales', compact('salesRecaps', 'items', 'grandTotals'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Ambil data dari request (validasi sudah dilakukan di HTML)
        $itemsJson = $request->input('items');

        // Decode JSON items menjadi array
        $items = json_decode($itemsJson, true);

        // Validasi sederhana: minimal harus ada 1 item
        if (empty($items)) {
            return back()->with('error', 'Minimal harus ada 1 item!')->withInput();
        }

        // Mulai database transaction untuk ensure consistency
        DB::beginTransaction();
        try {
            // Proses setiap item untuk stock management dan pricing
            foreach ($items as &$item) {
                // Cek apakah item dari stock inventory
                if (!empty($item['from_stock']) && !empty($item['id_item'])) {
                    // Lock stock item untuk concurrency control
                    $stockItem = Items::lockForUpdate()->where('id_item', $item['id_item'])->first();

                    // Validasi: stock item harus ada
                    if (!$stockItem) {
                        DB::rollBack();
                        return back()->with('error', 'Barang "' . $item['name_item'] . '" tidak ditemukan!')->withInput();
                    }

                    // Validasi: stock harus cukup
                    if ($stockItem->quantity < $item['quantity']) {
                        DB::rollBack();
                        return back()
                            ->with('error', 'Stok Barang Tidak Cukup Silahkan Sesuaikan Dengan Stook Yang Tersedia')
                            ->withInput();
                    }

                    // Kurangi stock dengan quantity yang dijual
                    $stockItem->quantity -= $item['quantity'];
                    $stockItem->save();

                    // Gunakan harga dari stock item
                    $item['capital_price'] = $stockItem->capital_price;
                    $item['selling_price'] = $stockItem->selling_price;
                } else {
                    // Item bukan dari stock - validasi harga manual
                    $capitalPrice = $item['capital_price'] ?? 0;
                    $sellingPrice = $item['selling_price'] ?? 0;

                    // Validasi business logic: harga modal < harga jual
                    if ($capitalPrice >= $sellingPrice) {
                        DB::rollBack();
                        return back()
                            ->with('error', "Harga modal harus lebih kecil dari harga jual untuk item")
                            ->withInput();
                    }
                }

                // Hitung profit per item: (harga jual - harga modal) × quantity
                $item['profit'] = ($item['selling_price'] - $item['capital_price']) * $item['quantity'];
            }

            // Siapkan data untuk insert ke database
            $data = [];
            $data['id_sales_recap'] = $this->generateSalesRecapId();
            $data['date'] = $request->date;  // Ambil dari request
            $data['name_proyek'] = $request->name_proyek;  // Ambil dari request
            $data['items'] = json_encode($items);
            $data['status'] = 'Belum Lunas';

            // Hitung total capital, selling, dan profit
            $totalCapital = 0;
            $totalSelling = 0;
            foreach ($items as $item) {
                $totalCapital += ($item['capital_price'] ?? 0) * ($item['quantity'] ?? 0);
                $totalSelling += ($item['selling_price'] ?? 0) * ($item['quantity'] ?? 0);
            }
            $data['total_capital'] = $totalCapital;
            $data['total_selling'] = $totalSelling;
            $data['total_profit'] = $totalSelling - $totalCapital;

            // Create sales report dengan data lengkap
            $salesRecap = SalesRecap::create($data);

            DB::commit();
            return redirect()->route('recap-sales.index')
                ->with('success', 'Data rekap penjualan berhasil ditambahkan!');
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
        // Cari sales report berdasarkan ID
        $salesRecap = SalesRecap::findOrFail($id);

        // Cek apakah sudah lunas (data lunas tidak bisa diubah)
        if ($salesRecap->isLunas()) {
            return back()->with('error', 'Data yang sudah lunas tidak dapat diubah!');
        }

        // Ambil items dari request (validasi sudah dilakukan di HTML)
        $newItems = $request->items;

        // Mulai database transaction untuk ensure consistency
        DB::beginTransaction();
        try {
            // Ambil old items untuk stock restoration
            $oldItems = is_string($salesRecap->items) ? json_decode($salesRecap->items, true) : $salesRecap->items;

            // Map untuk tracking perubahan stock per item: [id_item => delta_quantity]
            $stockChanges = [];

            // STEP 1: Hitung stock yang akan DIKEMBALIKAN dari old items
            foreach ($oldItems as $oldItem) {
                $isOldFromStock = isset($oldItem['from_stock']) && ($oldItem['from_stock'] === true || $oldItem['from_stock'] === 'true');

                if ($isOldFromStock && !empty($oldItem['id_item'])) {
                    $itemId = $oldItem['id_item'];
                    // Initialize jika belum ada
                    if (!isset($stockChanges[$itemId])) {
                        $stockChanges[$itemId] = 0;
                    }
                    // Tambahkan quantity yang dikembalikan (positif)
                    $stockChanges[$itemId] += $oldItem['quantity'];
                }
            }

            // STEP 2: Hitung stock yang akan DIKURANGI dari new items
            foreach ($newItems as &$item) {
                $isNewFromStock = isset($item['from_stock']) && ($item['from_stock'] === true || $item['from_stock'] === 'true');

                if ($isNewFromStock && !empty($item['id_item'])) {
                    $itemId = $item['id_item'];
                    // Initialize jika belum ada
                    if (!isset($stockChanges[$itemId])) {
                        $stockChanges[$itemId] = 0;
                    }
                    // Kurangi quantity yang akan diambil (negatif)
                    $stockChanges[$itemId] -= $item['quantity'];
                } else {
                    // Item tidak dari stock, set flag
                    $item['from_stock'] = false;
                    $item['id_item'] = null;
                }
            }

            // STEP 3: Validasi dan apply perubahan stock
            foreach ($stockChanges as $itemId => $delta) {
                // Lock item untuk concurrency
                $stockItem = Items::lockForUpdate()->where('id_item', $itemId)->first();

                if (!$stockItem) {
                    DB::rollBack();
                    return back()->with('error', 'Barang dengan ID "' . $itemId . '" tidak ditemukan!')->withInput();
                }

                // Hitung new stock: current + delta
                // delta bisa positif (return lebih banyak) atau negatif (ambil lebih banyak)
                $newStock = $stockItem->quantity + $delta;

                // Validasi: stock tidak boleh negatif
                if ($newStock < 0) {
                    DB::rollBack();
                    return back()
                        ->with('error', "Stock barang yang diambil melebihi stok tersedia")
                        ->withInput();
                }

                // Apply perubahan stock
                $stockItem->quantity = $newStock;
                $stockItem->save();
            }

            // STEP 4: Update item details untuk items from stock
            foreach ($newItems as &$item) {
                $isNewFromStock = isset($item['from_stock']) && ($item['from_stock'] === true || $item['from_stock'] === 'true');

                if ($isNewFromStock && !empty($item['id_item'])) {
                    // Ambil harga dari stock
                    $stockItem = Items::where('id_item', $item['id_item'])->first();

                    if ($stockItem) {
                        $item['capital_price'] = $stockItem->capital_price;
                        $item['selling_price'] = $stockItem->selling_price;
                    }

                    // Store sebagai boolean true
                    $item['from_stock'] = true;
                } else {
                    // Item bukan dari stock - validasi harga
                    $capitalPrice = $item['capital_price'] ?? 0;
                    $sellingPrice = $item['selling_price'] ?? 0;

                    if ($capitalPrice >= $sellingPrice) {
                        DB::rollBack();
                        return back()
                            ->with('error', "Harga modal harus lebih kecil dari harga jual untuk item")
                            ->withInput();
                    }
                }

                // Hitung profit per item
                $item['profit'] = ($item['selling_price'] - $item['capital_price']) * $item['quantity'];
            }

            // Update sales report
            $salesRecap->date = $request->date;
            $salesRecap->name_proyek = $request->name_proyek;
            $salesRecap->items = json_encode($newItems);
            // calculateTotals() method di model untuk hitung total_capital, total_selling, total_profit
            $salesRecap->calculateTotals();
            $salesRecap->save();

            // Commit transaction
            DB::commit();
            return redirect()->route('recap-sales.index')
                ->with('success', 'Data rekap penjualan berhasil diupdate!');
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
        // Cari sales report berdasarkan ID
        $salesRecap = SalesRecap::findOrFail($id);

        try {
            // Update status dari request (validasi sudah dilakukan di HTML)
            // Status: 'Belum Lunas' atau 'Lunas'
            $salesRecap->update(['status' => $request->status]);

            return redirect()->route('recap-sales.index')
                ->with('success', 'Status berhasil diupdate menjadi ' . $request->status . '!');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Delete selected items (bulk delete).
     */
    /**
     * Delete selected sales reports (bulk delete).
     */
    public function destroySelected(Request $request)
    {
        // Ambil array ID yang dipilih dari checkbox
        $selectedIds = $request->input('selected_sales', []);

        // Validasi sederhana: pastikan ada data yang dipilih
        if (empty($selectedIds)) {
            return back()->with('error', 'Tidak ada data yang dipilih!');
        }

        // Mulai database transaction untuk stock restoration
        DB::beginTransaction();
        try {
            // Ambil semua sales reports yang dipilih
            $salesRecaps = SalesRecap::whereIn('id_sales_recap', $selectedIds)->get();
            $deletedCount = 0;

            // Loop setiap sales report untuk restore stock
            foreach ($salesRecaps as $salesRecap) {
                // Kembalikan stock untuk items yang from_stock
                // Decode items dari JSON ke array
                $items = is_string($salesRecap->items)
                    ? json_decode($salesRecap->items, true)
                    : $salesRecap->items;

                // Loop setiap item untuk restore stock
                foreach ($items as $item) {
                    // Cek apakah item dari stock
                    if (!empty($item['from_stock']) && !empty($item['id_item'])) {
                        // Lock item untuk concurrency
                        $stockItem = Items::lockForUpdate()
                            ->where('id_item', $item['id_item'])
                            ->first();

                        if ($stockItem) {
                            // Kembalikan stock: tambahkan quantity yang sebelumnya dikurangi
                            $stockItem->quantity += $item['quantity'];
                            $stockItem->save();
                        }
                    }
                }

                // Hapus sales report dari database (ItemStockOut otomatis dihapus via Observer)
                $salesRecap->delete();
                $deletedCount++;
            }

            // Commit transaction jika semua berhasil
            DB::commit();

            return redirect()->route('recap-sales.index')
                ->with('success', "Berhasil menghapus {$deletedCount} data penjualan.");

        } catch (\Exception $e) {
            // Rollback jika ada error
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * Generate unique sales recap ID
     */
    private function generateSalesRecapId()
    {
        // Get the last sales recap ordered by ID (not created_at)
        $lastSalesRecap = SalesRecap::orderBy('id_sales_recap', 'desc')->first();

        if ($lastSalesRecap) {
            // Extract number from ID (e.g., "SR-00002" -> 2)
            $lastNumber = intval(substr($lastSalesRecap->id_sales_recap, 3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        // Generate new ID with zero padding
        $newId = 'SR-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);

        // Double-check for uniqueness (in case of race condition)
        while (SalesRecap::where('id_sales_recap', $newId)->exists()) {
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
        $query = SalesRecap::query()->with([]);

        // Apply search filter if exists
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name_proyek', 'like', '%' . $search . '%');
        }

        // Apply month filter
        if ($request->has('month') && $request->month) {
            $query->whereMonth('date', $request->month);
        }

        // Apply year filter
        if ($request->has('year') && $request->year) {
            $query->whereYear('date', $request->year);
        }

        $salesRecaps = $query->orderBy('date', 'desc')->get();

        // Pass month and year to export class
        return \Maatwebsite\Excel\Facades\Excel::download(
            new SalesRecapExport($salesRecaps, $request->month, $request->year),
            'Rekap_Penjualan_' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export sales report to PDF
     */
    public function exportPdf(Request $request)
    {
        $query = SalesRecap::query();

        // Apply search filter if exists
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name_proyek', 'like', '%' . $search . '%');
        }

        // Apply month filter
        if ($request->has('month') && $request->month) {
            $query->whereMonth('date', $request->month);
        }

        // Apply year filter
        if ($request->has('year') && $request->year) {
            $query->whereYear('date', $request->year);
        }

        $salesRecaps = $query->orderBy('date', 'desc')->get();

        // Calculate grand totals
        $grandTotalCapital = 0;
        $grandTotalSelling = 0;
        $grandTotalProfit = 0;

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

        // Generate month year label based on filter or latest data
        $month = $request->month;
        $year = $request->year;

        if (empty($month) && empty($year)) {
            // If no filter, use the latest date from data
            $latestDate = $salesRecaps->sortByDesc('date')->first()?->date;
            if ($latestDate) {
                $monthYear = \Carbon\Carbon::parse($latestDate)->locale('id')->translatedFormat('F Y');
            } else {
                $monthYear = \Carbon\Carbon::now()->locale('id')->translatedFormat('F Y');
            }
        } elseif (!empty($month) && empty($year)) {
            // If only month is filtered, get the latest year from data
            $latestYear = $salesRecaps->sortByDesc('date')->first()?->date;
            if ($latestYear) {
                $year = \Carbon\Carbon::parse($latestYear)->year;
            } else {
                $year = \Carbon\Carbon::now()->year;
            }
            $monthName = \Carbon\Carbon::create()->month($month)->locale('id')->translatedFormat('F');
            $monthYear = $monthName . ' ' . $year;
        } elseif (empty($month) && !empty($year)) {
            // If only year is filtered, show just the year
            $monthYear = 'TAHUN ' . $year;
        } else {
            // Both month and year are filtered
            $monthName = \Carbon\Carbon::create()->month($month)->locale('id')->translatedFormat('F');
            $monthYear = $monthName . ' ' . $year;
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.report.sales-report-pdf', [
            'salesRecaps' => $salesRecaps,
            'monthYear' => $monthYear,
            'grandTotalCapital' => $grandTotalCapital,
            'grandTotalSelling' => $grandTotalSelling,
            'grandTotalProfit' => $grandTotalProfit,
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('recap-sales-' . date('Y-m-d-His') . '.pdf');

    }
}

