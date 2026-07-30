<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreRecapExpenseRequest;
use App\Http\Requests\Finance\UpdateRecapExpenseRequest;
use App\Models\Report\ExpenseRecap;
use App\Services\Finance\RecapExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Report\ExpenseRecapExport;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Controller untuk mengelola Rekap Pengeluaran / Expense Recap.
 *
 * Tanggung jawab: Request handling, Response, View rendering.
 * Business logic didelegasikan ke RecapExpenseService.
 *
 * Fitur:
 * - CRUD expense recap (manual input)
 * - Filter berdasarkan bulan, tahun, kategori, dan pencarian
 * - Bulk delete untuk data manual
 * - Grand totals calculation (income, expense, balance)
 * - Export ke PDF dan Excel
 */
class RecapExpenseController extends Controller
{
    public function __construct(
        private RecapExpenseService $service
    ) {}

    /**
     * Menampilkan daftar rekap pengeluaran dengan filter dan grand totals.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $expenseRecaps = $this->service->buildIndexQuery($request)
            ->paginate(10)
            ->appends($request->all());

        $categories = $this->service->getExpenseCategories();

        $totals = $this->service->getGrandTotals($request);

        return view('pages.finance.expense-recaps', compact('expenseRecaps', 'categories', 'totals'));
    }

    /**
     * Menyimpan rekap pengeluaran baru dari input manual user.
     *
     * @param  \App\Http\Requests\Finance\StoreRecapExpenseRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRecapExpenseRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->service->createRecap($request->validated());

            DB::commit();

            return redirect()->route('recap-expense.index')
                ->with('success', 'Data rekap pengeluaran berhasil ditambahkan!');
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Recap Expense store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.')->withInput();
        }
    }

    /**
     * Mengupdate rekap pengeluaran.
     *
     * @param  \App\Http\Requests\Finance\UpdateRecapExpenseRequest $request
     * @param  string                                               $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRecapExpenseRequest $request, $id)
    {
        $expenseRecap = ExpenseRecap::findOrFail($id);

        DB::beginTransaction();
        try {
            $this->service->updateRecap($expenseRecap, $request->validated());

            DB::commit();

            return redirect()->route('recap-expense.index')
                ->with('success', 'Data rekap pengeluaran berhasil diupdate!');
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Recap Expense update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat mengupdate data. Silakan coba lagi.');
        }
    }

    /**
     * Hapus beberapa rekap pengeluaran sekaligus (bulk delete).
     *
     * Hanya menghapus data manual (bukan auto-generated dari sales report).
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request)
    {
        $selectedIds = $request->input('selected_expenses', []);

        if (empty($selectedIds)) {
            return back()->with('error', 'Tidak ada data yang dipilih!');
        }

        DB::beginTransaction();
        try {
            $deletedCount = $this->service->bulkDelete($selectedIds);

            DB::commit();

            return redirect()->route('recap-expense.index')
                ->with('success', "Berhasil menghapus {$deletedCount} data rekap pengeluaran.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Recap Expense destroySelected failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
        }
    }

    /**
     * Export rekap pengeluaran ke Excel.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        $expenseRecaps = $this->service->buildExportQuery($request)->get();

        $totals = (object) [
            'total_income' => $expenseRecaps->sum('income_amount'),
            'total_expense' => $expenseRecaps->sum('expense_amount'),
        ];
        $totals->balance = $totals->total_income - $totals->total_expense;

        $month = $request->get('month');
        $year = $request->get('year');

        $filename = 'Rekap_Pengeluaran_' . date('Y-m-d') . '.xlsx';

        return Excel::download(
            new ExpenseRecapExport($expenseRecaps, $month, $year, null, $totals),
            $filename
        );
    }

    /**
     * Export rekap pengeluaran ke PDF.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf(Request $request)
    {
        $expenseRecaps = $this->service->buildExportQuery($request)->get();

        $totals = (object) [
            'total_income' => $expenseRecaps->sum('income_amount'),
            'total_expense' => $expenseRecaps->sum('expense_amount'),
        ];
        $totals->balance = $totals->total_income - $totals->total_expense;

        $periodTitle = $this->service->buildPeriodTitle($request);

        $pdf = Pdf::loadView('exports.finance.expense-report-pdf', [
            'expenseRecaps' => $expenseRecaps,
            'totals' => $totals,
            'periodTitle' => $periodTitle,
        ])->setPaper('a4', 'landscape');

        $filename = 'Rekap_Pengeluaran_' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}
