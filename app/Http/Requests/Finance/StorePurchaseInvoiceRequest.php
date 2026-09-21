<?php

namespace App\Http\Requests\Finance;

use App\Services\InputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembuatan Faktur Pembelian baru (massal).
 *
 * Mendukung penambahan banyak faktur sekaligus lewat array `invoices`.
 * Memastikan semua field wajib terisi dengan format yang benar.
 */
class StorePurchaseInvoiceRequest extends FormRequest
{
    /**
     * Normalisasi input sebelum validasi dijalankan.
     *
     * Mengubah format Rupiah (Rp 1.000.000) menjadi angka mentah (1000000)
     * dan persentase desimal (11,5 -> 11.5) untuk setiap faktur di
     * dalam array `invoices`, agar validasi numeric tidak gagal.
     */
    protected function prepareForValidation(): void
    {
        $invoices = $this->input('invoices');

        if (! is_array($invoices) || empty($invoices)) {
            return;
        }

        foreach ($invoices as $index => $invoice) {
            $invoices[$index]['selling_price'] = $this->hasNonEmpty($invoice, 'selling_price')
                ? InputNormalizer::normalizeCurrency($invoice['selling_price'])
                : ($invoice['selling_price'] ?? null);

            $invoices[$index]['ppn_percentage'] = $this->hasNonEmpty($invoice, 'ppn_percentage')
                ? InputNormalizer::normalizeDecimal($invoice['ppn_percentage'])
                : ($invoice['ppn_percentage'] ?? null);
        }

        $this->merge(['invoices' => $invoices]);
    }

    /**
     * Cek apakah nilai field ada dan tidak kosong.
     *
     * Dipakai agar nilai kosong TIDAK dinormalisasi (mis. menjadi 0),
     * sehingga aturan `required` tetap menolak field yang sengaja tidak diisi.
     *
     * @param  array<string, mixed> $invoice
     * @param  string               $key
     * @return bool
     */
    private function hasNonEmpty(array $invoice, string $key): bool
    {
        return isset($invoice[$key]) && $invoice[$key] !== '' && $invoice[$key] !== null;
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
     * Aturan validasi untuk store Faktur Pembelian (massal).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'invoices'                  => 'required|array|min:1',
            'invoices.*.date'           => 'required|date',
            'invoices.*.material_name'  => 'required|string|max:255',
            'invoices.*.npwp'           => 'required|string|max:50',
            'invoices.*.tax_number_code' => 'required|string|max:50',
            'invoices.*.item_name'      => 'required|string|max:255',
            'invoices.*.selling_price'  => 'required|numeric|min:0',
            'invoices.*.ppn_percentage' => 'required|numeric|min:0|max:100',
            'invoices.*.notes'          => 'nullable|string',
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
            'invoices.required'          => 'Minimal satu faktur pembelian wajib diisi.',
            'invoices.min'               => 'Minimal satu faktur pembelian wajib diisi.',
            'invoices.*.date.required'   => 'Tanggal wajib diisi.',
            'invoices.*.date.date'       => 'Format tanggal tidak valid.',
            'invoices.*.material_name.required' => 'Nama material wajib diisi.',
            'invoices.*.material_name.max'      => 'Nama material maksimal 255 karakter.',
            'invoices.*.npwp.required'   => 'NPWP wajib diisi.',
            'invoices.*.npwp.max'        => 'NPWP maksimal 50 karakter.',
            'invoices.*.tax_number_code.required' => 'Kode nomor seri pajak wajib diisi.',
            'invoices.*.tax_number_code.max'      => 'Kode nomor seri pajak maksimal 50 karakter.',
            'invoices.*.item_name.required'       => 'Nama barang wajib diisi.',
            'invoices.*.item_name.max'            => 'Nama barang maksimal 255 karakter.',
            'invoices.*.selling_price.required'   => 'Harga jual wajib diisi.',
            'invoices.*.selling_price.numeric'    => 'Harga jual harus berupa angka.',
            'invoices.*.selling_price.min'        => 'Harga jual tidak boleh kurang dari 0.',
            'invoices.*.ppn_percentage.required'  => 'Persentase PPN wajib diisi.',
            'invoices.*.ppn_percentage.numeric'   => 'Persentase PPN harus berupa angka.',
            'invoices.*.ppn_percentage.min'       => 'Persentase PPN tidak boleh kurang dari 0.',
            'invoices.*.ppn_percentage.max'       => 'Persentase PPN tidak boleh lebih dari 100.',
        ];
    }
}