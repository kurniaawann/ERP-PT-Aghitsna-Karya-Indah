<?php

namespace App\Http\Requests\Sdm;

use App\Services\InputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for updating an existing overtime record.
 *
 * Validates employee, date, overtime hours, rate, and notes
 * before the overtime data is updated in the database.
 */
class UpdateOvertimeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled at the route level via middleware('role:admin').
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
            'employee_id' => 'required|string|exists:employees,employee_code',
            'attendance_date' => 'required|date|before_or_equal:today',
            'overtime_hours' => 'required|numeric|min:0.5|max:24',
            'overtime_rate' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:1000',
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
            'employee_id.required' => 'Karyawan tidak boleh kosong.',
            'employee_id.exists' => 'Karyawan yang dipilih tidak valid.',
            'attendance_date.required' => 'Tanggal tidak boleh kosong.',
            'attendance_date.before_or_equal' => 'Tanggal tidak boleh lebih dari hari ini.',
            'overtime_hours.required' => 'Jam lembur tidak boleh kosong.',
            'overtime_hours.numeric' => 'Jam lembur harus berupa angka.',
            'overtime_hours.min' => 'Jam lembur minimal 0.5 jam.',
            'overtime_hours.max' => 'Jam lembur maksimal 24 jam.',
            'overtime_rate.required' => 'Tarif tidak boleh kosong.',
            'overtime_rate.integer' => 'Tarif harus berupa angka bulat.',
            'overtime_rate.min' => 'Tarif tidak boleh negatif.',
            'notes.max' => 'Keterangan maksimal 1000 karakter.',
        ];
    }

    /**
     * Prepare the data before validation.
     *
     * Normalizes overtime_rate from a currency string format (e.g. "15.000")
     * to an integer (e.g. 15000) before validation rules are applied.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'overtime_rate' => InputNormalizer::normalizeCurrency($this->overtime_rate),
        ]);
    }
}
