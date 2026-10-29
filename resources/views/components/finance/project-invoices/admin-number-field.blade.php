{{-- =====================================================================
     Input No Invoice khusus role admin (format {nomor}/AKI/{bulan}/{tahun}),
     gaya Filament.

     User cukup mengetik nomor urut (angka saja, leading zero dipertahankan,
     contoh 060). Bulan (romawi) & tahun 4 digit diambil otomatis dari
     Tanggal Invoice pada form yang sama → contoh 060/AKI/VI/2026.
     Preview nomor lengkap, saran nomor berikutnya, dan cek nomor dobel
     dilakukan live oleh JS (initAdminNumberFields) memakai daftar nomor
     yang sudah dipakai (#proyek-invoice-taken-numbers). Validasi akhir
     tetap di server (Store/UpdateProyekInvoiceRequest).

     Hook JS (project-invoices/form.js): [data-admin-number-field],
     data-current-number, .invoice-seq-input, .invoice-number-preview,
     .invoice-number-hint, .invoice-number-error, .invoice-number-error-text.

     Props:
     - invoice : InvoiceProyek yang diedit (null untuk tambah). Hanya
                 dipakai untuk invoice berformat baru; nomor lama read-only.
     ===================================================================== --}}
@props(['invoice' => null])

@php
    $numberParts = $invoice ? \App\Models\Finance\InvoiceProyek::parseAdminInvoiceNumber($invoice->invoice_number) : null;
@endphp

<div {{ $attributes->class('grid content-start gap-y-2') }} data-admin-number-field data-current-number="{{ $invoice?->invoice_number }}">
    <label for="invoice-number-seq" class="inline-flex items-center gap-x-3 text-sm font-medium leading-6 text-gray-950">
        <span>No. Invoice<sup class="ms-0.5 font-medium text-red-600">*</sup></span>
    </label>

    <div class="flex flex-col gap-2 sm:flex-row sm:items-stretch">
        <div class="sm:w-32">
            <x-fi.input id="invoice-number-seq" name="invoice_number_seq" inputmode="numeric" maxlength="10" pattern="[0-9]+"
                :value="old('invoice_number_seq', $numberParts['sequence'] ?? '')" class="invoice-seq-input"
                placeholder="Contoh: 060" required autocomplete="off"
                oninvalid="if (this.validity.valueMissing) { this.setCustomValidity('No invoice wajib diisi, contoh 060'); } else if (this.validity.patternMismatch) { this.setCustomValidity('No invoice hanya boleh berisi angka, contoh 060'); }"
                oninput="this.setCustomValidity('')" />
        </div>
        <div class="flex min-w-0 flex-1 items-center gap-2 rounded-lg bg-gray-50 px-3 py-1.5 ring-1 ring-inset ring-gray-950/5">
            <span class="whitespace-nowrap text-sm text-gray-500">No lengkap:</span>
            <span class="invoice-number-preview break-all font-mono text-sm font-semibold text-primary">-</span>
        </div>
    </div>

    <p class="text-sm text-gray-500">
        Ketik nomor urutnya saja (angka). Bulan (romawi) &amp; tahun otomatis dari Tanggal Invoice.
    </p>
    <p class="invoice-number-hint hidden text-sm text-gray-500"></p>
    <p class="invoice-number-error hidden text-sm text-red-600">
        <x-fi.icon name="exclamation-circle" class="-mt-0.5 inline h-4 w-4" />
        <span class="invoice-number-error-text"></span>
    </p>
    @if ($invoice)
        <p class="flex items-start gap-1.5 text-sm text-amber-700">
            <x-fi.icon name="information-circle" class="mt-0.5 h-4 w-4 shrink-0" />
            <span>
                Mengubah nomor urut atau bulan/tahun Tanggal Invoice akan mengubah No Invoice. Bukti pembayaran &amp;
                kwitansi terkait ikut diperbarui.
            </span>
        </p>
    @endif
</div>
