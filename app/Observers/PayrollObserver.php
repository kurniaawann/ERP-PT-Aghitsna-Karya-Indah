<?php

namespace App\Observers;

use App\Models\Sdm\Payroll;
use App\Models\Notification\SalaryReminder;

/**
 * Observer untuk event model Payroll.
 *
 * Menyinkronkan perubahan status payroll dengan data SalaryReminder
 * agar sistem notifikasi tetap sinkron dengan status payroll.
 *
 * Transisi status yang ditangani:
 * - draft → paid:   Memperbarui status SalaryReminder menjadi 'paid'
 * - paid → draft:   Mengembalikan status SalaryReminder menjadi 'draft'
 */
class PayrollObserver
{
    /**
     * Tangani event "created" pada Payroll.
     *
     * @param  Payroll  $payroll
     * @return void
     */
    public function created(Payroll $payroll): void
    {
        //
    }

    /**
     * Tangani event "updated" pada Payroll.
     *
     * Menyinkronkan perubahan status ke SalaryReminder:
     * - draft → paid: Menandai pengingat sebagai sudah dibayar dan mengatur timestamp notifikasi
     * - paid → draft: Mengembalikan status pengingat dan mengosongkan timestamp notifikasi
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
     * Tangani event "deleted" pada Payroll.
     *
     * @param  Payroll  $payroll
     * @return void
     */
    public function deleted(Payroll $payroll): void
    {
        //
    }

    /**
     * Tangani event "restored" pada Payroll.
     *
     * @param  Payroll  $payroll
     * @return void
     */
    public function restored(Payroll $payroll): void
    {
        //
    }

    /**
     * Tangani event "force deleted" pada Payroll.
     *
     * @param  Payroll  $payroll
     * @return void
     */
    public function forceDeleted(Payroll $payroll): void
    {
        //
    }
}
