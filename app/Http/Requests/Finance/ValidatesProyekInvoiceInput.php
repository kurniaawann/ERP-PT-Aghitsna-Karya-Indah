<?php

namespace App\Http\Requests\Finance;

use App\Models\Finance\InvoiceProyek;
use App\Services\Finance\ProyekInvoiceService;
use Illuminate\Contracts\Validation\Validator;

/**
 * Helper validasi bersama untuk Store/Update Invoice Proyek.
 *
 * - Nomor urut invoice admin (invoice_number_seq): dirapikan & diberi pesan
 *   error Bahasa Indonesia.
 * - Persentase item admin bersifat opsional: kosong → null (jumlah = harga);
 *   bila diisi harus angka > 0 dan maksimal 100.
 * - Volume & satuan item superadmin bersifat opsional: volume kosong → null
 *   (item borongan, jumlah = harga); bila diisi harus angka tidak negatif.
 * - Pesan error validasi pertama di-flash ke session 'error' agar tampil
 *   sebagai toast (halaman invoice tidak merender $errors per field).
 */
trait ValidatesProyekInvoiceInput
{
    /**
     * Apakah user login ber-role admin (format invoice admin).
     */
    protected function isAdminUser(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Buang spasi pada nomor urut invoice admin (mis. " 060 " → "060").
     * Leading zero dipertahankan apa adanya.
     */
    protected function prepareInvoiceNumberSequence(): void
    {
        if ($this->has('invoice_number_seq') && $this->input('invoice_number_seq') !== null) {
            $this->merge([
                'invoice_number_seq' => preg_replace('/\s+/', '', (string) $this->input('invoice_number_seq')),
            ]);
        }
    }

    /**
     * Pesan error nomor urut invoice admin.
     *
     * @return array<string, string>
     */
    protected function invoiceNumberSequenceMessages(): array
    {
        return [
            'invoice_number_seq.required' => 'No invoice wajib diisi (cukup nomor urutnya, contoh 060).',
            'invoice_number_seq.regex' => 'No invoice hanya boleh berisi angka (contoh 060).',
            'invoice_number_seq.max' => 'No invoice maksimal 10 digit.',
        ];
    }

    /**
     * Validasi persentase setiap item format admin.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @param  mixed  $items  Array item (hasil decode JSON / input array)
     */
    protected function validateItemPercentages(Validator $validator, $items): void
    {
        if (! is_array($items)) {
            return;
        }

        foreach (array_values($items) as $index => $item) {
            if (! is_array($item) || ! InvoiceProyek::isAdminItem($item)) {
                continue;
            }

            $raw = $item['persentase'] ?? null;

            if ($raw === null || (is_string($raw) && trim($raw) === '')) {
                continue;
            }

            $normalized = is_string($raw) ? str_replace(',', '.', trim($raw)) : $raw;
            $percentage = ProyekInvoiceService::normalizeOptionalPercentage($raw);

            if (! is_numeric($normalized) || $percentage === null || $percentage <= 0 || $percentage > 100) {
                $validator->errors()->add(
                    "items.{$index}.persentase",
                    'Persentase item ke-' . ($index + 1) . ' harus berupa angka lebih dari 0 dan maksimal 100, atau dikosongkan.'
                );
            }
        }
    }

    /**
     * Validasi volume setiap item format superadmin.
     *
     * Volume dan satuan bersifat OPSIONAL: volume kosong → item borongan
     * (Jumlah = Harga). Bila volume diisi harus berupa angka dan tidak
     * negatif.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @param  mixed  $items  Array item (hasil decode JSON / input array)
     */
    protected function validateItemVolumes(Validator $validator, $items): void
    {
        if (! is_array($items)) {
            return;
        }

        foreach (array_values($items) as $index => $item) {
            if (! is_array($item) || InvoiceProyek::isAdminItem($item)) {
                continue;
            }

            if (! InvoiceProyek::hasEmptyVolume($item)) {
                $raw = $item['volume'];
                $normalized = is_string($raw) ? str_replace(',', '.', trim($raw)) : $raw;

                if (! is_numeric($normalized) || (float) $normalized < 0) {
                    $validator->errors()->add(
                        "items.{$index}.volume",
                        'Volume item ke-' . ($index + 1) . ' harus berupa angka (tidak negatif), atau dikosongkan.'
                    );
                }
            }

        }
    }

    /**
     * Flash pesan error validasi pertama ke session agar tampil di toast.
     */
    protected function flashFirstValidationError(Validator $validator): void
    {
        $firstMessage = $validator->errors()->first();

        if ($firstMessage) {
            session()->flash('error', $firstMessage);
        }
    }
}
