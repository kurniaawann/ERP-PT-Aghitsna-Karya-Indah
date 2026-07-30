<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi penyimpanan Barang Masuk.
 *
 * Memastikan data items berupa JSON yang valid, tanggal terisi,
 * dan keterangan tidak melebihi batas karakter.
 */
class StoreStockInRequest extends FormRequest
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
     * Aturan validasi untuk penyimpanan Barang Masuk.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'items' => 'required|json',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
