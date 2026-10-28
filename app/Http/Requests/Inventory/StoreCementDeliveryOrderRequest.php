<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi penyimpanan DO Semen (header + baris detail).
 */
class StoreCementDeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tanggal' => 'required|date',
            'tanggal_datang' => 'nullable|date',
            'tanggal_bayar' => 'nullable|date',
            'harga_modal' => 'required|string',

            'cements' => 'nullable|array',
            'cements.*.tanggal' => 'nullable|date',
            'cements.*.nama_proyek' => 'required|string|max:255',
            'cements.*.name' => 'nullable|string|max:255',
            'cements.*.jumlah' => 'required|integer|min:0',
            'cements.*.satuan' => 'nullable|string|max:50',
            'cements.*.harga' => 'required|string',
            'cements.*.tanggal_lunas' => 'nullable|date',
        ];
    }

    /**
     * Pesan validasi dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanggal.required' => 'Tanggal DO harus diisi.',
            'tanggal.date' => 'Format tanggal DO tidak valid.',
            'tanggal_datang.date' => 'Format tanggal datang tidak valid.',
            'tanggal_bayar.date' => 'Format tanggal bayar tidak valid.',
            'harga_modal.required' => 'Harga modal harus diisi.',
            'cements.*.tanggal.date' => 'Format tanggal pada data semen tidak valid.',
            'cements.*.nama_proyek.required' => 'Nama proyek pada data semen harus diisi.',
            'cements.*.nama_proyek.max' => 'Nama proyek pada data semen maksimal 255 karakter.',
            'cements.*.name.max' => 'Nama pada data semen maksimal 255 karakter.',
            'cements.*.jumlah.required' => 'Jumlah (zak) pada data semen harus diisi.',
            'cements.*.jumlah.integer' => 'Jumlah (zak) pada data semen harus berupa angka bulat.',
            'cements.*.jumlah.min' => 'Jumlah (zak) pada data semen tidak boleh negatif.',
            'cements.*.harga.required' => 'Harga pada data semen harus diisi.',
            'cements.*.tanggal_lunas.date' => 'Format tanggal lunas pada data semen tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $cements = collect($this->input('cements', []))
            ->filter(fn ($row) => !empty($row['nama_proyek']) || !empty($row['jumlah']))
            ->map(fn ($row) => $row + ['jumlah' => (int) ($row['jumlah'] ?? 0)])
            ->values()
            ->all();

        $this->merge([
            'cements' => $cements,
        ]);
    }
}
