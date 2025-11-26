<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Report\ExpenseReport;
use App\Models\Report\TransactionCategory;
use App\Exports\ExpenseReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ExpenseReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $month = $request->get('month');
        $year = $request->get('year');
        $category = $request->get('category');
        $search = $request->get('search');

        $expenseReports = ExpenseReport::query()
            ->with(['category', 'salesReport', 'creator'])
            ->when($month, function ($query, $month) {
                return $query->whereMonth('transaction_date', $month);
            })
            ->when($year, function ($query, $year) {
                return $query->whereYear('transaction_date', $year);
            })
            ->when($category, function ($query, $category) {
                return $query->where('transaction_category_id', $category);
            })
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('money_source', 'like', "%{$search}%");
                });
            })
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get all categories for dropdown in add/edit modal
        $categories = TransactionCategory::active()->orderBy('sort_order')->get();

        // Calculate totals with filters
        $totals = ExpenseReport::query()
            ->when($month, function ($query, $month) {
                return $query->whereMonth('transaction_date', $month);
            })
            ->when($year, function ($query, $year) {
                return $query->whereYear('transaction_date', $year);
            })
            ->when($category, function ($query, $category) {
                return $query->where('transaction_category_id', $category);
            })
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('money_source', 'like', "%{$search}%");
                });
            })
            ->select(
                DB::raw('SUM(income_amount) as total_income'),
                DB::raw('SUM(expense_amount) as total_expense')
            )->first();

        $totals->balance = ($totals->total_income ?? 0) - ($totals->total_expense ?? 0);

        return view('pages.report.expense-report', compact('expenseReports', 'categories', 'totals'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_category_id' => 'required|exists:transaction_categories,id',
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:1000',
            'expense_amount' => 'required|integer|min:0',
            'invoice_number' => 'nullable|string|max:100',
            'money_source' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'transaction_category_id.required' => 'Kategori pengeluaran wajib dipilih',
            'transaction_date.required' => 'Tanggal wajib diisi',
            'description.required' => 'Keterangan wajib diisi',
            'expense_amount.required' => 'Jumlah pengeluaran wajib diisi',
            'expense_amount.integer' => 'Jumlah pengeluaran harus berupa angka',
            'expense_amount.min' => 'Jumlah pengeluaran tidak boleh negatif',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        $validated['created_by'] = Auth::id();
        $validated['income_amount'] = null;

        try {
            ExpenseReport::create($validated);

            return redirect()->route('expense-report.index')
                ->with('success', 'Data laporan pengeluaran berhasil ditambahkan!');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $expenseReport = ExpenseReport::findOrFail($id);

        // Cek apakah ini auto-generated (tidak boleh edit yang auto-generated dari sales report)
        if ($expenseReport->isAutoGenerated()) {
            return back()->with('error', 'Data yang auto-generated dari sales report tidak dapat diubah!');
        }

        $validator = Validator::make($request->all(), [
            'transaction_category_id' => 'required|exists:transaction_categories,id',
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:1000',
            'expense_amount' => 'required|integer|min:0',
            'invoice_number' => 'nullable|string|max:100',
            'money_source' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'transaction_category_id.required' => 'Kategori pengeluaran wajib dipilih',
            'transaction_date.required' => 'Tanggal wajib diisi',
            'description.required' => 'Keterangan wajib diisi',
            'expense_amount.required' => 'Jumlah pengeluaran wajib diisi',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        try {
            $expenseReport->update($validated);

            return redirect()->route('expense-report.index')
                ->with('success', 'Data laporan pengeluaran berhasil diupdate!');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Delete selected items (bulk delete).
     */
    public function destroySelected(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'selected_expenses' => 'required|array|min:1',
            'selected_expenses.*' => 'exists:expense_reports,id',
        ], [
            'selected_expenses.required' => 'Tidak ada data yang dipilih!',
            'selected_expenses.min' => 'Pilih minimal satu data untuk dihapus!',
            'selected_expenses.*.exists' => 'Data yang dipilih tidak valid!',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $selectedIds = $request->input('selected_expenses', []);

        // Delete selected expense reports
        $deletedCount = ExpenseReport::whereIn('id', $selectedIds)->delete();

        return redirect()->route('expense-report.index')
            ->with('success', "Berhasil menghapus {$deletedCount} data pengeluaran.");
    }

    /**
     * Export expense report to Excel
     */
    public function exportExcel(Request $request)
    {
        $month = $request->get('month');
        $year = $request->get('year');

        // Get filtered data (only by month and year)
        $expenseReports = ExpenseReport::query()
            ->with(['category', 'salesReport',])
            ->when($month, function ($query, $month) {
                return $query->whereMonth('transaction_date', $month);
            })
            ->when($year, function ($query, $year) {
                return $query->whereYear('transaction_date', $year);
            })
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate totals
        $totals = (object) [
            'total_income' => $expenseReports->sum('income_amount'),
            'total_expense' => $expenseReports->sum('expense_amount'),
        ];
        $totals->balance = $totals->total_income - $totals->total_expense;

        // Generate filename
        $filename = 'laporan-pengeluaran-' . date('Y-m-d-His') . '.xlsx';

        return Excel::download(
            new ExpenseReportExport($expenseReports, $month, $year, null, $totals),
            $filename
        );
    }

    /**
     * Export expense report to PDF
     */
    public function exportPdf(Request $request)
    {
        $month = $request->get('month');
        $year = $request->get('year');

        // Get filtered data (only by month and year)
        $expenseReports = ExpenseReport::query()
            ->with(['category', 'salesReport', 'creator'])
            ->when($month, function ($query, $month) {
                return $query->whereMonth('transaction_date', $month);
            })
            ->when($year, function ($query, $year) {
                return $query->whereYear('transaction_date', $year);
            })
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate totals
        $totals = (object) [
            'total_income' => $expenseReports->sum('income_amount'),
            'total_expense' => $expenseReports->sum('expense_amount'),
        ];
        $totals->balance = $totals->total_income - $totals->total_expense;

        // Build period title (only month and year)
        $periodParts = [];

        if ($month && $year) {
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');
            $periodParts[] = $monthName . ' ' . $year;
        } elseif ($month) {
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');
            $periodParts[] = 'Bulan ' . $monthName;
        } elseif ($year) {
            $periodParts[] = 'Tahun ' . $year;
        }

        $periodTitle = !empty($periodParts) ? implode(' - ', $periodParts) : 'Semua Periode';

        // Generate PDF
        $pdf = Pdf::loadView('exports.expense-report-pdf', [
            'expenseReports' => $expenseReports,
            'totals' => $totals,
            'periodTitle' => $periodTitle,
        ])->setPaper('a4', 'landscape');

        // Generate filename
        $filename = 'laporan-pengeluaran-' . date('Y-m-d-His') . '.pdf';

        return $pdf->download($filename);
    }
}
