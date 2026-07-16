<?php

namespace App\Services\Report;

use App\Models\Report\SalesRecap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Service untuk business logic Laporan Penjualan (Sales Report).
 *
 * Menangani seluruh logika bisnis dashboard laporan penjualan untuk General Manager,
 * termasuk: query building, statistik ringkasan, trend bulanan, distribusi status,
 * dan proyek teratas.
 *
 * Service ini menggunakan RecapSalesService untuk utilitas bersama
 * (seperti validasi kolom sorting) tanpa mengubah logic bisnis yang ada.
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
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter dan sorting
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedRecaps(Request $request)
    {
        $sortBy = $this->getAllowedSortColumn($request->get('sort_by', 'date'));
        $sortOrder = $request->get('sort_order', 'desc');

        return $this->buildFilteredQuery($request)
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
    }

    /**
     * Menghitung ringkasan statistik (summary cards).
     *
     * Mengembalikan: total transaksi, total modal, total penjualan, total profit,
     * margin profit, rata-rata per transaksi, jumlah lunas/belum lunas,
     * dan nilai lunas/belum lunas.
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
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     * @return array<int, array{month: int, month_name: string, count: int, capital: int, selling: int, profit: int}>
     */
    public function getMonthlyTrend(Request $request): array
    {
        $year = $request->get('year', date('Y'));

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
     * @param  \Illuminate\Http\Request  $request  Request yang berisi parameter filter
     * @param  int  $limit  Jumlah proyek yang ditampilkan (default: 5)
     */
    public function getTopProjects(Request $request, int $limit = 5): Collection
    {
        return $this->buildFilteredQuery($request)
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
}
