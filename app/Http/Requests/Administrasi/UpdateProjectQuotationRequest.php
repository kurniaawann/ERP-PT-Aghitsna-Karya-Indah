<?php

namespace App\Http\Requests\Administrasi;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembaruan Penawaran Proyek.
 *
 * Memastikan semua field wajib terisi, minimal 1 rekening pembayaran
 * dipilih, data items valid, dan discount memiliki tipe yang diperbolehkan.
 * PPN & DP tidak ada pada penawaran — keduanya diisi pada Invoice (modul Finance).
 */
class UpdateProjectQuotationRequest extends FormRequest
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
     * Aturan validasi untuk pembaruan Penawaran Proyek.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'recipient' => 'required|string|max:255',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.keterangan' => 'required|string|max:255',
            'items.*.volume' => 'required|numeric|min:0',
            'items.*.satuan' => 'nullable|string|max:255',
            'items.*.harga' => 'required|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,amount',
            'discount_value' => 'nullable|numeric|min:0',
            'selected_payment_accounts' => 'required|array|min:1',
            'selected_payment_accounts.*' => 'integer|exists:payment_accounts,id',
            'subject' => 'nullable|string|max:255',
            'attachment' => 'nullable|string|max:255',
            'project_description' => 'nullable|string|max:255',
            'proyek' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'signed_by_id' => 'nullable|exists:executives,id',
            'division_id' => 'nullable|exists:divisions,id',
        ];
    }

    /**
     * Pesan validasi dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recipient.required' => 'Nama penerima harus diisi.',
            'recipient.max' => 'Nama penerima maksimal 255 karakter.',
            'date.required' => 'Tanggal penawaran harus diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'items.required' => 'Minimal harus ada 1 item.',
            'items.min' => 'Minimal harus ada 1 item.',
            'items.*.keterangan.required' => 'Keterangan item wajib diisi.',
            'items.*.volume.required' => 'Volume item wajib diisi.',
            'items.*.harga.required' => 'Harga item wajib diisi.',
            'selected_payment_accounts.required' => 'Minimal 1 rekening pembayaran harus dipilih.',
            'selected_payment_accounts.array' => 'Data rekening pembayaran tidak valid.',
            'selected_payment_accounts.min' => 'Minimal 1 rekening pembayaran harus dipilih.',
            'selected_payment_accounts.*.integer' => 'ID rekening pembayaran tidak valid.',
            'selected_payment_accounts.*.exists' => 'Rekening pembayaran yang dipilih tidak ditemukan.',
            'subject.max' => 'Perihal maksimal 255 karakter.',
            'attachment.max' => 'Lampiran maksimal 255 karakter.',
            'project_description.max' => 'Deskripsi proyek maksimal 255 karakter.',
            'proyek.max' => 'Nama proyek maksimal 255 karakter.',
            'location.max' => 'Lokasi maksimal 255 karakter.',
            'city.max' => 'Kota maksimal 255 karakter.',
            'signed_by_id.exists' => 'Nama penandatangan yang dipilih tidak ditemukan.',
            'division_id.exists' => 'Divisi yang dipilih tidak ditemukan.',
        ];
    }
}
