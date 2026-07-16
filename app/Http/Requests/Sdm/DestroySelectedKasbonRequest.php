<?php

namespace App\Http\Requests\Sdm;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for bulk deleting kasbon records.
 *
 * Validates that at least one kasbon code is selected
 * before processing the delete operation.
 *
 * Authorization is handled at the route level via middleware('role:admin').
 */
class DestroySelectedKasbonRequest extends FormRequest
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
            'selected_kasbons' => 'required|array|min:1',
            'selected_kasbons.*' => 'string|exists:kasbons,kasbon_code',
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
            'selected_kasbons.required' => 'Tidak ada data yang dipilih untuk dihapus.',
            'selected_kasbons.min' => 'Tidak ada data yang dipilih untuk dihapus.',
            'selected_kasbons.*.exists' => 'Salah satu kasbon yang dipilih tidak valid.',
        ];
    }
}
