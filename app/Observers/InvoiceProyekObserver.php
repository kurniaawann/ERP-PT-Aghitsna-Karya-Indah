<?php

namespace App\Observers;

use App\Models\Finance\InvoiceProyek;
use App\Models\Notification\InvoiceProyekReminder;
use App\Services\Finance\InvoiceCalculatorService;
use App\Services\Finance\PaymentProofService;
use Carbon\Carbon;

class InvoiceProyekObserver
{
    /**
     * Handle the InvoiceProyek "created" event.
     *
     * Buat reminder jatuh tempo ketika invoice proyek dibuat.
     */
    public function created(InvoiceProyek $invoiceProyek): void
    {
        $this->syncReminder($invoiceProyek);
    }

    /**
     * Handle the InvoiceProyek "updated" event.
     */
    public function updated(InvoiceProyek $invoiceProyek): void
    {
        $this->syncReminder($invoiceProyek);
    }

    /**
     * Handle the InvoiceProyek "deleted" event.
     */
    public function deleted(InvoiceProyek $invoiceProyek): void
    {
        InvoiceProyekReminder::where('invoice_number', $invoiceProyek->invoice_number)->delete();

        foreach ($invoiceProyek->paymentProofs as $proof) {
            app(PaymentProofService::class)->delete($proof->file_path);
            $proof->delete();
        }
    }

    /**
     * Sinkron reminder dengan data invoice terbaru.
     */
    private function syncReminder(InvoiceProyek $invoiceProyek): void
    {
        $calculator = app(InvoiceCalculatorService::class);
        $netAmount = $calculator->getNetAmount($invoiceProyek);
        $paidAmount = $calculator->getTotalPaidAmount($invoiceProyek);
        $remainingAmount = $calculator->getRemainingAmount($invoiceProyek);
        $reminderDate = Carbon::parse($invoiceProyek->invoice_date)->addMonthNoOverflow();

        $status = 'pending';

        if ($remainingAmount <= 0) {
            $status = 'paid';
        } elseif ($reminderDate->isPast()) {
            $status = 'notified';
        }

        InvoiceProyekReminder::updateOrCreate(
            ['invoice_number' => $invoiceProyek->invoice_number],
            [
                'invoice_date' => $invoiceProyek->invoice_date,
                'recipient' => $invoiceProyek->recipient,
                'total_amount' => $netAmount,
                'reminder_date' => $reminderDate,
                'status' => $status,
                'notification_sent_at' => $status === 'paid' || $status === 'notified' ? now() : null,
                'notes' => 'Reminder jatuh tempo invoice proyek',
            ]
        );
    }
}