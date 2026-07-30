<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembaruan Bukti Pembayaran.
 *
 * Hanya memvalidasi gambar bukti pembayaran karena field lain bersifat read-only.
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
     * Aturan validasi untuk update Payment Proof (hanya gambar).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'proof_image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
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
            'proof_image.required' => 'Gambar bukti pembayaran wajib diunggah.',
            'proof_image.image'    => 'File harus berupa gambar.',
            'proof_image.mimes'    => 'Format gambar harus JPG, JPEG, PNG, GIF, atau WEBP.',
            'proof_image.max'      => 'Ukuran gambar maksimal 5MB.',
        ];
    }
}
