<?php

namespace App\Http\Requests\Administrasi;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi data bukti kas keluar saat proses pembaruan (update).
 *
 * Kelas ini bertanggung jawab atas validasi input dari form edit bukti kas keluar.
 * Validation rules didefinisikan di sini agar terpisah dari Controller (Single Responsibility).
 */
class UpdateCashOutProofRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna berwenang melakukan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendefinisikan aturan validasi untuk pembaruan bukti kas keluar.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'paid_to' => 'required|string|max:255',
            'amount' => 'required|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'director' => 'nullable|string|max:255',
            'finance_head' => 'nullable|string|max:255',
            'template_type' => 'required|in:standard,hollow',
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
            'paid_to.required' => 'Dibayarkan kepada tidak boleh kosong.',
            'paid_to.max' => 'Dibayarkan kepada maksimal 255 karakter.',
            'amount.required' => 'Jumlah tidak boleh kosong.',
            'amount.min' => 'Jumlah minimal 0.',
            'date.required' => 'Tanggal tidak boleh kosong.',
            'date.date' => 'Format tanggal tidak valid.',
            'director.max' => 'Nama direktur maksimal 255 karakter.',
            'finance_head.max' => 'Nama kabag keuangan maksimal 255 karakter.',
            'template_type.required' => 'Tipe template tidak boleh kosong.',
            'template_type.in' => 'Tipe template harus standard atau hollow.',
        ];
    }
}
