<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk membuat Invoice Semen dari DO Semen.
 *
 * Alur: superadmin memilih DO Semen lalu memilih baris Data Semen mana yang
 * akan masuk invoice. Request ini memvalidasi pilihan tersebut beserta
 * field invoice (tanggal, catatan, penandatangan, rekening pembayaran).
 */
class GenerateSemenInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_date' => ['required', 'date'],
            'cement_nos' => ['required', 'array', 'min:1'],
            'cement_nos.*' => ['required', 'string', 'exists:cements,no'],
            'note' => ['nullable', 'string', 'max:255'],
            'signed_by_id' => ['nullable', 'integer', 'exists:executives,id'],
            'payment_account_id' => ['nullable', 'integer', 'exists:payment_accounts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_date.required' => 'Tanggal invoice tidak boleh kosong.',
            'invoice_date.date' => 'Format tanggal invoice tidak valid.',
            'cement_nos.required' => 'Pilih minimal satu data semen untuk diinvois.',
            'cement_nos.min' => 'Pilih minimal satu data semen untuk diinvois.',
            'cement_nos.*.exists' => 'Data semen yang dipilih tidak valid.',
            'note.max' => 'Catatan maksimal 255 karakter.',
            'signed_by_id.exists' => 'Penandatangan yang dipilih tidak valid.',
            'payment_account_id.exists' => 'Rekening pembayaran yang dipilih tidak valid.',
        ];
    }
}