<?php

namespace App\Http\Requests\Finance;

use App\Models\Finance\InvoiceProyek;
use App\Services\Finance\ProyekInvoiceService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request untuk validasi update Invoice Proyek.
 *
 * Sama dengan store namun items harus berupa array dengan minimal 1 item.
 *
 * Nomor invoice:
 * - Invoice admin format baru ({nomor}/AKI/{bulan romawi}/{yyyy}): nomor
 *   urut boleh diubah; nomor lengkap disusun ulang dari nomor urut + bulan/
 *   tahun tanggal invoice dan harus unik (selain invoice ini sendiri).
 * - Nomor format lama & nomor superadmin tidak bisa diubah (read-only).
 */
class UpdateProyekInvoiceRequest extends FormRequest
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
     * Aturan validasi untuk update Invoice Proyek.
     */
    public function rules(): array
    {
        return [
            'invoice_number_seq' => $this->canRenumber()
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
            'items' => 'required|array|min:1',
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
     * Validasi lanjutan: keunikan nomor invoice hasil edit, persentase item
     * (admin), serta volume/satuan opsional item (superadmin).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $errors = $validator->errors();
            $invoice = $this->routeInvoice();

            if ($invoice && $this->canRenumber() && ! $errors->has('invoice_number_seq') && ! $errors->has('invoice_date')) {
                $newNumber = app(ProyekInvoiceService::class)->resolveUpdatedInvoiceNumber($invoice, [
                    'invoice_number_seq' => $this->input('invoice_number_seq'),
                    'invoice_date' => $this->input('invoice_date'),
                ]);

                if ($newNumber !== $invoice->invoice_number && InvoiceProyek::whereKey($newNumber)->exists()) {
                    $errors->add('invoice_number_seq', "No invoice {$newNumber} sudah digunakan. Silakan gunakan nomor lain.");
                }
            }

            if (! $errors->has('items')) {
                $this->validateItemPercentages($validator, $this->input('items'));
                $this->validateItemVolumes($validator, $this->input('items'));
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
            'items.array' => 'Format item tidak valid.',
            'items.min' => 'Minimal harus ada 1 item.',
            'selected_payment_accounts.required' => 'Minimal 1 rekening pembayaran harus dipilih.',
            'selected_payment_accounts.array' => 'Format rekening pembayaran tidak valid.',
            'selected_payment_accounts.min' => 'Minimal 1 rekening pembayaran harus dipilih.',
            'ppn.max' => 'PPN tidak boleh lebih dari 100%.',
        ]);
    }

    /**
     * Tampilkan pesan error validasi pertama sebagai toast.
     */
    protected function failedValidation(Validator $validator)
    {
        $this->flashFirstValidationError($validator);

        parent::failedValidation($validator);
    }

    /**
     * Invoice yang sedang diedit (route model binding {proyek_invoice}).
     */
    private function routeInvoice(): ?InvoiceProyek
    {
        $invoice = $this->route('proyek_invoice');

        return $invoice instanceof InvoiceProyek ? $invoice : null;
    }

    /**
     * Apakah nomor invoice ini boleh diubah: hanya admin dan hanya invoice
     * berformat baru {nomor}/AKI/{bulan romawi}/{yyyy}.
     */
    private function canRenumber(): bool
    {
        $invoice = $this->routeInvoice();

        return $this->isAdminUser()
            && $invoice !== null
            && InvoiceProyek::parseAdminInvoiceNumber($invoice->invoice_number) !== null;
    }
}
