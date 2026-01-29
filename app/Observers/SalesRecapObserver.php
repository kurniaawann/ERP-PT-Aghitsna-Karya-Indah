<?php

namespace App\Observers;

use App\Models\Report\SalesRecap;
use App\Models\Report\ExpenseRecap;
use App\Models\Report\TransactionCategory;

class SalesRecapObserver
{
    /**
     * Handle the SalesRecap "updated" event.
     * Auto-create expense recap when status becomes LUNAS
     */
    public function updated(SalesRecap $salesRecap): void
    {
        // Cek apakah status berubah menjadi LUNAS
        if ($salesRecap->isDirty('status') && $salesRecap->status === 'Lunas') {
            // Get primary key value
            $salesRecapId = $salesRecap->getKey();

            // Cek apakah sudah ada expense recap untuk sales recap ini
            $existingExpenseRecap = ExpenseRecap::where('sales_recap_id', $salesRecapId)->first();

            if (!$existingExpenseRecap) {
                // Get kategori "UANG MASUK PENJUALAN"
                $incomeCategory = TransactionCategory::where('code', 'UANG_MASUK')->first();

                if ($incomeCategory) {
                    // Auto-create expense recap
                    ExpenseRecap::create([
                        'transaction_category_id' => $incomeCategory->id,
                        'invoice_number' => $salesRecapId,
                        'transaction_date' => $salesRecap->date ?? now(),
                        'description' => $salesRecap->name_proyek ?? 'Penjualan - ' . $salesRecapId,
                        'income_amount' => $salesRecap->total_selling,
                        'expense_amount' => null,
                        'money_source' => null,
                        'sales_recap_id' => $salesRecapId,
                        'notes' => 'Auto-generated from sales recap',
                    ]);
                }
            }
        }
    }
}
