<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Report\SalesRecap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesRecap::query();

        // Filter berdasarkan bulan
        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }

        // Filter berdasarkan tahun
        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        } else {
            // Default tahun saat ini
            $query->whereYear('date', date('Y'));
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('id_sales_recap', 'like', '%' . $request->search . '%')
                    ->orWhere('name_proyek', 'like', '%' . $request->search . '%');
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $salesRecaps = $query->paginate(10)->appends($request->all());

        // Calculate summary statistics
        $summary = $this->calculateSummary($request);

        // Get monthly trend
        $monthlyTrend = $this->getMonthlyTrend($request);

        // Get status distribution
        $statusDistribution = $this->getStatusDistribution($request);

        // Top projects
        $topProjects = $this->getTopProjects($request);

        return view('pages.report.sales-report', compact(
            'salesRecaps',
            'summary',
            'monthlyTrend',
            'statusDistribution',
            'topProjects'
        ));
    }

    private function calculateSummary(Request $request)
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

        $summary = $query->selectRaw('
            COUNT(*) as total_transactions,
            SUM(total_capital) as total_capital,
            SUM(total_selling) as total_selling,
            SUM(total_profit) as total_profit,
            SUM(CASE WHEN status = "Lunas" THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status = "Belum Lunas" THEN 1 ELSE 0 END) as unpaid_count,
            SUM(CASE WHEN status = "Lunas" THEN total_selling ELSE 0 END) as paid_amount,
            SUM(CASE WHEN status = "Belum Lunas" THEN total_selling ELSE 0 END) as unpaid_amount
        ')->first();

        // Calculate profit margin
        $profitMargin = $summary->total_selling > 0
            ? ($summary->total_profit / $summary->total_selling) * 100
            : 0;

        // Calculate average transaction value
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

    private function getMonthlyTrend(Request $request)
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

        // Fill missing months with zeros
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

    private function getStatusDistribution(Request $request)
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

        return $query->selectRaw('
            status,
            COUNT(*) as count,
            SUM(total_selling) as amount
        ')
            ->groupBy('status')
            ->get();
    }

    private function getTopProjects(Request $request, $limit = 5)
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

        return $query->orderBy('total_profit', 'desc')
            ->limit($limit)
            ->get();
    }
}
