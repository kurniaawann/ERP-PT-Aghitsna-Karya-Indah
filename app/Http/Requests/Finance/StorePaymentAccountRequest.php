<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembuatan Rekening Pembayaran baru.
 *
 * Memastikan semua field wajib terisi dengan format yang benar.
 */
class StorePaymentAccountRequest extends FormRequest
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
     * Aturan validasi untuk store Rekening Pembayaran.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bank_name'      => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_holder' => 'required|string|max:255',
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
            'bank_name.required'      => 'Nama bank wajib diisi.',
            'bank_name.max'           => 'Nama bank maksimal 255 karakter.',
            'account_number.required' => 'Nomor rekening wajib diisi.',
            'account_number.max'      => 'Nomor rekening maksimal 255 karakter.',
            'account_holder.required' => 'Nama pemilik rekening wajib diisi.',
            'account_holder.max'      => 'Nama pemilik rekening maksimal 255 karakter.',
        ];
    }
}
