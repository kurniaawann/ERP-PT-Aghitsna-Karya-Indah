<?php

namespace App\Http\Requests\Sdm;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for updating an existing division.
 *
 * The division is identified via route model binding.
 * The unique validation rule excludes the current division's ID.
 */
class UpdateDivisionRequest extends FormRequest
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
            'name' => 'required|string|max:100|unique:divisions,name,' . $this->route('division')->id,
            'description' => 'nullable|string|max:500',
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
            'name.required' => 'Nama divisi harus diisi',
            'name.unique' => 'Nama divisi sudah ada',
        ];
    }
}
