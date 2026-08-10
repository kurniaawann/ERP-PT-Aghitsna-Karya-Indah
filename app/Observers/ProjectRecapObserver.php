<?php

namespace App\Observers;

use App\Models\Finance\ProjectRecap;
use App\Services\Finance\PaymentProofService;

/**
 * Observer untuk model ProjectRecap.
 *
 * Menangani cleanup data terkait pada event deleted.
 */
class ProjectRecapObserver
{
    /**
     * Handle the ProjectRecap "deleted" event.
     *
     * Membersihkan file bukti pembayaran terkait rekap proyek yang dihapus.
     */
    public function deleted(ProjectRecap $projectRecap): void
    {
        foreach ($projectRecap->paymentProofs as $proof) {
            app(PaymentProofService::class)->delete($proof->file_path);
            $proof->delete();
        }
    }
}
