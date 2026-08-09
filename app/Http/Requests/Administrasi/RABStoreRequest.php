<?php

namespace App\Http\Requests\Administrasi;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request Store RAB
 *
 * Menangani validasi data saat membuat RAB baru.
 * Memastikan semua field wajib terisi, format angka sesuai,
 * dan data JSON kategori serta biaya lain-lain valid.
 */
class RABStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isAdmin = auth()->check() && auth()->user()->role === 'admin';

        return [
            'recipient' => 'required|string|max:255',
            'date' => 'required|date',
            'intro_text' => 'required|string',
            'recipient_address' => 'nullable|string|max:500',
            'signed_by' => 'nullable|string|max:255',
            'division' => 'nullable|string|max:255',
            'selected_payment_accounts' => $isAdmin
                ? ['array']
                : ['required', 'array', 'min:1'],
            'selected_payment_accounts.*' => 'integer|exists:payment_accounts,id',
            'rab_data' => 'required|string',
            'misc_costs_data' => 'nullable|string',
            'incoming_payment' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'recipient.required' => 'Nama penerima tidak boleh kosong.',
            'recipient.max' => 'Nama penerima maksimal 255 karakter.',
            'date.required' => 'Tanggal tidak boleh kosong.',
            'date.date' => 'Format tanggal tidak valid.',
            'intro_text.required' => 'Teks pengantar tidak boleh kosong.',
            'rab_data.required' => 'Data RAB tidak valid.',
            'selected_payment_accounts.required' => 'Minimal 1 rekening pembayaran harus dipilih.',
            'selected_payment_accounts.min' => 'Minimal 1 rekening pembayaran harus dipilih.',
            'selected_payment_accounts.*.exists' => 'Rekening pembayaran tidak ditemukan.',
        ];
    }

    /**
     * Persiapan data sebelum validasi.
     * Parse JSON data dari hidden input.
     */
    protected function prepareForValidation(): void
    {
        $selectedPayments = $this->input('selected_payment_accounts', []);

        if (is_string($selectedPayments)) {
            $selectedPayments = json_decode($selectedPayments, true) ?? [];
        }

        $this->merge([
            'selected_payment_accounts' => $selectedPayments,
        ]);
    }
}
