<?php

namespace App\Http\Requests\Sdm;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request untuk penghapusan data kasbon secara bulk.
 *
 * Memvalidasi bahwa setidaknya satu kode kasbon dipilih
 * sebelum memproses operasi hapus.
 *
 * Otorisasi ditangani di level route melalui middleware('role:admin').
 */
class DestroySelectedKasbonRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna berwenang melakukan permintaan ini.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendapatkan aturan validasi yang berlaku untuk permintaan ini.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'selected_kasbons' => 'required|array|min:1',
            'selected_kasbons.*' => 'string|exists:kasbons,kasbon_code',
        ];
    }

    /**
     * Mendapatkan pesan validasi kustom.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'selected_kasbons.required' => 'Tidak ada data yang dipilih untuk dihapus.',
            'selected_kasbons.min' => 'Tidak ada data yang dipilih untuk dihapus.',
            'selected_kasbons.*.exists' => 'Salah satu kasbon yang dipilih tidak valid.',
        ];
    }
}
