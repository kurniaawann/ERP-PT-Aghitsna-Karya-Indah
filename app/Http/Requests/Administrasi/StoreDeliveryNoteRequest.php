<?php

namespace App\Http\Requests\Administrasi;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi data surat jalan saat proses penyimpanan (create).
 *
 * Kelas ini bertanggung jawab atas validasi input dari form tambah surat jalan.
 * Validation rules didefinisikan di sini agar terpisah dari Controller (Single Responsibility).
 *
 * @package App\Http\Requests\Administrasi
 */
class StoreDeliveryNoteRequest extends FormRequest
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
     * Mendefinisikan aturan validasi untuk penyimpanan surat jalan.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_number' => 'required|string|max:100',
            'delivery_date' => 'required|date',
            'shipper_name' => 'required|string|max:255',
            'shipper_address' => 'required|string|max:500',
            'receiver_name' => 'required|string|max:255',
            'receiver_address' => 'required|string|max:500',
            'description' => 'nullable|string|max:1000',
            'item_no' => 'required|array|min:1',
            'item_no.*' => 'required|integer|min:1',
            'item_name' => 'required|array|min:1',
            'item_name.*' => 'required|string|max:255',
            'quantity' => 'required|array|min:1',
            'quantity.*' => 'required|integer|min:1',
            'unit' => 'nullable|array',
            'unit.*' => 'nullable|string|max:50',
            'item_notes' => 'nullable|array',
            'item_notes.*' => 'nullable|string|max:255',
            'driver_name' => 'nullable|string|max:255',
            'vehicle_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
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
            'document_number.required' => 'Nomor dokumen tidak boleh kosong.',
            'document_number.max' => 'Nomor dokumen maksimal 100 karakter.',
            'delivery_date.required' => 'Tanggal pengiriman tidak boleh kosong.',
            'delivery_date.date' => 'Format tanggal tidak valid.',
            'shipper_name.required' => 'Nama pengirim tidak boleh kosong.',
            'shipper_name.max' => 'Nama pengirim maksimal 255 karakter.',
            'shipper_address.required' => 'Alamat pengirim tidak boleh kosong.',
            'shipper_address.max' => 'Alamat pengirim maksimal 500 karakter.',
            'receiver_name.required' => 'Nama penerima tidak boleh kosong.',
            'receiver_name.max' => 'Nama penerima maksimal 255 karakter.',
            'receiver_address.required' => 'Alamat penerima tidak boleh kosong.',
            'receiver_address.max' => 'Alamat penerima maksimal 500 karakter.',
            'description.max' => 'Deskripsi maksimal 1000 karakter.',
            'item_no.required' => 'Minimal harus ada 1 item barang.',
            'item_no.min' => 'Minimal harus ada 1 item barang.',
            'item_no.*.required' => 'Nomor item tidak boleh kosong.',
            'item_no.*.integer' => 'Nomor item harus berupa angka.',
            'item_no.*.min' => 'Nomor item minimal 1.',
            'item_name.required' => 'Nama barang tidak boleh kosong.',
            'item_name.min' => 'Minimal harus ada 1 item barang.',
            'item_name.*.required' => 'Nama barang tidak boleh kosong.',
            'item_name.*.max' => 'Nama barang maksimal 255 karakter.',
            'quantity.required' => 'Jumlah barang tidak boleh kosong.',
            'quantity.min' => 'Minimal harus ada 1 item barang.',
            'quantity.*.required' => 'Jumlah barang tidak boleh kosong.',
            'quantity.*.integer' => 'Jumlah barang harus berupa angka.',
            'quantity.*.min' => 'Jumlah barang minimal 1.',
            'unit.*.max' => 'Satuan maksimal 50 karakter.',
            'item_notes.*.max' => 'Catatan item maksimal 255 karakter.',
            'driver_name.max' => 'Nama sopir maksimal 255 karakter.',
            'vehicle_number.max' => 'Nomor kendaraan maksimal 100 karakter.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }
}
