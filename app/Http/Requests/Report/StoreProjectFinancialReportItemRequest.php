<?php

namespace App\Http\Requests\Report;

use App\Services\InputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembuatan item "Bon" Laporan Keuangan Proyek.
 *
 * Memastikan data yang diterima valid sebelum diproses oleh Service.
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
        if ($this->has('expense_amount') && trim((string) $this->input('expense_amount')) !== '') {
            $this->merge([
                'expense_amount' => (string) InputNormalizer::normalizeCurrency($this->input('expense_amount')),
            ]);
        }
    }

    /**
     * Aturan validasi untuk store.
     */
    public function rules(): array
    {
        return [
            'project_recap_id' => ['required', 'string', 'exists:project_recaps,id'],
            'transaction_category_id' => ['required', 'integer', 'exists:transaction_categories,id'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:1000'],
            'expense_amount' => ['required', 'numeric', 'min:0'],
            'keterangan_bon' => ['nullable', 'string', 'max:255'],
            'proof_file' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,bmp,pdf'],
        ];
    }

    /**
     * Pesan error validasi dalam Bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            'project_recap_id.required' => 'Rekap Proyek tidak boleh kosong!',
            'project_recap_id.string' => 'Format rekap proyek tidak valid!',            'project_recap_id.exists' => 'Rekap proyek yang dipilih tidak ditemukan!',
            'transaction_category_id.required' => 'Kategori tidak boleh kosong!',
            'transaction_category_id.integer' => 'Format kategori tidak valid!',
            'transaction_category_id.exists' => 'Kategori yang dipilih tidak ditemukan!',
            'transaction_date.required' => 'Tanggal tidak boleh kosong!',
            'transaction_date.date' => 'Format tanggal tidak valid!',
            'description.required' => 'Keterangan tidak boleh kosong!',
            'description.max' => 'Keterangan maksimal 1000 karakter!',
            'expense_amount.required' => 'Jumlah tidak boleh kosong!',
            'expense_amount.numeric' => 'Jumlah harus berupa angka!',
            'expense_amount.min' => 'Jumlah tidak boleh negatif!',
            'keterangan_bon.max' => 'Keterangan Bon maksimal 255 karakter!',
            'proof_file.file' => 'File bukti tidak valid!',
            'proof_file.max' => 'File bukti maksimal 5 MB!',
            'proof_file.mimes' => 'Format file bukti tidak didukung (JPG, PNG, GIF, WEBP, BMP, PDF)!',
        ];
    }
}
