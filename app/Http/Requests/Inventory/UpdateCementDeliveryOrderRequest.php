<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembaruan DO Semen.
 */
class UpdateCementDeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'tanggal' => 'required|date',
            'proyek' => 'required|string|max:255',
            'volume' => 'required|integer|min:0',
            'satuan' => 'nullable|string|max:50',
            'harga' => 'required|string',
            'tanggal_lunas' => 'nullable|date',
            'harga_modal' => 'required|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'volume' => (int) $this->volume,
        ]);
    }
}
