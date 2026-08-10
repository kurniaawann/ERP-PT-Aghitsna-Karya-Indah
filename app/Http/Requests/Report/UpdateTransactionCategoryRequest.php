<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request untuk validasi update Kategori Transaksi.
 *
 * Memastikan data yang diterima valid sebelum diproses oleh Service.
 */
class UpdateTransactionCategoryRequest extends FormRequest
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
     * Aturan validasi untuk update Kategori Transaksi.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $categoryId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z_]+$/',
                Rule::unique('transaction_categories', 'code')->ignore($categoryId),
            ],
            'type' => ['required', 'string', 'in:INCOME,EXPENSE'],
            'module' => ['required', 'string', 'in:expense_recap,project_finance'],
            'sort_order' => ['required', 'integer', 'min:1'],
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
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 100 karakter.',
            'code.required' => 'Kode kategori wajib diisi.',
            'code.max' => 'Kode kategori maksimal 50 karakter.',
            'code.regex' => 'Kode kategori harus berformat HURUF_BESAR_UNDERSCORE.',
            'code.unique' => 'Kode kategori sudah digunakan!',
            'type.required' => 'Tipe kategori wajib dipilih.',
            'type.in' => 'Tipe kategori tidak valid.',
            'module.required' => 'Modul kategori wajib dipilih.',
            'module.in' => 'Modul kategori tidak valid.',
            'sort_order.required' => 'Urutan wajib diisi.',
            'sort_order.integer' => 'Urutan harus berupa angka bulat.',
            'sort_order.min' => 'Urutan minimal 1.',
        ];
    }
}
