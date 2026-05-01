<?php

namespace App\Observers;

use App\Models\Finance\InvoiceProyek;
use App\Models\Notification\InvoiceProyekReminder;
use Carbon\Carbon;

class InvoiceProyekObserver
{
    /**
     * Handle the InvoiceProyek "created" event.
     * 
     * Buat reminder jatuh tempo ketika invoice proyek dibuat
     * Jatuh tempo = 1 bulan dari tanggal invoice
     */
    public function created(InvoiceProyek $invoiceProyek): void
    {
        // Hitung reminder_date = 1 bulan setelah invoice_date
        $invoiceDate = Carbon::parse($invoiceProyek->invoice_date);
        $reminderDate = $invoiceDate->addMonth();

        // Buat reminder
        InvoiceProyekReminder::create([
            'invoice_number' => $invoiceProyek->invoice_number,
            'invoice_date' => $invoiceProyek->invoice_date,
            'recipient' => $invoiceProyek->recipient,
            'total_amount' => $invoiceProyek->total_after_discount ?? $invoiceProyek->total_amount,
            'reminder_date' => $reminderDate,
            'status' => 'pending',
            'notes' => 'Reminder jatuh tempo invoice proyek',
        ]);
    }

    /**
     * Handle the InvoiceProyek "updated" event.
     */
    public function updated(InvoiceProyek $invoiceProyek): void
    {
        // Update reminder jika data invoice berubah
        $reminder = InvoiceProyekReminder::where('invoice_number', $invoiceProyek->invoice_number)->first();

        if ($reminder) {
            $reminder->update([
                'invoice_date' => $invoiceProyek->invoice_date,
                'recipient' => $invoiceProyek->recipient,
                'total_amount' => $invoiceProyek->total_after_discount ?? $invoiceProyek->total_amount,
            ]);

            // Recalculate reminder_date jika invoice_date berubah
            if ($invoiceProyek->isDirty('invoice_date')) {
                $invoiceDate = Carbon::parse($invoiceProyek->invoice_date);
                $reminderDate = $invoiceDate->addMonth();
                $reminder->update(['reminder_date' => $reminderDate]);
            }
        }
    }

    /**
     * Handle the InvoiceProyek "deleted" event.
     */
    public function deleted(InvoiceProyek $invoiceProyek): void
    {
        // Hapus reminder ketika invoice dihapus
        InvoiceProyekReminder::where('invoice_number', $invoiceProyek->invoice_number)->delete();
    }

    /**
     * Handle the InvoiceProyek "restored" event.
     */
    public function restored(InvoiceProyek $invoiceProyek): void
    {
        //
    }

    /**
     * Handle the InvoiceProyek "force deleted" event.
     */
    public function forceDeleted(InvoiceProyek $invoiceProyek): void
    {
        //
    }
}
