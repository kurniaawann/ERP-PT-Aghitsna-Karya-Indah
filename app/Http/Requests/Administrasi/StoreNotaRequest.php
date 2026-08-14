<?php

namespace App\Http\Requests\Administrasi;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi data nota saat proses penyimpanan (create).
 *
 * Kelas ini bertanggung jawab atas validasi input dari form tambah nota.
 * Validation rules didefinisikan di sini agar terpisah dari Controller (Single Responsibility).
 */
class StoreNotaRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna berwenang melakukan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendefinisikan aturan validasi untuk penyimpanan nota.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tipe = $this->input('tipe_nota');

        if ($tipe === 'proyek') {
            return [
                'tipe_nota' => 'required|string|in:sewa_jual,proyek',
                'nama_proyek' => 'required|string|max:255',
                'location' => 'nullable|string|max:100',
                'nota_date' => 'required|date',
                'kepada' => 'required|string|max:255',
                'item_quantity' => 'required|array|min:1',
                'item_quantity.*' => 'required|integer|min:1',
                'item_satuan' => 'nullable|array',
                'item_satuan.*' => 'nullable|string|max:50',
                'item_nama_barang' => 'required|array|min:1',
                'item_nama_barang.*' => 'required|string|max:255',
                'item_harga' => 'required|array|min:1',
                'item_harga.*' => 'required',
                'penerima' => 'nullable|string|max:255',
                'petinggi_id' => 'nullable|integer|exists:executives,id',
                'divisi' => 'nullable|string|max:100',
                'selected_payment_accounts' => 'nullable|array',
                'selected_payment_accounts.*' => 'integer|exists:payment_accounts,id',
            ];
        }

        return [
            'tipe_nota' => 'required|string|in:sewa_jual,proyek',
            'location' => 'nullable|string|max:100',
            'nota_date' => 'required|date',
            'periode_start' => 'nullable|date',
            'periode_end' => 'nullable|date',
            'kepada' => 'required|string|max:255',
            'faktur_no' => 'required|string|max:100',
            'sj_no' => 'required|string|max:100',
            'item_banyaknya' => 'required|array|min:1',
            'item_banyaknya.*' => 'required|integer|min:1',
            'item_nama_barang' => 'required|array|min:1',
            'item_nama_barang.*' => 'required|string|max:255',
            'item_harga_satuan' => 'required|array|min:1',
            'item_harga_satuan.*' => 'required',
            'penerima' => 'nullable|string|max:255',
            'sewa_jual' => 'nullable',
            'ongkos_kirim' => 'nullable',
            'bongkar_pasang' => 'nullable',
            'lembur' => 'nullable',
            'uang_jaminan' => 'nullable',
            'selected_payment_accounts' => 'nullable|array',
            'selected_payment_accounts.*' => 'integer|exists:payment_accounts,id',
            'ppn_percentage' => 'nullable|numeric|min:0|max:100',
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
            'tipe_nota.required' => 'Tipe nota tidak boleh kosong.',
            'tipe_nota.in' => 'Tipe nota tidak valid.',
            'nama_proyek.required' => 'Nama proyek tidak boleh kosong.',
            'nama_proyek.max' => 'Nama proyek maksimal 255 karakter.',
            'nota_date.required' => 'Tanggal nota tidak boleh kosong.',
            'nota_date.date' => 'Format tanggal tidak valid.',
            'periode_start.date' => 'Format tanggal periode tidak valid.',
            'periode_end.date' => 'Format tanggal periode tidak valid.',
            'kepada.required' => 'Kepada tidak boleh kosong.',
            'kepada.max' => 'Kepada maksimal 255 karakter.',
            'faktur_no.required' => 'Faktur No tidak boleh kosong.',
            'faktur_no.max' => 'Faktur No maksimal 100 karakter.',
            'sj_no.required' => 'SJ No tidak boleh kosong.',
            'sj_no.max' => 'SJ No maksimal 100 karakter.',
            'item_banyaknya.required' => 'Minimal harus ada 1 item barang.',
            'item_banyaknya.min' => 'Minimal harus ada 1 item barang.',
            'item_banyaknya.*.required' => 'Jumlah item tidak boleh kosong.',
            'item_banyaknya.*.integer' => 'Jumlah item harus berupa angka.',
            'item_banyaknya.*.min' => 'Jumlah item minimal 1.',
            'item_nama_barang.required' => 'Nama barang tidak boleh kosong.',
            'item_nama_barang.*.required' => 'Nama barang tidak boleh kosong.',
            'item_nama_barang.*.max' => 'Nama barang maksimal 255 karakter.',
            'item_harga_satuan.required' => 'Harga satuan tidak boleh kosong.',
            'item_harga_satuan.*.required' => 'Harga satuan tidak boleh kosong.',
            'item_quantity.required' => 'Minimal harus ada 1 item barang.',
            'item_quantity.*.required' => 'Quantity tidak boleh kosong.',
            'item_quantity.*.integer' => 'Quantity harus berupa angka.',
            'item_quantity.*.min' => 'Quantity minimal 1.',
            'item_satuan.*.max' => 'Satuan maksimal 50 karakter.',
            'item_harga.required' => 'Harga tidak boleh kosong.',
            'item_harga.*.required' => 'Harga tidak boleh kosong.',
            'penerima.max' => 'Penerima maksimal 255 karakter.',
            'petinggi_id.exists' => 'Petinggi yang dipilih tidak valid.',
            'divisi.max' => 'Divisi maksimal 100 karakter.',
            'selected_payment_accounts.*.exists' => 'Bank yang dipilih tidak valid.',
            'ppn_percentage.numeric' => 'Persentase PPN harus berupa angka.',
            'ppn_percentage.min' => 'Persentase PPN minimal 0.',
            'ppn_percentage.max' => 'Persentase PPN maksimal 100.',
            'location.max' => 'Lokasi maksimal 100 karakter.',
        ];
    }

    /**
     * Validasi tambahan setelah aturan utama dijalankan.
     *
     * Memastikan periode akhir tidak mendahului periode awal.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $start = $this->input('periode_start');
            $end = $this->input('periode_end');

            if ($start && $end && strtotime($end) < strtotime($start)) {
                $validator->errors()->add('periode_end', 'Periode "s/d" tidak boleh sebelum periode awal.');
            }
        });
    }
}
