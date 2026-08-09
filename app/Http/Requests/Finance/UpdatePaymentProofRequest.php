<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembaruan Bukti Pembayaran.
 *
 * Memvalidasi tanggal pembayaran (manual) dan gambar bukti pembayaran.
 * Gambar bersifat opsional; pengguna dapat mengubah tanggal saja.
 */
class UpdatePaymentProofRequest extends FormRequest
{
    /**
     * Menentukan apakah user memiliki otorisasi untuk melakukan request ini.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk update Payment Proof.
     *
     * Gambar bersifat opsional (nullable) agar pengguna dapat mengubah
     * tanggal pembayaran (payment_date) tanpa harus mengganti gambar.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_date' => ['nullable', 'date'],
            'proof_image'  => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ];
    }

    /**
     * Pesan error validasi dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_date.date'    => 'Format tanggal pembayaran tidak valid.',
            'proof_image.image'    => 'File harus berupa gambar.',
            'proof_image.mimes'    => 'Format gambar harus JPG, JPEG, PNG, GIF, atau WEBP.',
            'proof_image.max'      => 'Ukuran gambar maksimal 5MB.',
        ];
    }
}
