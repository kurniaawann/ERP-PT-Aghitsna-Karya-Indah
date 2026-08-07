<?php

namespace App\Services\Report;

use App\Models\Report\SalesRecap;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Service untuk business logic Laporan Penjualan (Sales Report).
 *
 * Menangani seluruh logika bisnis dashboard laporan penjualan untuk General Manager,
 * termasuk: query building, statistik ringkasan, trend bulanan, distribusi status,
 * proyek teratas, dan data export PDF/Excel.
 */
class SalesReportService
{
    /**
     * Kolom yang diizinkan untuk sorting pada laporan penjualan.
     */
    private const ALLOWED_SORT_COLUMNS = [
        'date',
        'name_proyek',
        'total_capital',
        'total_selling',
        'total_profit',
        'status',
        'created_at',
    ];

    public function __construct() {}

    /**
     * Membangun query dasar dengan filter dari request.
     *
     * Filter yang didukung:
     * - month: Filter berdasarkan bulan
     * - year: Filter berdasarkan tahun (default: tahun saat ini)
     * - status: Filter berdasarkan status (Lunas/Belum Lunas)
     * - search: Pencarian berdasarkan ID atau nama proyek
     *
     * Logika:
     * - Semua filter memakai kolom `date` sebagai basis periode.
     * - Jika year tidak dikirim, dianggap tahun berjalan — laporan tidak pernah
     *   menampilkan data lintas tahun tanpa filter eksplisit.
     * - search dibungkus closure agar LIKE pada id_sales_recap dan name_proyek
     *   digabung dengan OR di dalam satu group (tidak merusak filter lain).
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     */
    public function buildFilteredQuery(Request $request): Builder
    {
        $query = SalesRecap::query();

        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        } else {
            $query->whereYear('date', date('Y'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id_sales_recap', 'like', '%'.$search.'%')
                    ->orWhere('name_proyek', 'like', '%'.$search.'%');
            });
        }

        return $query;
    }

    /**
     * Mengambil data rekap penjualan dengan pagination dan sorting.
     *
     * Hanya mengambil kolom yang diperlukan untuk tampilan tabel
     * tanpa kolom JSON `items` untuk menghindari over-fetching.
     *
     * Logika nomor faktur (no_faktur):
     * - Nomor faktur di-generate saat render, TIDAK disimpan di database.
     * - Format: {A}/{B}/DIV.PRODUKSI/{tahun}; A mulai dari 317, B dari 598,
     *   keduanya naik 1 per baris.
     * - Karena halaman hanya menampilkan 10 baris, offset = (halaman-1) × 10
     *   dijumlahkan ke angka awal supaya urutan faktur berlanjut antar halaman
     *   (halaman 2 lanjut dari 327/608, bukan mengulang dari 317/598).
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter dan sorting
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedRecaps(Request $request)
    {
        $sortBy = $this->getAllowedSortColumn($request->get('sort_by', 'date'));
        $sortOrder = $request->get('sort_order', 'desc');

        $paginator = $this->buildFilteredQuery($request)
            ->select([
                'id_sales_recap',
                'date',
                'name_proyek',
                'total_capital',
                'total_selling',
                'total_profit',
                'status',
            ])
            ->orderBy($sortBy, $sortOrder)
            ->paginate(10)
            ->appends($request->all());

        $fakturYear = $request->filled('year') ? $request->input('year') : date('Y');
        $offset = ($paginator->currentPage() - 1) * $paginator->perPage();
        $fakturA = 317 + $offset;
        $fakturB = 598 + $offset;
        foreach ($paginator->getCollection() as $item) {
            $item->no_faktur = $fakturA++ . '/' . $fakturB++ . '/DIV.PRODUKSI/' . $fakturYear;
        }

        return $paginator;
    }

    /**
     * Menghitung ringkasan statistik (summary cards).
     *
     * Mengembalikan: total transaksi, total modal, total penjualan, total profit,
     * margin profit, rata-rata per transaksi, jumlah lunas/belum lunas,
     * dan nilai lunas/belum lunas.
     *
     * Logika:
     * - Semua agregasi memakai satu SELECT agregat (single-row) lewat selectRaw,
     *   bukan perhitungan per record — efisien walau dataset besar.
     * - paid/unpaid dihitung dengan CASE WHEN pada kolom status (1/0) lalu di-SUM.
     * - profit_margin = total_profit / total_selling × 100; 0 jika selling = 0.
     * - avg_transaction = total_selling / total_transactions, dibulatkan ke
     *   integer (round, 0 desimal) karena nominal rupiah.
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     * @return array<string, mixed>
     */
    public function calculateSummary(Request $request): array
    {
        $summary = $this->buildFilteredQuery($request)
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(total_capital) as total_capital,
                SUM(total_selling) as total_selling,
                SUM(total_profit) as total_profit,
                SUM(CASE WHEN status = "Lunas" THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status = "Belum Lunas" THEN 1 ELSE 0 END) as unpaid_count,
                SUM(CASE WHEN status = "Lunas" THEN total_selling ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status = "Belum Lunas" THEN total_selling ELSE 0 END) as unpaid_amount
            ')->first();

        $profitMargin = $summary->total_selling > 0
            ? ($summary->total_profit / $summary->total_selling) * 100
            : 0;

        $avgTransaction = $summary->total_transactions > 0
            ? $summary->total_selling / $summary->total_transactions
            : 0;

        return [
            'total_transactions' => $summary->total_transactions ?? 0,
            'total_capital' => $summary->total_capital ?? 0,
            'total_selling' => $summary->total_selling ?? 0,
            'total_profit' => $summary->total_profit ?? 0,
            'profit_margin' => round($profitMargin, 2),
            'avg_transaction' => round($avgTransaction, 0),
            'paid_count' => $summary->paid_count ?? 0,
            'unpaid_count' => $summary->unpaid_count ?? 0,
            'paid_amount' => $summary->paid_amount ?? 0,
            'unpaid_amount' => $summary->unpaid_amount ?? 0,
        ];
    }

    /**
     * Mendapatkan data trend bulanan untuk chart.
     *
     * Mengembalikan data 12 bulan (Jan-Des) dengan jumlah transaksi,
     * total modal, total penjualan, dan total profit per bulan.
     * Bulan yang tidak ada data akan diisi dengan nilai 0.
     *
     * Logika:
     * - Query memakai MONTH(date) + GROUP BY month → hanya bulan berdata yang
     *   keluar dari database.
     * - Loop 1..12 lalu keyBy('month') memastikan 12 titik chart selalu lengkap;
     *   bulan kosong diisi 0 (bukan null) agar grafik tidak putus.
     * - Filter status tetap diterapkan sehingga trend mengikuti pilihan user.
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     * @return array<int, array{month: int, month_name: string, count: int, capital: int, selling: int, profit: int}>
     */
    public function getMonthlyTrend(Request $request): array
    {
        $year = $request->filled('year') ? $request->input('year') : date('Y');

        $trend = SalesRecap::whereYear('date', $year)
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->selectRaw('
                MONTH(date) as month,
                COUNT(*) as count,
                SUM(total_capital) as capital,
                SUM(total_selling) as selling,
                SUM(total_profit) as profit
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $result = [];
        for ($i = 1; $i <= 12; $i++) {
            $result[] = [
                'month' => $i,
                'month_name' => date('M', mktime(0, 0, 0, $i, 1)),
                'count' => $trend->has($i) ? $trend[$i]->count : 0,
                'capital' => $trend->has($i) ? $trend[$i]->capital : 0,
                'selling' => $trend->has($i) ? $trend[$i]->selling : 0,
                'profit' => $trend->has($i) ? $trend[$i]->profit : 0,
            ];
        }

        return $result;
    }

    /**
     * Mendapatkan distribusi status pembayaran (Lunas/Belum Lunas).
     *
     * Mengembalikan koleksi dengan field: status, count, amount.
     * Digunakan untuk chart distribusi status.
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     */
    public function getStatusDistribution(Request $request): Collection
    {
        return $this->buildFilteredQuery($request)
            ->selectRaw('
                status,
                COUNT(*) as count,
                SUM(total_selling) as amount
            ')
            ->groupBy('status')
            ->get();
    }

    /**
     * Mendapatkan top proyek berdasarkan profit.
     *
     * Hanya mengambil kolom yang diperlukan untuk tampilan
     * tanpa kolom JSON `items` untuk menghindari over-fetching.
     *
     * Logika:
     * - Diurutkan total_profit menurun lalu dibatasi $limit → "top proyek".
     * - Nomor faktur di-generate sekuensial dari 317/598 tanpa offset halaman,
     *   karena list selalu dimulai dari baris pertama (bukan hasil pagination).
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     * @param  int  $limit  Jumlah proyek yang ditampilkan (default: 5)
     */
    public function getTopProjects(Request $request, int $limit = 5): Collection
    {
        $projects = $this->buildFilteredQuery($request)
            ->select([
                'id_sales_recap',
                'date',
                'name_proyek',
                'total_capital',
                'total_selling',
                'total_profit',
                'status',
            ])
            ->orderBy('total_profit', 'desc')
            ->limit($limit)
            ->get();

        $fakturYear = $request->filled('year') ? $request->input('year') : date('Y');
        $fakturA = 317;
        $fakturB = 598;
        foreach ($projects as $item) {
            $item->no_faktur = $fakturA++ . '/' . $fakturB++ . '/DIV.PRODUKSI/' . $fakturYear;
        }

        return $projects;
    }

    /**
     * Mendapatkan kolom sorting yang diizinkan.
     *
     * Mencegah SQL injection dengan memastikan kolom sorting
     * hanya berasal dari daftar yang diizinkan.
     *
     * @param  string  $sortBy  Kolom yang diminta untuk sorting
     * @return string Kolom yang valid atau default 'date'
     */
    public function getAllowedSortColumn(string $sortBy): string
    {
        return in_array($sortBy, self::ALLOWED_SORT_COLUMNS) ? $sortBy : 'date';
    }

    // ============================================================
    // EXPORT METHODS
    // ============================================================

    /**
     * Membangun data untuk export PDF/Excel.
     *
     * Mengembalikan array dengan struktur:
     * - projects: Array proyek dengan sales_recaps, subtotal, lunas_date
     * - periodTitle: Label periode untuk header
     * - grandTotal: Total keseluruhan
     *
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     * @return array{projects: array, periodTitle: string, grandTotal: int}
     */
    public function buildExportData(Request $request): array
    {
        $salesRecaps = $this->buildFilteredQuery($request)
            ->orderBy('date', 'desc')
            ->get();

        $projectGroups = $salesRecaps->groupBy('name_proyek');

        $projects = [];
        $grandTotal = 0;

        $fakturYear = $request->filled('year') ? $request->input('year') : date('Y');
        $fakturA = 317;
        $fakturB = 598;

        foreach ($projectGroups as $projectName => $projectSales) {
            $projectData = $this->buildProjectExportData($projectSales, $fakturYear, $fakturA, $fakturB);
            $projectData['project_name'] = $projectName;

            $grandTotal += $projectData['subtotal'];
            $projects[] = $projectData;
        }

        $periodTitle = $this->buildPeriodTitle($request, $salesRecaps);

        return [
            'projects' => $projects,
            'periodTitle' => $periodTitle,
            'grandTotal' => $grandTotal,
        ];
    }

    /**
     * Membangun data export untuk satu proyek.
     *
     * Logika:
     * 1. Kolom items di DB bisa berupa JSON string atau array → di-decode dulu
     *    jika string, agar item bisa di-loop seragam.
     * 2. Subtotal DIHITUNG ULANG dari selling_price × quantity per item (bukan
     *    memakai total_selling tabel) supaya sesuai detail items pada faktur.
     * 3. lunas_date diambil dari record pertama berstatus 'Lunas': memakai
     *    updated_at (tanggal pembayaran) jika ada, fallback ke date.
     * 4. Nomor faktur (317/598++) diteruskan antar proyek lewat referensi
     *    $fakturA/$fakturB agar tidak ada nomor yang terulang di seluruh export.
     *
     * @param  \Illuminate\Support\Collection  $projectSales  Data sales dalam satu proyek
     * @return array{sales_recaps: array, subtotal: int, lunas_date: string|null}
     */
    private function buildProjectExportData(Collection $projectSales, string $fakturYear, int &$fakturA, int &$fakturB): array
    {
        $subtotal = 0;
        $lunasDate = null;
        $salesRecapsData = [];

        foreach ($projectSales as $sale) {
            $items = is_string($sale->items) ? json_decode($sale->items, true) : $sale->items;

            $saleItems = [];
            foreach ($items as $item) {
                $qty = $item['quantity'] ?? 0;
                $capital = $item['capital_price'] ?? 0;
                $selling = $item['selling_price'] ?? 0;
                $jumlah = $selling * $qty;

                $subtotal += $jumlah;

                $saleItems[] = [
                    'name_item' => $item['name_item'] ?? '-',
                    'qty' => $qty,
                    'capital_price' => $capital,
                    'selling_price' => $selling,
                    'jumlah' => $jumlah,
                ];
            }

            $salesRecapsData[] = [
                'id_sales_recap' => $sale->id_sales_recap,
                'no_faktur' => $fakturA++ . '/' . $fakturB++ . '/DIV.PRODUKSI/' . $fakturYear,
                'date' => Carbon::parse($sale->date)->format('d/m/Y'),
                'status' => $sale->status,
                'items' => $saleItems,
            ];

            if ($sale->status === 'Lunas' && $lunasDate === null) {
                $lunasDate = Carbon::parse($sale->updated_at ?? $sale->date)->format('d/m/Y');
            }
        }

        return [
            'sales_recaps' => $salesRecapsData,
            'subtotal' => $subtotal,
            'lunas_date' => $lunasDate,
        ];
    }

    /**
     * Membangun label periode untuk header PDF/Excel.
     *
     * Logika:
     * - Prioritas label: (month+year) → (month saja) → (year saja) → otomatis
     *   dari data terbaru → fallback bulan berjalan.
     * - Jika hanya month dikirim, tahun diturunkan dari tanggal terbaru di
     *   data (atau tahun sekarang bila tidak ada data), bukan dari request.
     * - Nama bulan memakai locale id lewat Carbon::translatedFormat('F').
     *
     * @param  \Illuminate\Http\Request                $request     Request yang berisi parameter filter
     * @param  \Illuminate\Support\Collection          $salesRecaps Data rekap penjualan
     * @return string Label periode (contoh: "BULAN FEBRUARI 2026")
     */
    public function buildPeriodTitle(Request $request, Collection $salesRecaps): string
    {
        $month = $request->month;
        $year = $request->year;

        if (!empty($month) && !empty($year)) {
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');
            return 'BULAN ' . strtoupper($monthName) . ' ' . $year;
        }

        if (!empty($month)) {
            $latestDate = $salesRecaps->sortByDesc('date')->first()?->date;
            $year = $latestDate ? Carbon::parse($latestDate)->year : Carbon::now()->year;
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');
            return 'BULAN ' . strtoupper($monthName) . ' ' . $year;
        }

        if (!empty($year)) {
            return 'TAHUN ' . $year;
        }

        $latestDate = $salesRecaps->sortByDesc('date')->first()?->date;
        if ($latestDate) {
            return 'BULAN ' . strtoupper(Carbon::parse($latestDate)->locale('id')->translatedFormat('F Y'));
        }

        return 'BULAN ' . strtoupper(Carbon::now()->locale('id')->translatedFormat('F Y'));
    }
}
