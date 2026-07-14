<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Report\SalesRecap;
use App\Services\Finance\RecapSalesService;
use Illuminate\Http\Request;

/**
 * Controller untuk Laporan Penjualan (Sales Report).
 *
 * Hanya bisa diakses oleh General Manager.
 * Menampilkan dashboard ringkasan, trend bulanan, distribusi status, top proyek, dan detail transaksi.
 */
class SalesReportController extends Controller
{
    public function __construct(
        private RecapSalesService $recapSalesService
    ) {}

    /**
     * Menampilkan halaman laporan penjualan.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = $this->buildBaseQuery($request);

        // Sorting
        $sortBy = $this->recapSalesService->getAllowedSortColumn($request->get('sort_by', 'date'));
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $salesRecaps = $query->paginate(10)->appends($request->all());

        $summary = $this->calculateSummary($request);
        $monthlyTrend = $this->getMonthlyTrend($request);
        $statusDistribution = $this->getStatusDistribution($request);
        $topProjects = $this->getTopProjects($request);

        return view('pages.report.sales-report', compact(
            'salesRecaps',
            'summary',
            'monthlyTrend',
            'statusDistribution',
            'topProjects'
        ));
    }

    /**
     * Membangun query dasar dengan filter dari request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildBaseQuery(Request $request)
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
            $query->where(function ($q) use ($request) {
                $q->where('id_sales_recap', 'like', '%' . $request->search . '%')
                    ->orWhere('name_proyek', 'like', '%' . $request->search . '%');
            });
        }

        return $query;
    }

    /**
     * Menghitung ringkasan statistik (summary cards).
     *
     * @param  \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    private function calculateSummary(Request $request): array
    {
        $summary = $this->buildBaseQuery($request)
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
     * @param  \Illuminate\Http\Request $request
     * @return array<int, array<string, mixed>>
     */
    private function getMonthlyTrend(Request $request): array
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
     * Mendapatkan distribusi status (Lunas/Belum Lunas).
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Collection
     */
    private function getStatusDistribution(Request $request)
    {
        return $this->buildBaseQuery($request)
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
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $limit  Jumlah proyek yang ditampilkan
     * @return \Illuminate\Support\Collection
     */
    private function getTopProjects(Request $request, $limit = 5)
    {
        return $this->buildBaseQuery($request)
            ->orderBy('total_profit', 'desc')
            ->limit($limit)
            ->get();
    }
}
