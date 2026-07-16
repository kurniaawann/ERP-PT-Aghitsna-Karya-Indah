<?php

namespace App\Observers;

use App\Models\Sdm\Payroll;
use App\Models\Notification\SalaryReminder;

/**
 * Observer for Payroll model events.
 *
 * Syncs payroll status changes with SalaryReminder records
 * to keep the notification system in sync with payroll state.
 *
 * Status transitions handled:
 * - draft → paid:  Updates SalaryReminder status to 'paid'
 * - paid → draft:  Reverts SalaryReminder status to 'draft'
 */
class PayrollObserver
{
    /**
     * Handle the Payroll "created" event.
     *
     * @param  Payroll  $payroll
     * @return void
     */
    public function created(Payroll $payroll): void
    {
        //
    }

    /**
     * Handle the Payroll "updated" event.
     *
     * Syncs status changes to SalaryReminder:
     * - draft → paid: Mark reminder as paid and set notification timestamp
     * - paid → draft: Revert reminder status and clear notification timestamp
     *
     * @param  Payroll  $payroll
     * @return void
     */
    public function updated(Payroll $payroll): void
    {
        if ($payroll->isDirty('status')) {
            $oldStatus = $payroll->getOriginal('status');
            $newStatus = $payroll->status;

            if ($oldStatus === 'draft' && $newStatus === 'paid') {
                SalaryReminder::where('payroll_id', $payroll->id)
                    ->update([
                        'status' => 'paid',
                        'notification_sent_at' => now(),
                    ]);
            } elseif ($oldStatus === 'paid' && $newStatus === 'draft') {
                SalaryReminder::where('payroll_id', $payroll->id)
                    ->update([
                        'status' => 'draft',
                        'notification_sent_at' => null,
                    ]);
            }
        }
    }

    /**
     * Handle the Payroll "deleted" event.
     *
     * @param  Payroll  $payroll
     * @return void
     */
    public function deleted(Payroll $payroll): void
    {
        //
    }

    /**
     * Handle the Payroll "restored" event.
     *
     * @param  Payroll  $payroll
     * @return void
     */
    public function restored(Payroll $payroll): void
    {
        //
    }

    /**
     * Handle the Payroll "force deleted" event.
     *
     * @param  Payroll  $payroll
     * @return void
     */
    public function forceDeleted(Payroll $payroll): void
    {
        //
    }
}
