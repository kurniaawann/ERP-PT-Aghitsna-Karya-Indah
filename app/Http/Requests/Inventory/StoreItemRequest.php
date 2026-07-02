<?php

namespace App\Http\Requests\Inventory;

use App\Services\InputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_item' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'capital_price' => 'required|string',
            'selling_price' => 'required|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'quantity' => (int) $this->quantity,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $capitalPrice = InputNormalizer::normalizeCurrency($this->capital_price);
            $sellingPrice = InputNormalizer::normalizeCurrency($this->selling_price);

            if ($sellingPrice > 0 && $capitalPrice >= $sellingPrice) {
                $validator->errors()->add('selling_price', 'Harga jual harus lebih besar dari harga modal.');
            }
        });
    }
}
