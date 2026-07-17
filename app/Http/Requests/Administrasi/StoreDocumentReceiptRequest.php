<?php

namespace App\Http\Requests\Administrasi;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi data tanda terima dokumen saat proses penyimpanan (create).
 *
 * Kelas ini bertanggung jawab atas validasi input dari form tambah tanda terima dokumen.
 * Validation rules didefinisikan di sini agar terpisah dari Controller (Single Responsibility).
 */
class StoreDocumentReceiptRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna berwenang melakukan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendefinisikan aturan validasi untuk penyimpanan tanda terima dokumen.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'received_from' => 'required|string|max:255',
            'regarding' => 'required|string|max:255',
            'form_of' => 'required|string|max:255',
            'receipt_date' => 'required|date',
            'receipt_time' => 'required',
            'location' => 'nullable|string|max:100',
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
            'received_from.required' => 'Telah terima dari tidak boleh kosong.',
            'received_from.max' => 'Telah terima dari maksimal 255 karakter.',
            'regarding.required' => 'Perihal tidak boleh kosong.',
            'regarding.max' => 'Perihal maksimal 255 karakter.',
            'form_of.required' => 'Berupa tidak boleh kosong.',
            'form_of.max' => 'Berupa maksimal 255 karakter.',
            'receipt_date.required' => 'Tanggal tidak boleh kosong.',
            'receipt_date.date' => 'Format tanggal tidak valid.',
            'receipt_time.required' => 'Jam tidak boleh kosong.',
            'location.max' => 'Lokasi maksimal 100 karakter.',
        ];
    }
}
