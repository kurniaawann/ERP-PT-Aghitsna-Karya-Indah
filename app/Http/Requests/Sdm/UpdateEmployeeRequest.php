<?php

namespace App\Http\Requests\Sdm;

use App\Services\InputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request untuk pembaruan data karyawan.
 *
 * employee_code tidak divalidasi di sini karena diidentifikasi melalui route model
 * binding. Semua kolom lainnya divalidasi untuk memastikan integritas data.
 */
class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna berwenang melakukan permintaan ini.
     *
     * Otorisasi ditangani di level route melalui middleware middleware('role:admin').
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
            'name' => 'required|string|max:255',
            'daily_wage' => 'required|integer|min:0',
            'division' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
        ];
    }

    /**
     * Mempersiapkan data sebelum validasi.
     *
     * Menormalisasi daily_wage dari format string mata uang (misalnya "150.000")
     * menjadi integer (misalnya 150000) sebelum aturan validasi diterapkan.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'daily_wage' => InputNormalizer::normalizeCurrency($this->daily_wage),
        ]);
    }
}
