<?php

namespace App\Observers;

use App\Models\Report\SalesReport;
use App\Models\Report\ExpenseReport;
use App\Models\TransactionCategory;

class SalesReportObserver
{
    /**
     * Handle the SalesReport "updated" event.
     * Auto-create expense report when status becomes LUNAS
     */
    public function updated(SalesReport $salesReport): void
    {
        // Cek apakah status berubah menjadi LUNAS
        if ($salesReport->isDirty('status') && $salesReport->status === 'Lunas') {
            // Get primary key value
            $salesReportId = $salesReport->getKey();

            // Cek apakah sudah ada expense report untuk sales report ini
            $existingExpenseReport = ExpenseReport::where('sales_report_id', $salesReportId)->first();

            if (!$existingExpenseReport) {
                // Get kategori "UANG MASUK PENJUALAN"
                $incomeCategory = TransactionCategory::where('code', 'UANG_MASUK')->first();

                if ($incomeCategory) {
                    // Auto-create expense report
                    ExpenseReport::create([
                        'transaction_category_id' => $incomeCategory->id,
                        'invoice_number' => $salesReportId,
                        'transaction_date' => $salesReport->date ?? now(),
                        'description' => $salesReport->name_proyek ?? 'Penjualan - ' . $salesReportId,
                        'income_amount' => $salesReport->total_selling,
                        'expense_amount' => null,
                        'money_source' => null,
                        'sales_report_id' => $salesReportId,
                        'notes' => 'Auto-generated from sales report',
                    ]);
                }
            }
        }
    }
}
