<?php

namespace App\Http\Requests\Sdm;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Sdm\Executive;

/**
 * Form request untuk penyimpanan data petinggi baru.
 *
 * Memastikan nama dan jabatan terisi, peran opsional dipilih dari daftar
 * blok tanda tangan, serta gambar tanda tangan berupa file gambar yang
 * valid bila diunggah.
 */
class StoreExecutiveRequest extends FormRequest
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
            'name' => 'required|string|max:150',
            'position' => 'nullable|string|max:150',
            'role' => ['nullable', 'string', 'in:' . implode(',', array_keys(Executive::ROLE_LABELS))],
            'signature_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
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
            'name.required' => 'Nama petinggi harus diisi',
            'position.max' => 'Jabatan maksimal 150 karakter.',
            'role.in' => 'Peran tanda tangan tidak valid.',
            'signature_image.image' => 'File harus berupa gambar.',
            'signature_image.mimes' => 'Format gambar harus JPG, JPEG, PNG, GIF, atau WEBP.',
            'signature_image.max' => 'Ukuran gambar maksimal 5MB.',
        ];
    }
}
