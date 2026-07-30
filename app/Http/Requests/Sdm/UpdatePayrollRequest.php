<?php

namespace App\Http\Requests\Sdm;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request untuk pembaruan data payroll draft.
 *
 * Hanya data payroll draft yang dapat diperbarui. Validasi memastikan
 * bahwa project_name selalu diberikan dan additional_expenses
 * berupa integer non-negatif. Jika additional_expenses > 0,
 * additional_expenses_notes wajib diisi.
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
            'additional_expenses' => 'required|integer|min:0',
            'additional_expenses_notes' => 'nullable|string|max:1000',
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
            'additional_expenses.required' => 'Pengeluaran tambahan tidak boleh kosong.',
            'additional_expenses.integer' => 'Pengeluaran tambahan harus berupa angka.',
            'additional_expenses.min' => 'Pengeluaran tambahan tidak boleh negatif.',
            'additional_expenses_notes.string' => 'Keterangan pengeluaran tambahan harus berupa teks.',
            'additional_expenses_notes.max' => 'Keterangan pengeluaran tambahan maksimal 1000 karakter.',
            'notes.string' => 'Catatan harus berupa teks.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }

    /**
     * Mempersiapkan data untuk validasi.
     *
     * Memotong spasi pada additional_expenses_notes dan menormalisasi string kosong menjadi null.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('additional_expenses_notes')) {
            $notes = $this->input('additional_expenses_notes');
            $this->merge([
                'additional_expenses_notes' => !empty(trim($notes)) ? $notes : null,
            ]);
        }

        if ($this->has('notes')) {
            $notes = $this->input('notes');
            $this->merge([
                'notes' => !empty(trim($notes)) ? $notes : null,
            ]);
        }
    }
}
