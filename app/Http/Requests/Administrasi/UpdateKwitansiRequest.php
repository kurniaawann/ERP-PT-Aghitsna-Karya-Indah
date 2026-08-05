<?php

namespace App\Http\Requests\Administrasi;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi data kwitansi saat proses pembaruan (update).
 *
 * Kelas ini bertanggung jawab atas validasi input dari form edit kwitansi.
 * Validation rules didefinisikan di sini agar terpisah dari Controller (Single Responsibility).
 */
class UpdateKwitansiRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna berwenang melakukan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendefinisikan aturan validasi untuk pembaruan kwitansi.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'received_from' => 'required|string|max:255',
            'payment_for' => 'required|string',
            'amount' => 'required|min:0',
            'remaining' => 'nullable|min:0',
            'include_bank' => 'nullable',
            'is_tunai' => 'nullable',
            'is_cheque' => 'nullable',
            'is_bilyet_giro' => 'nullable',
            'payment_account_id' => 'required_with:include_bank|nullable|exists:payment_accounts,id',
            'kwintansi_date' => 'required|date',
            'location' => 'nullable|string|max:100',
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
            'received_from.required' => 'Sudah terima dari tidak boleh kosong.',
            'received_from.max' => 'Sudah terima dari maksimal 255 karakter.',
            'payment_for.required' => 'Uang pembayaran tidak boleh kosong.',
            'amount.required' => 'Jumlah tidak boleh kosong.',
            'amount.min' => 'Jumlah minimal 0.',
            'remaining.min' => 'Sisa minimal 0.',
            'payment_account_id.required_with' => 'Bank tidak boleh kosong jika menampilkan bank di PDF.',
            'payment_account_id.exists' => 'Bank yang dipilih tidak valid.',
            'kwintansi_date.required' => 'Tanggal tidak boleh kosong.',
            'kwintansi_date.date' => 'Format tanggal tidak valid.',
            'location.max' => 'Lokasi maksimal 100 karakter.',
        ];
    }
}
