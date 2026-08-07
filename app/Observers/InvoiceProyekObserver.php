<?php

namespace App\Observers;

use App\Models\Finance\InvoiceProyek;
use App\Services\Finance\PaymentProofService;

/**
 * Observer untuk model InvoiceProyek.
 *
 * Menangani cleanup data terkait pada event deleted.
 */
class InvoiceProyekObserver
{
    /**
     * Handle the InvoiceProyek "deleted" event.
     *
     * Membersihkan file bukti pembayaran terkait invoice yang dihapus.
     *
     * @param  \App\Models\Finance\InvoiceProyek  $invoiceProyek
     * @return void
     */
    public function deleted(InvoiceProyek $invoiceProyek): void
    {
        foreach ($invoiceProyek->paymentProofs as $proof) {
            app(PaymentProofService::class)->delete($proof->file_path);
            $proof->delete();
        }
    }
}
