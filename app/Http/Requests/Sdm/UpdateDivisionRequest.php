<?php

namespace App\Http\Requests\Sdm;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request untuk pembaruan data divisi.
 *
 * Divisi diidentifikasi melalui route model binding.
 * Aturan validasi unik mengecualikan ID divisi saat ini.
 */
class UpdateDivisionRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna berwenang melakukan permintaan ini.
     *
     * Otorisasi ditangani di level route melalui middleware('role:admin').
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
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:divisions,name,' . $this->route('division')->id,
            'description' => 'nullable|string|max:500',
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
            'name.required' => 'Nama divisi harus diisi',
            'name.unique' => 'Nama divisi sudah ada',
        ];
    }
}
