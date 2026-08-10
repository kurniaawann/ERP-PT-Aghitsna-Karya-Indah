<?php

namespace App\Observers;

use App\Models\Administrasi\Kwintansi;
use App\Models\Finance\PaymentProof;
use App\Services\Report\ProjectFinancialReportService;

/**
 * Observer untuk model PaymentProof.
 *
 * Menjaga konsistensi saat bukti pembayaran berubah:
 * - Kwitansi yang dibuat otomatis dari bukti tersebut (payment_proof_id)
 *   ikut dihapus, karena kwitansi otomatis tidak bisa dihapus terpisah.
 * - Bukti pembayaran tipe 'recap' disinkronkan ke Laporan Keuangan Proyek:
 *   dibuat/diperbarui saat bukti dibuat atau diubah, dihapus saat bukti
 *   dihapus (lihat ProjectFinancialReportService::syncFromPaymentProof).
 */
class PaymentProofObserver
{
    /**
     * Handle the PaymentProof "created" event.
     */
    public function created(PaymentProof $paymentProof): void
    {
        app(ProjectFinancialReportService::class)->syncFromPaymentProof($paymentProof);
    }

    /**
     * Handle the PaymentProof "updated" event.
     */
    public function updated(PaymentProof $paymentProof): void
    {
        app(ProjectFinancialReportService::class)->syncFromPaymentProof($paymentProof);
    }

    /**
     * Handle the PaymentProof "deleting" event.
     */
    public function deleting(PaymentProof $paymentProof): void
    {
        Kwintansi::query()
            ->where('payment_proof_id', $paymentProof->id)
            ->delete();

        app(ProjectFinancialReportService::class)->deleteFromPaymentProof($paymentProof);
    }
}
