<?php

namespace App\Observers;

use App\Models\Report\SalesRecap;
use App\Models\Report\ExpenseRecap;
use App\Models\Report\TransactionCategory;
use App\Models\Inventory\ItemStockOut;
use App\Services\Finance\PaymentProofService;

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
     * Update stock out records and auto-create expense recap when status becomes LUNAS.
     * Always recreate stock outs to ensure consistency.
     */
    public function updated(SalesRecap $salesRecap): void
    {
        // Always recreate stock outs whenever SalesRecap is updated
        // This ensures that even if only date/name_proyek changed, stock outs are synced
        $this->createStockOuts($salesRecap);

        if ($salesRecap->wasChanged('status') && $salesRecap->status === 'Lunas') {
            $salesRecapId = $salesRecap->getKey();

            $existingExpenseRecap = ExpenseRecap::where('sales_recap_id', $salesRecapId)->first();

            if (!$existingExpenseRecap) {
                $incomeCategory = TransactionCategory::where('code', 'UANG_MASUK')->first();

                if ($incomeCategory) {
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

        foreach ($salesRecap->paymentProofs as $proof) {
            app(PaymentProofService::class)->delete($proof->file_path);
            $proof->delete();
        }
    }

    /**
     * Create stock out records from sales recap items
     */
    private function createStockOuts(SalesRecap $salesRecap): void
    {
        $salesRecapId = $salesRecap->getKey();
        $items = is_string($salesRecap->items) ? json_decode($salesRecap->items, true) : $salesRecap->items;

        ItemStockOut::where('id_sales_recap', $salesRecapId)->delete();

        if ($items && is_array($items)) {
            foreach ($items as $item) {
                if ($this->isFromStock($item['from_stock'] ?? null) && !empty($item['id_item'])) {
                    ItemStockOut::create([
                        'id_stock_out' => $this->generateStockOutId(),
                        'id_item' => $item['id_item'],
                        'quantity' => $item['quantity'] ?? 0,
                        'id_sales_recap' => $salesRecapId,
                        'date' => $salesRecap->date ?? now(),
                        'project_name' => $salesRecap->name_proyek,
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
        $date = date('Ymd');
        $count = ItemStockOut::whereDate('created_at', date('Y-m-d'))->count() + 1;
        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);

        do {
            $id = 'SOUT-' . $date . '-' . $sequence;
            $sequence = str_pad((int) $sequence + 1, 4, '0', STR_PAD_LEFT);
        } while (ItemStockOut::where('id_stock_out', $id)->exists());

        return $id;
    }

    private function isFromStock($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

}
