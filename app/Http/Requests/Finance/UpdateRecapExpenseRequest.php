<?php

namespace App\Http\Requests\Finance;

use App\Services\InputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi update Rekap Pengeluaran.
 *
 * Memastikan data yang diterima valid sebelum diproses oleh Service.
 */
class UpdateRecapExpenseRequest extends FormRequest
{
    /**
     * Menentukan apakah user memiliki akses ke request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalisasi nilai nominal sebelum validasi.
     *
     * Input jumlah diformat di frontend ke format Indonesia (mis. "1.000.000"),
     * sehingga perlu dinormalisasi ke angka murni agar lolos rule `numeric`.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('expense_amount') && trim((string) $this->input('expense_amount')) !== '') {
            $this->merge([
                'expense_amount' => (string) InputNormalizer::normalizeCurrency($this->input('expense_amount')),
            ]);
        }
    }

    /**
     * Aturan validasi untuk update.
     */
    public function rules(): array
    {
        return [
            'transaction_category_id' => ['required', 'integer', 'exists:transaction_categories,id'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:1000'],
            'expense_amount' => ['required', 'numeric', 'min:0'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'money_source' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Pesan error validasi dalam Bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            'transaction_category_id.required' => 'Kategori tidak boleh kosong!',
            'transaction_category_id.integer' => 'Format kategori tidak valid!',
            'transaction_category_id.exists' => 'Kategori yang dipilih tidak ditemukan!',
            'transaction_date.required' => 'Tanggal tidak boleh kosong!',
            'transaction_date.date' => 'Format tanggal tidak valid!',
            'description.required' => 'Keterangan tidak boleh kosong!',
            'description.max' => 'Keterangan maksimal 1000 karakter!',
            'expense_amount.required' => 'Jumlah tidak boleh kosong!',
            'expense_amount.numeric' => 'Jumlah harus berupa angka!',
            'expense_amount.min' => 'Jumlah tidak boleh negatif!',
            'invoice_number.max' => 'Nomor faktur maksimal 100 karakter!',
            'money_source.max' => 'Sumber uang maksimal 255 karakter!',
        ];
    }
}
