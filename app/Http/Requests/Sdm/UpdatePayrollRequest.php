<?php

namespace App\Http\Requests\Sdm;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request untuk pembaruan data payroll draft.
 *
 * Hanya data payroll draft yang dapat diperbarui. Validasi memastikan
 * bahwa project_name selalu diberikan.
 */
class UpdatePayrollRequest extends FormRequest
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
            'project_name' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
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
            'project_name.required' => 'Nama proyek tidak boleh kosong.',
            'project_name.string' => 'Nama proyek harus berupa teks.',
            'project_name.max' => 'Nama proyek maksimal 255 karakter.',
            'notes.string' => 'Catatan harus berupa teks.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }

    /**
     * Mempersiapkan data untuk validasi.
     *
     * Memotong spasi dan menormalisasi string kosong menjadi null.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('notes')) {
            $notes = $this->input('notes');
            $this->merge([
                'notes' => !empty(trim($notes)) ? $notes : null,
            ]);
        }
    }
}
