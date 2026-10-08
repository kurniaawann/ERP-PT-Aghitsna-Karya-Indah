{{-- =====================================================================
     Input No Invoice khusus role admin (format {nomor}/AKI/{bulan}/{tahun}).

     User cukup mengetik nomor urut (angka saja, leading zero dipertahankan,
     contoh 060). Bulan (romawi) & tahun 4 digit diambil otomatis dari
     Tanggal Invoice pada form yang sama → contoh 060/AKI/VI/2026.
     Preview nomor lengkap, saran nomor berikutnya, dan cek nomor dobel
     dilakukan live oleh JS (initAdminNumberFields) memakai daftar nomor
     yang sudah dipakai (#proyek-invoice-taken-numbers). Validasi akhir
     tetap di server (Store/UpdateProyekInvoiceRequest).

     Props:
     - invoice : InvoiceProyek yang diedit (null untuk modal tambah). Hanya
                 dipakai untuk invoice berformat baru; nomor lama read-only.
     ===================================================================== --}}
@props(['invoice' => null])

@php
    $numberParts = $invoice ? \App\Models\Finance\InvoiceProyek::parseAdminInvoiceNumber($invoice->invoice_number) : null;
@endphp

<div class="mb-3" data-admin-number-field data-current-number="{{ $invoice?->invoice_number }}">
    <label class="block text-text-primary mb-1">No Invoice <span class="text-error">*</span></label>
    <div class="flex flex-col sm:flex-row sm:items-stretch gap-2">
        <input type="text" name="invoice_number_seq" inputmode="numeric" maxlength="10" pattern="[0-9]+"
            value="{{ $numberParts['sequence'] ?? '' }}"
            class="invoice-seq-input w-full sm:w-32 border border-border-strong rounded-lg p-2 bg-surface-base text-text-input"
            placeholder="Contoh: 060" required autocomplete="off"
            oninvalid="if (this.validity.valueMissing) { this.setCustomValidity('No invoice wajib diisi, contoh 060'); } else if (this.validity.patternMismatch) { this.setCustomValidity('No invoice hanya boleh berisi angka, contoh 060'); }"
            oninput="this.setCustomValidity('')">
        <div class="flex-1 flex items-center gap-2 px-3 py-2 rounded-lg border border-dashed border-border-strong bg-surface-secondary">
            <span class="text-xs text-text-secondary whitespace-nowrap">No lengkap:</span>
            <span class="invoice-number-preview font-mono font-semibold text-primary break-all">-</span>
        </div>
    </div>
    <p class="text-xs text-text-secondary mt-1">
        Ketik nomor urutnya saja (angka). Bulan (romawi) &amp; tahun otomatis dari Tanggal Invoice.
    </p>
    <p class="invoice-number-hint hidden text-xs text-text-secondary mt-0.5"></p>
    <p class="invoice-number-error hidden mt-1 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
        <i class="fa-solid fa-exclamation-circle"></i>
        <span class="invoice-number-error-text"></span>
    </p>
    @if ($invoice)
        <p class="text-xs text-warning mt-1">
            <i class="fa-solid fa-circle-info"></i>
            Mengubah nomor urut atau bulan/tahun Tanggal Invoice akan mengubah No Invoice. Bukti pembayaran &amp;
            kwitansi terkait ikut diperbarui.
        </p>
    @endif
</div>
