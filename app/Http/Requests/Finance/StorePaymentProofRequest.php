<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request untuk validasi upload Bukti Pembayaran baru.
 *
 * Memastikan semua field wajib terisi dengan format yang benar.
 */
class StorePaymentProofRequest extends FormRequest
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
     * Aturan validasi untuk store Payment Proof.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $allowedInvoiceTypes = auth()->user()?->role === 'admin'
            ? ['proyek', 'rekap_penjualan']
            : ['proyek', 'alumunium', 'rekap_penjualan'];

        return [
            'module_type'  => ['required', Rule::in(['finance'])],
            'invoice_type' => ['required', Rule::in($allowedInvoiceTypes)],
            'invoice_number' => ['required', 'string'],
            'amount'       => ['nullable'],
            'payment_date' => ['nullable', 'date'],
            'proof_image'  => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
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
            'module_type.required'  => 'Tipe modul wajib diisi.',
            'module_type.in'        => 'Tipe modul tidak valid.',
            'invoice_type.required' => 'Tipe invoice wajib diisi.',
            'invoice_type.in'       => 'Tipe invoice tidak valid.',
            'invoice_number.required' => 'Nomor invoice wajib diisi.',
            'payment_date.date'       => 'Format tanggal pembayaran tidak valid.',
            'proof_image.required'  => 'Bukti pembayaran wajib diupload.',
            'proof_image.image'     => 'File harus berupa gambar.',
            'proof_image.mimes'     => 'Format gambar harus JPG, JPEG, PNG, GIF, atau WEBP.',
            'proof_image.max'       => 'Ukuran gambar maksimal 5MB.',
        ];
    }
}
