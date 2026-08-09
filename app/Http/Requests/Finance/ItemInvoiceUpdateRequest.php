<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi update Invoice Barang.
 *
 * Sama dengan store namun items harus array dengan minimal 1 item.
 */
class ItemInvoiceUpdateRequest extends FormRequest
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
     * Aturan validasi untuk update Invoice Barang.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'invoice_date' => 'required|date',
            'recipient' => 'required|string|max:255',
            'regarding' => 'nullable|string|max:255',
            'project_description' => 'required|string|max:255',
            'proyek' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'selected_payment_accounts' => 'nullable|array',
            'selected_payment_accounts.*' => 'integer|exists:payment_accounts,id',
            'signed_by_id' => 'nullable|exists:executives,id',
            'division_id' => 'nullable|exists:divisions,id',
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
            'invoice_date.required' => 'Tanggal invoice wajib diisi.',
            'invoice_date.date' => 'Format tanggal invoice tidak valid.',
            'recipient.required' => 'Nama penerima wajib diisi.',
            'recipient.max' => 'Nama penerima maksimal 255 karakter.',
            'project_description.required' => 'Deskripsi proyek wajib diisi.',
            'project_description.max' => 'Deskripsi proyek maksimal 255 karakter.',
            'proyek.max' => 'Nama proyek maksimal 255 karakter.',
            'items.required' => 'Minimal harus ada 1 item.',
            'items.array' => 'Format item tidak valid.',
            'items.min' => 'Minimal harus ada 1 item.',
        ];
    }
}
