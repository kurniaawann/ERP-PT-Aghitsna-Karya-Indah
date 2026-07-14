<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi update Rekap Penjualan.
 *
 * Memastikan data yang diterima valid sebelum diproses oleh Service.
 */
class UpdateRecapSalesRequest extends FormRequest
{
    /**
     * Menentukan apakah user memiliki akses ke request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk update.
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'name_proyek' => ['required', 'string', 'max:255'],
            'items' => ['required'],
        ];
    }

    /**
     * Pesan error validasi dalam Bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal tidak boleh kosong!',
            'date.date' => 'Format tanggal tidak valid!',
            'name_proyek.required' => 'Nama proyek tidak boleh kosong!',
            'name_proyek.max' => 'Nama proyek maksimal 255 karakter!',
            'items.required' => 'Minimal harus ada 1 item!',
        ];
    }
}
