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
            'position' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:100',
            'employment_type' => 'nullable|in:harian,bulanan',
            'daily_wage' => 'required_unless:employment_type,bulanan|nullable|integer|min:0',
            'base_salary' => 'required_if:employment_type,bulanan|nullable|integer|min:0',
            'transport_rate' => 'nullable|integer|min:0',
            'meal_rate' => 'nullable|integer|min:0',
            'ump' => 'nullable|integer|min:0',
            'division' => 'nullable|string|max:100',
            'project_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ];
    }

    /**
     * Mempersiapkan data sebelum validasi.
     *
     * Menormalisasi daily_wage/base_salary dari format string mata uang
     * (misalnya "150.000") menjadi integer (misalnya 150000) serta memastikan
     * employment_type memiliki nilai default 'harian'.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'employment_type' => $this->employment_type ?? 'harian',
            'daily_wage' => InputNormalizer::normalizeCurrency($this->daily_wage),
            'base_salary' => InputNormalizer::normalizeCurrency($this->base_salary),
            'transport_rate' => InputNormalizer::normalizeCurrency($this->transport_rate),
            'meal_rate' => InputNormalizer::normalizeCurrency($this->meal_rate),
            'ump' => InputNormalizer::normalizeCurrency($this->ump),
        ]);
    }
}
