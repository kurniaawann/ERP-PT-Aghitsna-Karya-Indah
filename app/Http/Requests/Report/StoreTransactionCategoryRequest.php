<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembuatan Kategori Transaksi baru.
 *
 * Memastikan data yang diterima valid sebelum diproses oleh Service.
 */
class StoreTransactionCategoryRequest extends FormRequest
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
     * Aturan validasi untuk store Kategori Transaksi.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z_]+$/', 'unique:transaction_categories,code'],
            'type' => ['required', 'string', 'in:INCOME,EXPENSE'],
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
        ];
    }
}
