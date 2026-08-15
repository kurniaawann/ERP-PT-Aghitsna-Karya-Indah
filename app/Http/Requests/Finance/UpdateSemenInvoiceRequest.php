<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembaruan Invoice Semen.
 */
class UpdateSemenInvoiceRequest extends FormRequest
{
    /**
     * Menentukan apakah user memiliki otorisasi untuk melakukan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Menyiapkan input sebelum validasi: decode field `projects` dari
     * JSON string (dikirim oleh form modal) menjadi array.
     */
    protected function prepareForValidation(): void
    {
        $projects = $this->input('projects');

        if (is_string($projects)) {
            $decoded = json_decode($projects, true);
            $this->merge(['projects' => is_array($decoded) ? $decoded : []]);
        }
    }

    /**
     * Aturan validasi untuk update Invoice Semen.
     */
    public function rules(): array
    {
        return [
            'invoice_date' => 'required|date',
            'projects' => 'required|array|min:1',
            'projects.*.nama_proyek' => 'required|string|max:255',
            'projects.*.payment_account_id' => 'nullable|exists:payment_accounts,id',
            'projects.*.items' => 'required|array|min:1',
            'projects.*.items.*.tanggal' => 'nullable|date',
            'projects.*.items.*.nama_barang' => 'nullable|string|max:255',
            'projects.*.items.*.qty' => 'required|numeric|min:1',
            'projects.*.items.*.harga' => 'nullable|numeric|min:0',
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
            'projects.required' => 'Minimal harus ada 1 proyek.',
            'projects.array' => 'Format proyek tidak valid.',
            'projects.min' => 'Minimal harus ada 1 proyek.',
            'projects.*.nama_proyek.required' => 'Nama proyek wajib diisi.',
            'projects.*.nama_proyek.max' => 'Nama proyek maksimal 255 karakter.',
            'projects.*.items.required' => 'Minimal harus ada 1 data semen pada setiap proyek.',
            'projects.*.items.array' => 'Format item tidak valid.',
            'projects.*.items.min' => 'Minimal harus ada 1 data semen pada setiap proyek.',
            'projects.*.items.*.qty.required' => 'Qty wajib diisi.',
            'projects.*.items.*.qty.min' => 'Qty minimal 1.',
        ];
    }
}