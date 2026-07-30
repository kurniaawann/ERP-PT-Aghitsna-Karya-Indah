<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi update Pengembalian Barang.
 *
 * Hanya memvalidasi field yang dapat diubah:
 * quantity, reason, date, notes.
 * Return type dan item tidak dapat diubah setelah dibuat.
 */
class UpdateItemReturnRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantity.required' => 'Jumlah harus diisi.',
            'quantity.integer' => 'Jumlah harus berupa angka.',
            'quantity.min' => 'Jumlah minimal 1.',
            'date.required' => 'Tanggal harus diisi.',
            'date.date' => 'Format tanggal tidak valid.',
        ];
    }
}
