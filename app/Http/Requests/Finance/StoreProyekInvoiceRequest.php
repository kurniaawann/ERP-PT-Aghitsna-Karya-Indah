<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembuatan Invoice Proyek baru.
 *
 * Memastikan semua field wajib terisi, minimal ada 1 item,
 * discount/persentase tidak lebih dari 100%, dan minimal 1 rekening pembayaran.
 */
class StoreProyekInvoiceRequest extends FormRequest
{
    /**
     * Menentukan apakah user memiliki otorisasi untuk melakukan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk store Invoice Proyek.
     */
    public function rules(): array
    {
        return [
            'invoice_number' => 'nullable|string|max:255',
            'invoice_date' => 'required|date',
            'recipient' => 'required|string|max:255',
            'regarding' => 'nullable|string|max:255',
            'project_description' => 'required|string|max:255',
            'items' => 'required|json',
            'discount_type' => 'nullable|in:percentage,amount',
            'discount_value' => 'nullable|numeric|min:0',
            'dp_type' => 'nullable|in:percentage,amount',
            'dp_value' => 'nullable|numeric|min:0',
            'selected_payment_accounts' => 'required|array|min:1',
            'selected_payment_accounts.*' => 'integer|exists:payment_accounts,id',
            'signed_by' => 'nullable|string|max:255',
            'division' => 'nullable|string|max:255',
        ];
    }

    /**
     * Pesan error custom dalam Bahasa Indonesia.
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
            'items.required' => 'Minimal harus ada 1 item.',
            'items.json' => 'Format item tidak valid.',
            'selected_payment_accounts.required' => 'Minimal 1 rekening pembayaran harus dipilih.',
            'selected_payment_accounts.array' => 'Format rekening pembayaran tidak valid.',
            'selected_payment_accounts.min' => 'Minimal 1 rekening pembayaran harus dipilih.',
        ];
    }
}
