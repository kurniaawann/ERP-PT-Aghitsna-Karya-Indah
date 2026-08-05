<?php

namespace App\Http\Requests\Administrasi;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi penyimpanan Penawaran Aluminium.
 *
 * Memastikan semua field wajib terisi, minimal 1 rekening pembayaran
 * dipilih, data groups valid, dan volume bernilai numerik.
 */
class StoreAluminiumQuotationRequest extends FormRequest
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
     * Aturan validasi untuk penyimpanan Penawaran Aluminium.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'recipient' => 'required|string|max:255',
            'date' => 'required|date',
            'groups_json' => 'required|string',
            'selected_payment_accounts' => 'required|array|min:1',
            'selected_payment_accounts.*' => 'integer|exists:payment_accounts,id',
            'subject' => 'nullable|string|max:255',
            'project_description' => 'nullable|string|max:255',
            'signed_by_id' => 'nullable|exists:executives,id',
            'division_id' => 'nullable|exists:divisions,id',
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
            'recipient.required' => 'Nama penerima harus diisi.',
            'recipient.max' => 'Nama penerima maksimal 255 karakter.',
            'date.required' => 'Tanggal penawaran harus diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'groups_json.required' => 'Data kelompok item harus diisi.',
            'groups_json.string' => 'Data kelompok item harus berupa string JSON.',
            'selected_payment_accounts.required' => 'Minimal 1 rekening pembayaran harus dipilih.',
            'selected_payment_accounts.array' => 'Data rekening pembayaran tidak valid.',
            'selected_payment_accounts.min' => 'Minimal 1 rekening pembayaran harus dipilih.',
            'selected_payment_accounts.*.integer' => 'ID rekening pembayaran tidak valid.',
            'selected_payment_accounts.*.exists' => 'Rekening pembayaran yang dipilih tidak ditemukan.',
            'subject.max' => 'Perihal maksimal 255 karakter.',
            'project_description.max' => 'Deskripsi proyek maksimal 255 karakter.',
            'signed_by_id.exists' => 'Nama penandatangan yang dipilih tidak ditemukan.',
            'division_id.exists' => 'Divisi yang dipilih tidak ditemukan.',
        ];
    }

    /**
     * Validasi tambahan: groups_json harus berisi array yang valid dengan minimal 1 group.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $groupsJson = $this->input('groups_json');

        if ($groupsJson) {
            $groups = json_decode($groupsJson, true);

            $this->merge([
                'groups_count' => is_array($groups) ? count($groups) : 0,
            ]);
        }
    }

    /**
     * Menambahkan validasi custom setelah aturan utama dijalankan.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $groupsJson = $this->input('groups_json');

            if (!$groupsJson) {
                return;
            }

            $groups = json_decode($groupsJson, true);

            if (!is_array($groups) || count($groups) === 0) {
                $validator->errors()->add('groups_json', 'Minimal 1 kelompok harus ditambahkan.');
                return;
            }

            foreach ($groups as $groupIndex => $group) {
                if (empty($group['name'])) {
                    $validator->errors()->add('groups_json', 'Nama kelompok tidak boleh kosong.');
                    return;
                }

                if (empty($group['items']) || count($group['items']) === 0) {
                    $validator->errors()->add('groups_json', 'Kelompok "' . ($group['name'] ?? '') . '" harus memiliki minimal 1 item.');
                    return;
                }

                foreach ($group['items'] as $item) {
                    if (!empty($item['volume']) && !is_numeric($item['volume'])) {
                        $validator->errors()->add('groups_json', 'Volume harus berupa angka.');
                        return;
                    }

                    if (isset($item['volume']) && is_numeric($item['volume']) && $item['volume'] < 0) {
                        $validator->errors()->add('groups_json', 'Volume tidak boleh bernilai negatif.');
                        return;
                    }
                }
            }
        });
    }
}
