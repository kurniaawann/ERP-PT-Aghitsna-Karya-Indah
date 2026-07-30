<?php

namespace App\Http\Requests\Finance;

use App\Services\InputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembuatan Faktur Pembelian baru.
 *
 * Memastikan semua field wajib terisi dengan format yang benar,
 * termasuk validasi harga jual dan persentase PPN.
 */
class StorePurchaseInvoiceRequest extends FormRequest
{
    /**
     * Normalisasi input sebelum validasi dijalankan.
     *
     * Mengubah format Rupiah (Rp 1.000.000) menjadi angka mentah (1000000)
     * agar validasi numeric tidak gagal.
     */
    protected function prepareForValidation(): void
    {
        if ($this->selling_price) {
            $this->merge([
                'selling_price' => InputNormalizer::normalizeCurrency($this->selling_price),
            ]);
        }

        if ($this->ppn_percentage) {
            $this->merge([
                'ppn_percentage' => InputNormalizer::normalizeDecimal($this->ppn_percentage),
            ]);
        }
    }

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
     * Aturan validasi untuk store Faktur Pembelian.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date'           => 'required|date',
            'material_name'  => 'required|string|max:255',
            'npwp'           => 'required|string|max:50',
            'tax_number_code' => 'required|string|max:50',
            'item_name'      => 'required|string|max:255',
            'selling_price'  => 'required|numeric|min:0',
            'ppn_percentage' => 'required|numeric|min:0|max:100',
            'notes'          => 'nullable|string',
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
            'date.required'          => 'Tanggal wajib diisi.',
            'date.date'              => 'Format tanggal tidak valid.',
            'material_name.required' => 'Nama material wajib diisi.',
            'material_name.max'      => 'Nama material maksimal 255 karakter.',
            'npwp.required'          => 'NPWP wajib diisi.',
            'npwp.max'               => 'NPWP maksimal 50 karakter.',
            'tax_number_code.required' => 'Kode nomor seri pajak wajib diisi.',
            'tax_number_code.max'    => 'Kode nomor seri pajak maksimal 50 karakter.',
            'item_name.required'     => 'Nama barang wajib diisi.',
            'item_name.max'          => 'Nama barang maksimal 255 karakter.',
            'selling_price.required' => 'Harga jual wajib diisi.',
            'selling_price.numeric'  => 'Harga jual harus berupa angka.',
            'selling_price.min'      => 'Harga jual tidak boleh kurang dari 0.',
            'ppn_percentage.required' => 'Persentase PPN wajib diisi.',
            'ppn_percentage.numeric' => 'Persentase PPN harus berupa angka.',
            'ppn_percentage.min'     => 'Persentase PPN tidak boleh kurang dari 0.',
            'ppn_percentage.max'     => 'Persentase PPN tidak boleh lebih dari 100.',
        ];
    }
}
