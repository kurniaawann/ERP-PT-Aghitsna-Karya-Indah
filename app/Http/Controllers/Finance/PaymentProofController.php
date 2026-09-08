<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StorePaymentProofRequest;
use App\Models\Finance\PaymentProof;
use App\Services\Finance\PaymentProofService;

/**
 * Controller untuk modul Bukti Pembayaran (Payment Proof).
 *
 * Menangani HTTP request untuk operasi data bukti pembayaran.
 * Seluruh business logic didelegasikan ke PaymentProofService.
 *
 * Catatan: Tidak ada halaman index mandiri lagi. Bukti pembayaran dikelola
 * dari dalam modal Edit tiap modul (upload & hapus) — endpoint di bawah hanya
 * dipakai untuk operasi data, bukan render halaman.
 */
class PaymentProofController extends Controller
{
    public function __construct(
        private PaymentProofService $service
    ) {}

    /**
     * Menyimpan bukti pembayaran baru.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StorePaymentProofRequest $request)
    {
        $result = $this->service->store($request->validated(), $request->file('proof_image'));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Menghapus bukti pembayaran tunggal.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(PaymentProof $payment_proof)
    {
        $result = $this->service->destroy($payment_proof);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
