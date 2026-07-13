<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembuatan Pengembalian Barang baru.
 *
 * Validasi berbeda tergantung tipe return:
 * - 'masuk': membutuhkan id_stock_in
 * - 'keluar': membutuhkan id_stock_out
 */
class StoreItemReturnRequest extends FormRequest
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
        $returnType = $this->input('return_type', 'keluar');

        $rules = [
            'id_item' => 'required|exists:items,id_item',
            'return_type' => 'required|in:masuk,keluar',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ];

        if ($returnType === 'masuk') {
            $rules['id_stock_in'] = 'required|exists:item_stock_ins,id_stock_in';
        } else {
            $rules['id_stock_out'] = 'required|exists:item_stock_outs,id_stock_out';
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_item.required' => 'Barang harus dipilih.',
            'id_item.exists' => 'Barang yang dipilih tidak valid.',
            'return_type.required' => 'Tipe return harus dipilih.',
            'return_type.in' => 'Tipe return harus masuk atau keluar.',
            'quantity.required' => 'Jumlah harus diisi.',
            'quantity.integer' => 'Jumlah harus berupa angka.',
            'quantity.min' => 'Jumlah minimal 1.',
            'date.required' => 'Tanggal harus diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'id_stock_in.required' => 'Barang masuk harus dipilih untuk tipe return masuk.',
            'id_stock_out.required' => 'Barang keluar harus dipilih untuk tipe return keluar.',
        ];
    }
}
