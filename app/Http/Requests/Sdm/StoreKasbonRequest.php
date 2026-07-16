<?php

namespace App\Http\Requests\Sdm;

use App\Services\InputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for storing a new kasbon record.
 *
 * Validates kasbon type, employee/division selection, amount,
 * dates, and period information before creation.
 *
 * Authorization is handled at the route level via middleware('role:admin').
 */
class StoreKasbonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kasbon_type' => 'required|in:personal,team',
            'employee_id' => 'required_if:kasbon_type,personal|nullable|exists:employees,employee_code',
            'division' => 'required_if:kasbon_type,team|nullable|string|max:100',
            'amount' => 'required|integer|min:1000',
            'kasbon_date' => 'required|date',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2020',
            'period_start_date' => 'required|date',
            'period_end_date' => 'required|date|after_or_equal:period_start_date',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kasbon_type.required' => 'Jenis kasbon harus dipilih',
            'kasbon_type.in' => 'Jenis kasbon tidak valid',
            'employee_id.required_if' => 'Karyawan harus dipilih untuk kasbon personal',
            'employee_id.exists' => 'Karyawan yang dipilih tidak valid',
            'division.required_if' => 'Divisi harus dipilih untuk kasbon tim',
            'amount.required' => 'Jumlah kasbon harus diisi',
            'amount.min' => 'Jumlah kasbon minimal Rp 1.000',
            'kasbon_date.required' => 'Tanggal kasbon harus diisi',
            'kasbon_date.date' => 'Format tanggal kasbon tidak valid',
            'period_month.required' => 'Bulan periode harus diisi',
            'period_year.required' => 'Tahun periode harus diisi',
            'period_start_date.required' => 'Tanggal mulai periode harus diisi',
            'period_end_date.required' => 'Tanggal akhir periode harus diisi',
            'period_end_date.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai',
        ];
    }

    /**
     * Prepare the data before validation.
     *
     * Normalizes amount from a currency string format (e.g. "15.000")
     * to an integer (e.g. 15000) before validation rules are applied.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => InputNormalizer::normalizeCurrency($this->amount),
        ]);
    }
}
