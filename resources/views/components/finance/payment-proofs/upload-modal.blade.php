{{-- =====================================================================
     Komponen: Upload Bukti Pembayaran (shared modal, gaya Filament)
     Dipakai: dirender SEKALI di halaman modul (di luar form Edit),
     dipicu dari tombol "Upload Bukti Pembayaran" di
     components.finance.payment-proofs.manager / menu Aksi tabel.
     Field invoice_type & invoice_number di-set via JS (openPaymentProofUpload)
     sebelum modal dibuka.

     Hook JS (payment-proofs/embedded.js): #payment-proof-upload-form,
     #payment-proof-invoice-type, #payment-proof-invoice-number,
     #payment-proof-invoice-label, #payment-proof-feedback,
     #payment-proof-amount-wrap, #payment-proof-amount,
     #payment-proof-amount-error.

     Data dari route payment-proofs.store ('finance' fixed).
     ===================================================================== --}}
<x-fi.modal id="paymentProofUploadModal" heading="Upload Bukti Pembayaran" icon="arrow-up-tray"
    :action="route('payment-proofs.store')" submitLabel="Simpan" enctype="multipart/form-data"
    formId="payment-proof-upload-form">

    <input type="hidden" name="module_type" value="finance">
    <input type="hidden" name="invoice_type" id="payment-proof-invoice-type" value="">
    <input type="hidden" name="invoice_number" id="payment-proof-invoice-number" value="">

    <div id="payment-proof-feedback" role="alert"
        class="hidden rounded-lg bg-red-50 p-3 text-sm text-red-700 ring-1 ring-inset ring-red-600/20"></div>

    {{-- Info Invoice (read-only) --}}
    <div class="rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-inset ring-gray-950/5">
        <p class="text-sm text-gray-500">Invoice</p>
        <p id="payment-proof-invoice-label" class="font-semibold text-gray-950">-</p>
    </div>

    {{-- Nominal Pembayaran (hanya proyek/recap) --}}
    <div id="payment-proof-amount-wrap" class="hidden">
        <x-fi.field label="Nominal Pembayaran" for="payment-proof-amount" required
            hint="Nominal ini diisi manual untuk invoice proyek dan rekap proyek.">
            <x-fi.input name="amount" id="payment-proof-amount" prefix="Rp" inputmode="numeric" placeholder="0"
                oninput="formatCurrencyInput(this)" />
            <div id="payment-proof-amount-error" class="hidden text-sm text-red-600"></div>
        </x-fi.field>
    </div>

    {{-- Tanggal Pembayaran --}}
    <x-fi.field label="Tanggal Pembayaran" for="payment-proof-date"
        hint="Isi manual jika tanggal pembayaran berbeda dari hari ini.">
        <x-fi.input type="date" id="payment-proof-date" name="payment_date" :value="now()->toDateString()" />
    </x-fi.field>

    {{-- File Bukti --}}
    <x-fi.field label="Bukti Gambar" for="payment-proof-file" required hint="Format: JPG, PNG, GIF, WEBP. Maksimal 5 MB.">
        <input type="file" id="payment-proof-file" name="proof_image" accept="image/jpeg,image/png,image/gif,image/webp" required
            class="block w-full rounded-lg bg-white text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/10 file:me-3 file:cursor-pointer file:rounded-s-lg file:border-0 file:border-e file:border-gray-200 file:bg-gray-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary">
    </x-fi.field>
</x-fi.modal>
