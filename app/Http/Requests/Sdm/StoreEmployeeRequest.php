<?php

namespace App\Http\Requests\Sdm;

use App\Services\InputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request untuk penyimpanan data karyawan baru (massal).
 *
 * Mendukung penambahan banyak karyawan sekaligus lewat array `employees`.
 * Memastikan semua kolom yang diperlukan tersedia dengan tipe dan batasan
 * yang tepat sebelum data karyawan disimpan ke basis data.
 */
class StoreEmployeeRequest extends FormRequest
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
            'employees' => 'required|array|min:1',
            'employees.*.name' => 'required|string|max:255',
            'employees.*.position' => 'nullable|string|max:100',
            'employees.*.status' => 'nullable|string|max:100',
            'employees.*.employment_type' => 'nullable|in:harian,bulanan',
            'employees.*.daily_wage' => 'required_unless:employees.*.employment_type,bulanan|nullable|integer|min:0',
            'employees.*.base_salary' => 'required_if:employees.*.employment_type,bulanan|nullable|integer|min:0',
            'employees.*.transport_rate' => 'nullable|integer|min:0',
            'employees.*.meal_rate' => 'nullable|integer|min:0',
            'employees.*.ump' => 'nullable|integer|min:0',
            'employees.*.division' => 'nullable|string|max:100',
            'employees.*.project_name' => 'nullable|string|max:255',
            'employees.*.phone' => 'nullable|string|max:20',
            'employees.*.address' => 'nullable|string',
        ];
    }

    /**
     * Mempersiapkan data sebelum validasi.
     *
     * Menormalisasi daily_wage/base_salary tiap karyawan dari format string
     * mata uang (misalnya "150.000") menjadi integer (misalnya 150000) serta
     * memastikan employment_type memiliki nilai default 'harian' sebelum
     * aturan validasi diterapkan.
     */
    protected function prepareForValidation(): void
    {
        $employees = $this->input('employees');

        if (! is_array($employees) || empty($employees)) {
            return;
        }

        foreach ($employees as $index => $employee) {
            $employees[$index]['employment_type'] = $employee['employment_type'] ?? 'harian';
            $employees[$index]['daily_wage'] = InputNormalizer::normalizeCurrency(
                $employee['daily_wage'] ?? null
            );
            $employees[$index]['base_salary'] = InputNormalizer::normalizeCurrency(
                $employee['base_salary'] ?? null
            );
            $employees[$index]['transport_rate'] = InputNormalizer::normalizeCurrency(
                $employee['transport_rate'] ?? null
            );
            $employees[$index]['meal_rate'] = InputNormalizer::normalizeCurrency(
                $employee['meal_rate'] ?? null
            );
            $employees[$index]['ump'] = InputNormalizer::normalizeCurrency(
                $employee['ump'] ?? null
            );
        }

        $this->merge(['employees' => $employees]);
    }
}
