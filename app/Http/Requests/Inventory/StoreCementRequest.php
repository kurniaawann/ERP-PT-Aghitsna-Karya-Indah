<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi penyimpanan Data Semen.
 */
class StoreCementRequest extends FormRequest
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
            'do_no' => 'nullable|string|max:255',
            'tanggal' => 'required|date',
            'nama_proyek' => 'required|string|max:255',
            'jumlah' => 'required|integer|min:0',
            'satuan' => 'nullable|string|max:50',
            'harga' => 'required|string',
            'tanggal_lunas' => 'nullable|date',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'jumlah' => (int) $this->jumlah,
        ]);
    }
}
