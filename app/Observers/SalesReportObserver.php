<?php

namespace App\Observers;

use App\Models\SalesReport;
use App\Models\ExpenseReport;
use App\Models\TransactionCategory;

class SalesReportObserver
{
    /**
     * Handle the SalesReport "updated" event.
     * Auto-create expense report when status becomes LUNAS
     */
    public function updated(SalesReport $salesReport): void
    {
        // Cek apakah property status ada dan nilainya LUNAS
        if (isset($salesReport->status) && $salesReport->status === 'Lunas') {
            // Cek apakah sudah ada expense report untuk sales report ini
            $existingExpenseReport = ExpenseReport::where('sales_report_id', $salesReport->id_sales_report)->first();

            if (!$existingExpenseReport) {
                // Get kategori "UANG MASUK PENJUALAN"
                $incomeCategory = TransactionCategory::where('code', 'UANG_MASUK')->first();

                if ($incomeCategory) {
                    // Auto-create expense report
                    ExpenseReport::create([
                        'transaction_category_id' => $incomeCategory->id,
                        'invoice_number' => $salesReport->id_sales_report,
                        'transaction_date' => $salesReport->date ?? now(),
                        'description' => $salesReport->name_proyek ?? 'Penjualan - ' . $salesReport->id_sales_report,
                        'income_amount' => $salesReport->total_selling,
                        'expense_amount' => null,
                        'money_source' => null,
                        'sales_report_id' => $salesReport->id_sales_report,
                        'created_by' => null, // Auto-generated, no user
                        'notes' => 'Auto-generated from sales report',
                    ]);
                }
            }
        }
    }
}
