<?php

namespace App\Http\Requests\Administrasi;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi data Surat Perintah Kerja (SPK) saat pembaruan (update).
 *
 * @package App\Http\Requests\Administrasi
 */
class UpdateSuratPerintahKerjaRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna berwenang melakukan request ini.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendefinisikan aturan validasi untuk pembaruan surat perintah kerja.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'proyek' => 'required|string|max:255',
            'lokasi' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'pemberi_tugas_nama' => 'required|string|max:255',
            'pemberi_tugas_alamat' => 'required|string|max:500',
            'signer_nama' => 'required|string|max:255',
            'signer_jabatan' => 'required|string|max:255',
            'no' => 'required|array|min:1',
            'no.*' => 'required|integer|min:1',
            'kode' => 'nullable|array',
            'kode.*' => 'nullable|string|max:100',
            'detail_keterangan' => 'required|array|min:1',
            'detail_keterangan.*' => 'required|array|min:1',
            'detail_keterangan.*.*' => 'required|string|max:500',
            'detail_volume' => 'required|array|min:1',
            'detail_volume.*' => 'required|array|min:1',
            'detail_volume.*.*' => 'required|numeric|min:0',
            'detail_satuan' => 'nullable|array',
            'detail_satuan.*' => 'nullable|array',
            'detail_satuan.*.*' => 'nullable|string|max:50',
            'detail_harga' => 'required|array|min:1',
            'detail_harga.*' => 'required|array|min:1',
            'detail_harga.*.*' => 'required|numeric|min:0',
            'detail_jumlah' => 'nullable|array',
            'detail_jumlah.*' => 'nullable|array',
            'detail_jumlah.*.*' => 'nullable|numeric|min:0',
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
            'proyek.required' => 'Proyek tidak boleh kosong.',
            'proyek.max' => 'Proyek maksimal 255 karakter.',
            'lokasi.required' => 'Lokasi tidak boleh kosong.',
            'lokasi.max' => 'Lokasi maksimal 255 karakter.',
            'tanggal.required' => 'Tanggal tidak boleh kosong.',
            'tanggal.date' => 'Format tanggal tidak valid.',
            'pemberi_tugas_nama.required' => 'Nama Pemberi Tugas tidak boleh kosong.',
            'pemberi_tugas_nama.max' => 'Nama Pemberi Tugas maksimal 255 karakter.',
            'pemberi_tugas_alamat.required' => 'Alamat Pemberi Tugas tidak boleh kosong.',
            'pemberi_tugas_alamat.max' => 'Alamat Pemberi Tugas maksimal 500 karakter.',
            'signer_nama.required' => 'Nama yang bertanda tangan tidak boleh kosong.',
            'signer_nama.max' => 'Nama yang bertanda tangan maksimal 255 karakter.',
            'signer_jabatan.required' => 'Jabatan yang bertanda tangan tidak boleh kosong.',
            'signer_jabatan.max' => 'Jabatan yang bertanda tangan maksimal 255 karakter.',
            'no.required' => 'Minimal harus ada 1 item pekerjaan.',
            'no.min' => 'Minimal harus ada 1 item pekerjaan.',
            'no.*.required' => 'Nomor item tidak boleh kosong.',
            'no.*.integer' => 'Nomor item harus berupa angka.',
            'no.*.min' => 'Nomor item minimal 1.',
            'kode.*.max' => 'Kode maksimal 100 karakter.',
            'detail_keterangan.required' => 'Keterangan tidak boleh kosong.',
            'detail_keterangan.min' => 'Minimal harus ada 1 item pekerjaan.',
            'detail_keterangan.*.required' => 'Keterangan tidak boleh kosong.',
            'detail_keterangan.*.min' => 'Setiap No/Kode minimal punya 1 keterangan.',
            'detail_keterangan.*.*.required' => 'Keterangan tidak boleh kosong.',
            'detail_keterangan.*.*.max' => 'Keterangan maksimal 500 karakter.',
            'detail_volume.*.*.required' => 'Volume tidak boleh kosong.',
            'detail_volume.*.*.numeric' => 'Volume harus berupa angka.',
            'detail_satuan.*.*.max' => 'Satuan maksimal 50 karakter.',
            'detail_harga.*.*.required' => 'Harga tidak boleh kosong.',
            'detail_harga.*.*.numeric' => 'Harga harus berupa angka.',
            'detail_jumlah.*.*.numeric' => 'Jumlah harus berupa angka.',
        ];
    }
}
