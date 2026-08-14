<?php

namespace App\Http\Requests\Finance;

use App\Services\InputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembuatan Reimburse baru.
 *
 * Memastikan semua field wajib terisi dengan format yang benar.
 */
class StoreReimburseRequest extends FormRequest
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
     * Aturan validasi untuk store Reimburse.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date'                => 'required|date',
            'project_name'        => 'required|string|max:255',
            'expense_description' => 'required|string',
            'total_amount'        => 'required|integer|min:0',
            'due_date'            => 'required|date|after_or_equal:date',
            'notes'               => 'nullable|string',
        ];
    }

    /**
     * Mempersiapkan data sebelum validasi.
     *
     * Menormalisasi total_amount dari format mata uang bertitik
     * (misal "10.000.000.000") menjadi integer (10000000000) sebelum
     * aturan validasi integer diterapkan.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'total_amount' => InputNormalizer::normalizeCurrency($this->total_amount),
        ]);
    }

    /**
     * Pesan error validasi dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required'                => 'Tanggal wajib diisi.',
            'date.date'                    => 'Format tanggal tidak valid.',
            'project_name.required'        => 'Nama proyek wajib diisi.',
            'project_name.max'             => 'Nama proyek maksimal 255 karakter.',
            'expense_description.required' => 'Keterangan belanja wajib diisi.',
            'total_amount.required'        => 'Total amount wajib diisi.',
            'total_amount.integer'         => 'Total amount harus berupa angka.',
            'total_amount.min'             => 'Total amount minimal 0.',
            'due_date.required'            => 'Tanggal jatuh tempo wajib diisi.',
            'due_date.date'                => 'Format tanggal jatuh tempo tidak valid.',
            'due_date.after_or_equal'      => 'Tanggal jatuh tempo harus hari ini atau setelahnya.',
        ];
    }
}
