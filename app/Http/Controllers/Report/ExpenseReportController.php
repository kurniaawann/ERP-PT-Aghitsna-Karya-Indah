<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Report\ExpenseRecap;
use App\Models\Report\TransactionCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseReportController extends Controller
{
    public function index(Request $request)
    {
        $query = ExpenseRecap::with(['category', 'creator']);

        // Filter berdasarkan bulan
        if ($request->filled('month')) {
            $query->whereMonth('transaction_date', $request->month);
        }

        // Filter berdasarkan tahun
        if ($request->filled('year')) {
            $query->whereYear('transaction_date', $request->year);
        } else {
            // Default tahun saat ini
            $query->whereYear('transaction_date', date('Y'));
        }

        // Filter berdasarkan kategori
        if ($request->filled('category')) {
            $query->where('transaction_category_id', $request->category);
        }

        // Filter berdasarkan tipe transaksi
        if ($request->filled('type')) {
            if ($request->type === 'income') {
                $query->where('income_amount', '>', 0);
            } elseif ($request->type === 'expense') {
                $query->where('expense_amount', '>', 0);
            }
        }

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('id', 'like', '%' . $request->search . '%')
                    ->orWhere('invoice_number', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'transaction_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $expenseRecaps = $query->paginate(10)->appends($request->all());

        // Calculate summary statistics
        $summary = $this->calculateSummary($request);

        // Get monthly trend
        $monthlyTrend = $this->getMonthlyTrend($request);

        // Get category distribution
        $categoryDistribution = $this->getCategoryDistribution($request);

        // Get cash flow analysis
        $cashFlow = $this->getCashFlow($request);

        // Get transaction categories for filter
        $categories = TransactionCategory::where('is_active', true)->get();

        return view('pages.report.expense-report', compact(
            'expenseRecaps',
            'summary',
            'monthlyTrend',
            'categoryDistribution',
            'cashFlow',
            'categories'
        ));
    }

    private function calculateSummary(Request $request)
    {
        $query = ExpenseRecap::query();

        if ($request->filled('month')) {
            $query->whereMonth('transaction_date', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('transaction_date', $request->year);
        } else {
            $query->whereYear('transaction_date', date('Y'));
        }

        if ($request->filled('category')) {
            $query->where('transaction_category_id', $request->category);
        }

        if ($request->filled('type')) {
            if ($request->type === 'income') {
                $query->where('income_amount', '>', 0);
            } elseif ($request->type === 'expense') {
                $query->where('expense_amount', '>', 0);
            }
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('id', 'like', '%' . $request->search . '%')
                    ->orWhere('invoice_number', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $summary = $query->selectRaw('
            COUNT(*) as total_transactions,
            SUM(income_amount) as total_income,
            SUM(expense_amount) as total_expense,
            SUM(CASE WHEN income_amount > 0 THEN 1 ELSE 0 END) as income_count,
            SUM(CASE WHEN expense_amount > 0 THEN 1 ELSE 0 END) as expense_count
        ')->first();

        $balance = ($summary->total_income ?? 0) - ($summary->total_expense ?? 0);

        return [
            'total_transactions' => $summary->total_transactions ?? 0,
            'total_income' => $summary->total_income ?? 0,
            'total_expense' => $summary->total_expense ?? 0,
            'balance' => $balance,
            'income_count' => $summary->income_count ?? 0,
            'expense_count' => $summary->expense_count ?? 0,
            'avg_income' => $summary->income_count > 0 ? $summary->total_income / $summary->income_count : 0,
            'avg_expense' => $summary->expense_count > 0 ? $summary->total_expense / $summary->expense_count : 0,
        ];
    }

    private function getMonthlyTrend(Request $request)
    {
        $year = $request->get('year', date('Y'));

        $trend = ExpenseRecap::whereYear('transaction_date', $year)
            ->when($request->filled('category'), function ($q) use ($request) {
                $q->where('transaction_category_id', $request->category);
            })
            ->when($request->filled('type'), function ($q) use ($request) {
                if ($request->type === 'income') {
                    $q->where('income_amount', '>', 0);
                } elseif ($request->type === 'expense') {
                    $q->where('expense_amount', '>', 0);
                }
            })
            ->selectRaw('
                MONTH(transaction_date) as month,
                COUNT(*) as count,
                SUM(income_amount) as income,
                SUM(expense_amount) as expense
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
                'income' => $trend->has($i) ? $trend[$i]->income : 0,
                'expense' => $trend->has($i) ? $trend[$i]->expense : 0,
                'balance' => $trend->has($i) ? ($trend[$i]->income - $trend[$i]->expense) : 0,
            ];
        }

        return $result;
    }

    private function getCategoryDistribution(Request $request)
    {
        $query = ExpenseRecap::with('category');

        if ($request->filled('month')) {
            $query->whereMonth('transaction_date', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('transaction_date', $request->year);
        } else {
            $query->whereYear('transaction_date', date('Y'));
        }

        if ($request->filled('type')) {
            if ($request->type === 'income') {
                $query->where('income_amount', '>', 0);
            } elseif ($request->type === 'expense') {
                $query->where('expense_amount', '>', 0);
            }
        }

        $results = $query->selectRaw('
            transaction_category_id,
            COUNT(*) as count,
            SUM(income_amount) as income,
            SUM(expense_amount) as expense
        ')
            ->groupBy('transaction_category_id')
            ->orderByRaw('SUM(expense_amount) DESC')
            ->get();

        // Get all categories at once
        $categoryIds = $results->pluck('transaction_category_id')->unique()->filter();
        $categories = TransactionCategory::whereIn('id', $categoryIds)->get()->keyBy('id');

        return $results->map(function ($item) use ($categories) {
            $category = $categories->get($item->transaction_category_id);
            return [
                'category_id' => $item->transaction_category_id,
                'category_name' => $category ? $category->name : 'Tidak ada kategori',
                'count' => $item->count,
                'income' => $item->income,
                'expense' => $item->expense,
                'total' => $item->income + $item->expense,
            ];
        });
    }

    private function getCashFlow(Request $request)
    {
        $query = ExpenseRecap::query();

        if ($request->filled('year')) {
            $query->whereYear('transaction_date', $request->year);
        } else {
            $query->whereYear('transaction_date', date('Y'));
        }

        // Get opening balance (before the period)
        $openingBalance = 0; // You might want to implement this based on your business logic

        // Get current period data
        $periodData = $query->selectRaw('
            SUM(income_amount) as total_income,
            SUM(expense_amount) as total_expense
        ')->first();

        $totalIncome = $periodData->total_income ?? 0;
        $totalExpense = $periodData->total_expense ?? 0;
        $closingBalance = $openingBalance + $totalIncome - $totalExpense;

        return [
            'opening_balance' => $openingBalance,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_cash_flow' => $totalIncome - $totalExpense,
            'closing_balance' => $closingBalance,
        ];
    }
}
