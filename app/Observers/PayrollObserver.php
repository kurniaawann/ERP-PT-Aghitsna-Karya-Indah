<?php

namespace App\Observers;

use App\Models\Sdm\Payroll;
use App\Models\Notification\SalaryReminder;

class PayrollObserver
{
    /**
     * Handle the Payroll "updated" event.
     * 
     * Sync status Payroll ke SalaryReminder ketika status berubah
     */
    public function updated(Payroll $payroll): void
    {
        // Cek apakah status payroll berubah
        if ($payroll->isDirty('status')) {
            $oldStatus = $payroll->getOriginal('status');
            $newStatus = $payroll->status;

            // Jika status berubah dari 'draft' menjadi 'paid'
            if ($oldStatus === 'draft' && $newStatus === 'paid') {
                // Update SalaryReminder yang terkait dengan payroll ini
                SalaryReminder::where('payroll_id', $payroll->id)
                    ->update([
                        'status' => 'paid',
                        'notification_sent_at' => now(),
                    ]);
            }
            // Jika status berubah dari 'paid' kembali ke 'draft' (reverse)
            elseif ($oldStatus === 'paid' && $newStatus === 'draft') {
                // Revert SalaryReminder status kembali ke 'draft'
                SalaryReminder::where('payroll_id', $payroll->id)
                    ->update([
                        'status' => 'draft',
                        'notification_sent_at' => null,
                    ]);
            }
        }
    }

    /**
     * Handle the Payroll "created" event.
     */
    public function created(Payroll $payroll): void
    {
        //
    }

    /**
     * Handle the Payroll "deleted" event.
     */
    public function deleted(Payroll $payroll): void
    {
        //
    }

    /**
     * Handle the Payroll "restored" event.
     */
    public function restored(Payroll $payroll): void
    {
        //
    }

    /**
     * Handle the Payroll "force deleted" event.
     */
    public function forceDeleted(Payroll $payroll): void
    {
        //
    }
}
