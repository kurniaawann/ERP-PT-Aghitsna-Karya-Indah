<?php

namespace App\Http\Requests\Sdm;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for updating an existing payroll draft.
 *
 * Only draft payroll can be updated. The validation ensures
 * that project_name is always provided and additional_expenses
 * is a non-negative integer. If additional_expenses > 0,
 * additional_expenses_notes is required.
 */
class UpdatePayrollRequest extends FormRequest
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
     * Get custom validation messages.
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
     * Prepare the data for validation.
     *
     * Trim additional_expenses_notes and normalize empty strings to null.
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
