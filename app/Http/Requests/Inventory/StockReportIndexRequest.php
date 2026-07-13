<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi parameter Laporan Stok Barang.
 *
 * Memastikan parameter filter yang diterima valid:
 * - start_date: tanggal mulai (opsional, default awal bulan ini)
 * - end_date: tanggal akhir (opsional, default hari ini, harus >= start_date)
 * - item_id: ID barang (opsional, harus ada di tabel items)
 */
class StockReportIndexRequest extends FormRequest
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
     * Aturan validasi untuk parameter Laporan Stok.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'item_id' => 'nullable|exists:items,id_item',
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
            'start_date.date' => 'Format tanggal mulai tidak valid.',
            'end_date.date' => 'Format tanggal akhir tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai.',
            'item_id.exists' => 'Barang yang dipilih tidak ditemukan.',
        ];
    }
}
