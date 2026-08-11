<?php

namespace App\Http\Requests\Sdm;

use App\Services\InputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request untuk penyimpanan data kasbon baru.
 *
 * Memvalidasi jenis kasbon, pemilihan karyawan/divisi, jumlah,
 * tanggal, dan informasi periode sebelum pembuatan.
 *
 * Otorisasi ditangani di level route melalui middleware('role:admin').
 */
class StoreKasbonRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna berwenang melakukan permintaan ini.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendapatkan aturan validasi yang berlaku untuk permintaan ini.
     *
     * Untuk kasbon tim, setiap proyek diisi lewat baris `projects[]`
     * (masing-masing dengan jumlah, periode, dan catatan sendiri) dan
     * disimpan sebagai record kasbon terpisah.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kasbon_type' => 'required|in:personal,team',
            'employee_id' => 'required_if:kasbon_type,personal|nullable|exists:employees,employee_code',
            'division' => 'required_if:kasbon_type,team|nullable|string|max:100',
            'projects' => 'required_if:kasbon_type,team|nullable|array|min:1',
            'projects.*.project' => 'required|string|max:255',
            'projects.*.amount' => 'required|integer|min:1000',
            'projects.*.kasbon_date' => 'required|date',
            'projects.*.period_start_date' => 'required|date',
            'projects.*.period_end_date' => 'required|date',
            'projects.*.notes' => 'nullable|string|max:500',
            'amount' => 'nullable|required_if:kasbon_type,personal|integer|min:1000',
            'kasbon_date' => 'required_if:kasbon_type,personal|date',
            'period_start_date' => 'required_if:kasbon_type,personal|date',
            'period_end_date' => 'required_if:kasbon_type,personal|date|after_or_equal:period_start_date',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Mendapatkan pesan validasi kustom.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kasbon_type.required' => 'Jenis kasbon harus dipilih',
            'kasbon_type.in' => 'Jenis kasbon tidak valid',
            'employee_id.required_if' => 'Karyawan harus dipilih untuk kasbon personal',
            'employee_id.exists' => 'Karyawan yang dipilih tidak valid',
            'division.required_if' => 'Divisi harus dipilih untuk kasbon tim',
            'projects.required_if' => 'Minimal satu proyek wajib diisi untuk kasbon divisi',
            'projects.min' => 'Minimal satu proyek wajib diisi untuk kasbon divisi',
            'projects.*.project.required' => 'Proyek wajib dipilih',
            'projects.*.amount.required' => 'Jumlah kasbon wajib diisi',
            'projects.*.amount.min' => 'Jumlah kasbon minimal Rp 1.000',
            'projects.*.kasbon_date.required' => 'Periode kasbon wajib diisi',
            'projects.*.period_start_date.required' => 'Tanggal mulai periode wajib diisi',
            'projects.*.period_end_date.required' => 'Tanggal akhir periode wajib diisi',
            'amount.required' => 'Jumlah kasbon harus diisi',
            'amount.min' => 'Jumlah kasbon minimal Rp 1.000',
            'kasbon_date.required' => 'Periode kasbon harus diisi',
            'kasbon_date.date' => 'Format periode kasbon tidak valid',
            'period_start_date.required' => 'Tanggal mulai periode harus diisi',
            'period_end_date.required' => 'Tanggal akhir periode harus diisi',
            'period_end_date.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai',
        ];
    }

    /**
     * Mempersiapkan data sebelum validasi.
     *
     * Menormalisasi jumlah dari format string mata uang (misalnya "15.000")
     * menjadi integer (misalnya 15000) sebelum aturan validasi diterapkan,
     * baik pada amount utama (personal) maupun amount tiap baris proyek (tim).
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => ($this->kasbon_type ?? '') === 'personal'
                ? InputNormalizer::normalizeCurrency($this->amount)
                : null,
        ]);

        // Kasbon personal tidak memakai baris proyek; abaikan data proyek
        // yang tersisa di DOM (baris kosong tersembunyi) agar tidak lolos
        // sebagai input validasi.
        if (($this->kasbon_type ?? '') === 'personal') {
            $this->merge(['projects' => null]);

            return;
        }

        if (is_array($this->projects)) {
            $this->merge([
                'projects' => collect($this->projects)->map(function ($row) {
                    $row['amount'] = InputNormalizer::normalizeCurrency($row['amount'] ?? 0);

                    return $row;
                })->values()->all(),
            ]);
        }
    }
}
