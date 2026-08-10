<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembuatan Rekap Proyek baru.
 *
 * Memastikan data yang diterima valid sebelum diproses oleh Service.
 */
class StoreRecapProyekRequest extends FormRequest
{
    /**
     * Menentukan apakah user memiliki akses ke request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Bersihkan format Rupiah sebelum validasi.
     *
     * Input Total RAB diformat JS menjadi "125.000.000" (titik ribuan) yang
     * tidak lolos aturan 'numeric'. Normalisasi menjadi "125000000" terlebih
     * dahulu agar validasi berjalan benar.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('total_rab')) {
            $this->merge([
                'total_rab' => preg_replace('/[^0-9]/', '', (string) $this->input('total_rab')),
            ]);
        }
    }

    /**
     * Aturan validasi untuk store.
     */
    public function rules(): array
    {
        return [
            'project_name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'total_rab' => ['required', 'numeric', 'min:0'],
            'design_file' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,bmp'],
        ];
    }

    /**
     * Pesan error validasi dalam Bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            'project_name.required' => 'Nama proyek tidak boleh kosong!',
            'project_name.max' => 'Nama proyek maksimal 255 karakter!',
            'location.max' => 'Lokasi maksimal 255 karakter!',
            'total_rab.required' => 'Total RAB tidak boleh kosong!',
            'total_rab.numeric' => 'Total RAB harus berupa angka!',
            'total_rab.min' => 'Total RAB tidak boleh negatif!',
            'design_file.file' => 'File design tidak valid!',
            'design_file.max' => 'File design maksimal 5 MB!',
            'design_file.mimes' => 'Format file design tidak didukung (JPG, PNG, GIF, WEBP, BMP)!',
        ];
    }
}
