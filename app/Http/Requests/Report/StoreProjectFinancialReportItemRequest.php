<?php

namespace App\Http\Requests\Report;

use App\Services\InputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembuatan item "Bon" Laporan Keuangan Proyek.
 *
 * Menerima satu atau banyak transaksi dalam satu submit (struktur dinamis):
 * - `project_recap_id`        : rekap proyek tujuan (tinggal satu)
 * - `items[]`                 : array transaksi "Bon"
 *   - `items.*.transaction_category_id` : kategori transaksi
 *   - `items.*.transaction_date`        : tanggal transaksi
 *   - `items.*.description`             : keterangan
 *   - `items.*.expense_amount`          : jumlah (pemasukan/pengeluaran)
 *   - `items.*.keterangan_bon`          : keterangan bon (opsional)
 *   - `items.*.proof_file`              : bukti pembayaran (opsional)
 */
class StoreProjectFinancialReportItemRequest extends FormRequest
{
    /**
     * Menentukan apakah user memiliki akses ke request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalisasi nilai nominal sebelum validasi.
     *
     * Input jumlah diformat di frontend ke format Indonesia (mis. "1.000.000"),
     * sehingga perlu dinormalisasi ke angka murni agar lolos rule `numeric`.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('items')) {
            $items = $this->input('items');

            foreach ($items as $key => $item) {
                if (isset($item['expense_amount']) && trim((string) $item['expense_amount']) !== '') {
                    $items[$key]['expense_amount'] = (string) InputNormalizer::normalizeCurrency($item['expense_amount']);
                }
            }

            $this->merge(['items' => $items]);
        }
    }

    /**
     * Aturan validasi untuk store.
     */
    public function rules(): array
    {
        return [
            'project_recap_id' => ['required', 'string', 'exists:project_recaps,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.transaction_category_id' => ['required', 'integer', 'exists:transaction_categories,id'],
            'items.*.transaction_date' => ['required', 'date'],
            'items.*.description' => ['nullable', 'string', 'max:1000'],
            'items.*.expense_amount' => ['required', 'numeric', 'min:0'],
            'items.*.keterangan_bon' => ['nullable', 'string', 'max:255'],
            'items.*.proof_file' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,bmp'],
        ];
    }

    /**
     * Pesan error validasi dalam Bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            'project_recap_id.required' => 'Rekap Proyek tidak boleh kosong!',
            'project_recap_id.string' => 'Format rekap proyek tidak valid!',
            'project_recap_id.exists' => 'Rekap proyek yang dipilih tidak ditemukan!',
            'items.required' => 'Minimal satu transaksi harus diisi!',
            'items.array' => 'Format transaksi tidak valid!',
            'items.min' => 'Minimal satu transaksi harus diisi!',
            'items.*.transaction_category_id.required' => 'Kategori transaksi tidak boleh kosong!',
            'items.*.transaction_category_id.integer' => 'Format kategori transaksi tidak valid!',
            'items.*.transaction_category_id.exists' => 'Kategori transaksi yang dipilih tidak ditemukan!',
            'items.*.transaction_date.required' => 'Tanggal transaksi tidak boleh kosong!',
            'items.*.transaction_date.date' => 'Format tanggal transaksi tidak valid!',
            'items.*.description.required' => 'Keterangan tidak boleh kosong!',
            'items.*.description.max' => 'Keterangan maksimal 1000 karakter!',
            'items.*.expense_amount.required' => 'Jumlah tidak boleh kosong!',
            'items.*.expense_amount.numeric' => 'Jumlah harus berupa angka!',
            'items.*.expense_amount.min' => 'Jumlah tidak boleh negatif!',
            'items.*.keterangan_bon.max' => 'Keterangan Bon maksimal 255 karakter!',
            'items.*.proof_file.file' => 'File bukti tidak valid!',
            'items.*.proof_file.max' => 'File bukti maksimal 5 MB!',
            'items.*.proof_file.mimes' => 'Format file bukti tidak didukung (khusus gambar JPG, PNG, GIF, WEBP, BMP)!',
        ];
    }
}
