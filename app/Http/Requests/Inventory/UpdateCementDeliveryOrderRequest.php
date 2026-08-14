<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembaruan DO Semen (header + baris detail).
 */
class UpdateCementDeliveryOrderRequest extends FormRequest
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
            'cements.*.payment_account_id' => 'nullable|integer|exists:payment_accounts,id',
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
