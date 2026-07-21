<?php

namespace App\Http\Requests\Administrasi;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi penyimpanan Penawaran Proyek.
 *
 * Memastikan semua field wajib terisi, minimal 1 rekening pembayaran
 * dipilih, data items valid, dan volume bernilai numerik.
 */
class StoreProjectQuotationRequest extends FormRequest
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
     * Aturan validasi untuk penyimpanan Penawaran Proyek.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'recipient' => 'required|string|max:255',
            'date' => 'required|date',
            'items_json' => 'required|string',
            'selected_payment_accounts' => 'required|array|min:1',
            'selected_payment_accounts.*' => 'integer|exists:payment_accounts,id',
            'subject' => 'nullable|string|max:255',
            'project_description' => 'nullable|string|max:255',
            'signed_by' => 'nullable|string|max:255',
            'division' => 'nullable|string|max:255',
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
            'items_json.required' => 'Data item harus diisi.',
            'items_json.string' => 'Data item harus berupa string JSON.',
            'selected_payment_accounts.required' => 'Minimal 1 rekening pembayaran harus dipilih.',
            'selected_payment_accounts.array' => 'Data rekening pembayaran tidak valid.',
            'selected_payment_accounts.min' => 'Minimal 1 rekening pembayaran harus dipilih.',
            'selected_payment_accounts.*.integer' => 'ID rekening pembayaran tidak valid.',
            'selected_payment_accounts.*.exists' => 'Rekening pembayaran yang dipilih tidak ditemukan.',
            'subject.max' => 'Perihal maksimal 255 karakter.',
            'project_description.max' => 'Deskripsi proyek maksimal 255 karakter.',
            'signed_by.max' => 'Nama penandatangan maksimal 255 karakter.',
            'division.max' => 'Nama divisi maksimal 255 karakter.',
        ];
    }

    /**
     * Menambahkan validasi custom setelah aturan utama dijalankan.
     *
     * Memvalidasi:
     * - items_json harus berisi array yang valid dengan minimal 1 item
     * - Setiap item harus memiliki description
     * - Setiap item harus memiliki unit_price > 0
     * - Volume harus bernilai numerik dan tidak negatif
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $itemsJson = $this->input('items_json');

            if (!$itemsJson) {
                return;
            }

            $items = json_decode($itemsJson, true);

            if (!is_array($items) || count($items) === 0) {
                $validator->errors()->add('items_json', 'Minimal 1 item harus ditambahkan.');
                return;
            }

            foreach ($items as $index => $item) {
                if (empty($item['description'])) {
                    $validator->errors()->add('items_json', 'Item ' . ($index + 1) . ': Keterangan harus diisi.');
                    return;
                }

                if (empty($item['unit_price']) || (int) $item['unit_price'] <= 0) {
                    $validator->errors()->add('items_json', 'Item ' . ($index + 1) . ': Harga satuan harus lebih dari 0.');
                    return;
                }

                if (!empty($item['volume']) && !is_numeric($item['volume'])) {
                    $validator->errors()->add('items_json', 'Volume harus berupa angka.');
                    return;
                }

                if (isset($item['volume']) && is_numeric($item['volume']) && $item['volume'] < 0) {
                    $validator->errors()->add('items_json', 'Volume tidak boleh bernilai negatif.');
                    return;
                }
            }
        });
    }
}
