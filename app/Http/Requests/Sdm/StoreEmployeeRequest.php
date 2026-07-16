<?php

namespace App\Http\Requests\Sdm;

use App\Services\InputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for storing a new employee.
 *
 * Ensures all required fields are present with proper types and bounds
 * before the employee data is persisted to the database.
 */
class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled at the route level via middleware middleware('role:admin').
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
            'name' => 'required|string|max:255',
            'daily_wage' => 'required|integer|min:0',
            'division' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
        ];
    }

    /**
     * Prepare the data before validation.
     *
     * Normalizes daily_wage from a currency string format (e.g. "150.000")
     * to an integer (e.g. 150000) before validation rules are applied.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'daily_wage' => InputNormalizer::normalizeCurrency($this->daily_wage),
        ]);
    }
}
