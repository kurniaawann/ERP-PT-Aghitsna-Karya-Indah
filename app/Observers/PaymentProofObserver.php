<?php

namespace App\Observers;

use App\Models\Administrasi\Kwintansi;
use App\Models\Finance\PaymentProof;

/**
 * Observer untuk model PaymentProof.
 *
 * Menjaga konsistensi saat bukti pembayaran dihapus: kwitansi yang dibuat
 * otomatis dari bukti tersebut (payment_proof_id) ikut dihapus, karena
 * kwitansi otomatis tidak bisa dihapus terpisah dari modul kwitansi.
 */
class PaymentProofObserver
{
    /**
     * Handle the PaymentProof "deleting" event.
     *
     * @param  \App\Models\Finance\PaymentProof  $paymentProof
     * @return void
     */
    public function deleting(PaymentProof $paymentProof): void
    {
        Kwintansi::query()
            ->where('payment_proof_id', $paymentProof->id)
            ->delete();
    }
}
