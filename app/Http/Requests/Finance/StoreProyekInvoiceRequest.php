<?php

namespace App\Http\Requests\Finance;

use App\Models\Finance\InvoiceProyek;
use App\Services\Finance\ProyekInvoiceService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request untuk validasi pembuatan Invoice Proyek baru.
 *
 * Memastikan semua field wajib terisi, minimal ada 1 item,
 * discount/persentase tidak lebih dari 100%, dan minimal 1 rekening pembayaran.
 *
 * Khusus role admin:
 * - Nomor urut invoice (invoice_number_seq) wajib diisi angka saja; nomor
 *   lengkap {nomor}/AKI/{bulan romawi}/{yyyy} disusun dari tanggal invoice
 *   dan harus unik.
 * - Persentase item bersifat opsional (kosong → jumlah = harga).
 *
 * Tautan Rekap Proyek (project_recap_id) opsional untuk semua role dan
 * hanya boleh menunjuk rekap milik user login.
 */
class StoreProyekInvoiceRequest extends FormRequest
{
    use ValidatesProyekInvoiceInput;

    /**
     * Menentukan apakah user memiliki otorisasi untuk melakukan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rapikan nomor urut invoice admin sebelum validasi (buang spasi).
     */
    protected function prepareForValidation(): void
    {
        $this->prepareInvoiceNumberSequence();
    }

    /**
     * Aturan validasi untuk store Invoice Proyek.
     */
    public function rules(): array
    {
        return [
            'invoice_number' => 'nullable|string|max:255',
            'invoice_number_seq' => $this->isAdminUser()
                ? ['required', 'string', 'regex:/^\d+$/', 'max:10']
                : ['nullable'],
            'invoice_date' => 'required|date',
            'recipient' => 'required|string|max:255',
            'regarding' => 'nullable|string|max:255',
            'project_description' => 'nullable|string|max:255',
            'proyek' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'project_recap_id' => [
                'nullable',
                'string',
                Rule::exists('project_recaps', 'id')->where('created_by', auth()->id()),
            ],
            'items' => 'required|json',
            'discount_type' => 'nullable|in:percentage,amount',
            'discount_value' => 'nullable|numeric|min:0',
            'dp_type' => 'nullable|in:percentage,amount',
            'dp_value' => 'nullable|numeric|min:0',
            'ppn' => 'nullable|numeric|min:0|max:100',
            'selected_payment_accounts' => 'required|array|min:1',
            'selected_payment_accounts.*' => 'integer|exists:payment_accounts,id',
            'signed_by_id' => 'nullable|exists:executives,id',
            'division_id' => 'nullable|exists:divisions,id',
        ];
    }

    /**
     * Validasi lanjutan: keunikan nomor invoice admin & persentase item.
     *
     * Alur:
     * 1. Admin: susun nomor lengkap dari nomor urut + tanggal invoice; tolak
     *    bila nomor sudah dipakai invoice lain (primary key).
     * 2. Item admin: persentase boleh kosong; bila diisi harus > 0 dan ≤ 100.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $errors = $validator->errors();

            if ($this->isAdminUser() && ! $errors->has('invoice_number_seq') && ! $errors->has('invoice_date')) {
                $invoiceNumber = app(ProyekInvoiceService::class)->composeAdminInvoiceNumber(
                    (string) $this->input('invoice_number_seq'),
                    $this->input('invoice_date')
                );

                if (InvoiceProyek::whereKey($invoiceNumber)->exists()) {
                    $errors->add('invoice_number_seq', "No invoice {$invoiceNumber} sudah digunakan. Silakan gunakan nomor lain.");
                }
            }

            if (! $errors->has('items')) {
                $this->validateItemPercentages($validator, json_decode((string) $this->input('items'), true));
            }
        });
    }

    /**
     * Pesan error custom dalam Bahasa Indonesia.
     */
    public function messages(): array
    {
        return array_merge($this->invoiceNumberSequenceMessages(), [
            'invoice_date.required' => 'Tanggal invoice wajib diisi.',
            'invoice_date.date' => 'Format tanggal invoice tidak valid.',
            'recipient.required' => 'Nama penerima wajib diisi.',
            'recipient.max' => 'Nama penerima maksimal 255 karakter.',
            'project_description.max' => 'Deskripsi proyek maksimal 255 karakter.',
            'proyek.max' => 'Nama proyek maksimal 255 karakter.',
            'project_recap_id.exists' => 'Rekap proyek yang dipilih tidak ditemukan.',
            'items.required' => 'Minimal harus ada 1 item.',
            'items.json' => 'Format item tidak valid.',
            'selected_payment_accounts.required' => 'Minimal 1 rekening pembayaran harus dipilih.',
            'selected_payment_accounts.array' => 'Format rekening pembayaran tidak valid.',
            'selected_payment_accounts.min' => 'Minimal 1 rekening pembayaran harus dipilih.',
            'ppn.max' => 'PPN tidak boleh lebih dari 100%.',
        ]);
    }

    /**
     * Tampilkan pesan error validasi pertama sebagai toast.
     *
     * Halaman invoice tidak merender $errors per field, sehingga pesan
     * pertama di-flash ke session 'error' (ditampilkan komponen x-toast)
     * sebelum redirect kembali.
     */
    protected function failedValidation(Validator $validator)
    {
        $this->flashFirstValidationError($validator);

        parent::failedValidation($validator);
    }
}
