<?php

namespace App\Http\Requests\Inventory;

use App\Services\InputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembaruan Data Barang.
 *
 * Memastikan semua field wajib terisi dan harga jual
 * lebih besar dari harga modal.
 */
class UpdateItemRequest extends FormRequest
{
    /**
     * Menentukan apakah request ini diizinkan.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk pembaruan Data Barang.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name_item' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'capital_price' => 'required|string',
            'selling_price' => 'required|string',
            'keterangan' => 'nullable|string|max:255',
        ];
    }

    /**
     * Menyiapkan data sebelum validasi dilakukan.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'quantity' => (int) $this->quantity,
        ]);
    }

    /**
     * Validasi tambahan: harga jual harus lebih besar dari harga modal.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
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
