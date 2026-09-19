<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StorePaymentProofRequest;
use App\Models\Finance\PaymentProof;
use App\Services\Finance\PaymentProofService;
use Illuminate\Http\Request;

/**
 * Controller untuk modul Bukti Pembayaran (Payment Proof).
 *
 * Menangani HTTP request untuk operasi data bukti pembayaran.
 * Seluruh business logic didelegasikan ke PaymentProofService.
 *
 * Catatan: Tidak ada halaman index mandiri lagi. Bukti pembayaran dikelola
 * dari dalam modal Edit tiap modul (upload & hapus) — endpoint di bawah hanya
 * dipakai untuk operasi data, bukan render halaman.
 *
 * Upload & delete sekarang mendukung AJAX (mengembalikan JSON) agar pesan
 * validasi (mis. nominal melebihi sisa tagihan) tampil di dalam modal tanpa
 * menutup modal maupun me-reload halaman.
 */
class PaymentProofController extends Controller
{
    public function __construct(
        private PaymentProofService $service
    ) {}

    /**
     * Menyimpan bukti pembayaran baru.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(StorePaymentProofRequest $request)
    {
        $result = $this->service->store($request->validated(), $request->file('proof_image'));

        if ($request->ajax()) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ], $result['success'] ? 200 : 422);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Menghapus bukti pembayaran tunggal.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroy(PaymentProof $payment_proof, Request $request)
    {
        $result = $this->service->destroy($payment_proof);

        if ($request->ajax()) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ], $result['success'] ? 200 : 422);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
