<?php

namespace App\Observers;

use App\Models\Report\SalesRecap;
use App\Models\Report\ExpenseRecap;
use App\Models\Report\TransactionCategory;
use App\Models\Inventory\ItemStockOut;

class SalesRecapObserver
{
    /**
     * Handle the SalesRecap "created" event.
     * Auto-create stock out records
     */
    public function created(SalesRecap $salesRecap): void
    {
        $this->createStockOuts($salesRecap);
    }

    /**
     * Handle the SalesRecap "updated" event.
     * Update stock out records and auto-create expense recap when status becomes LUNAS
     */
    public function updated(SalesRecap $salesRecap): void
    {
        // Update stock out records
        $this->createStockOuts($salesRecap);

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

    /**
     * Handle the SalesRecap "deleted" event.
     * Auto-delete stock out records
     */
    public function deleted(SalesRecap $salesRecap): void
    {
        ItemStockOut::where('id_sales_recap', $salesRecap->getKey())->delete();
    }

    /**
     * Create stock out records from sales recap items
     */
    private function createStockOuts(SalesRecap $salesRecap): void
    {
        $salesRecapId = $salesRecap->getKey();
        $items = is_string($salesRecap->items) ? json_decode($salesRecap->items, true) : $salesRecap->items;

        // Delete existing stock outs for this sales recap
        ItemStockOut::where('id_sales_recap', $salesRecapId)->delete();

        // Create new stock outs for each item that comes from stock
        if ($items && is_array($items)) {
            foreach ($items as $item) {
                // Only create stock out if item comes from stock and has valid id_item
                if (($item['from_stock'] ?? false) && !empty($item['id_item'])) {
                    ItemStockOut::create([
                        'id_stock_out' => $this->generateStockOutId(),
                        'id_item' => $item['id_item'],
                        'quantity' => $item['quantity'] ?? 0,
                        'id_sales_recap' => $salesRecapId,
                        'tanggal' => $salesRecap->date ?? now(),
                    ]);
                }
            }
        }
    }

    /**
     * Generate unique stock out ID
     */
    private function generateStockOutId(): string
    {
        return 'SOUT-' . date('Ymd') . '-0001';
    }

}
