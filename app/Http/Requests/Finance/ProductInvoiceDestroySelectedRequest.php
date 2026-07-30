<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi penghapusan massal Invoice Barang.
 *
 * Memastikan minimal ada 1 invoice yang dipilih.
 */
class ProductInvoiceDestroySelectedRequest extends FormRequest
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
     * Aturan validasi untuk penghapusan massal Invoice Barang.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'selected_invoices' => 'required|array|min:1',
            'selected_invoices.*' => 'string|max:255',
        ];
    }

    /**
     * Pesan error validasi dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'selected_invoices.required' => 'Tidak ada invoice yang dipilih untuk dihapus.',
            'selected_invoices.array' => 'Format data invoice tidak valid.',
            'selected_invoices.min' => 'Tidak ada invoice yang dipilih untuk dihapus.',
        ];
    }
}
