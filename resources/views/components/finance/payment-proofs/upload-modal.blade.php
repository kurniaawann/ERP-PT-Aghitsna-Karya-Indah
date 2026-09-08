{{-- =====================================================================
     Komponen: Upload Bukti Pembayaran (shared modal)
     Dipakai: dirender SEKALI di halaman modul (di luar form modal Edit),
     dipicu dari tombol "Upload Bukti Pembayaran" di
     components.finance.payment-proofs.manager.
     Field invoice_type & invoice_number di-set via JS (openPaymentProofUpload)
     sebelum modal dibuka.

     Data dari route payment-proofs.store ('finance' fixed).
     ===================================================================== --}}
<x-modal id="paymentProofUploadModal" title="Upload Bukti Pembayaran"
    action="{{ route('payment-proofs.store') }}" method="POST" buttonText="Simpan"
    enctype="multipart/form-data" formId="payment-proof-upload-form">

    <input type="hidden" name="module_type" value="finance">
    <input type="hidden" name="invoice_type" id="payment-proof-invoice-type" value="">
    <input type="hidden" name="invoice_number" id="payment-proof-invoice-number" value="">

    {{-- Section: Info Invoice (read-only) --}}
    <div class="mb-3 p-3 bg-surface-secondary rounded border">
        <label class="block text-text-primary mb-1">Invoice</label>
        <p id="payment-proof-invoice-label" class="font-semibold text-primary">-</p>
    </div>

    {{-- Section: Nominal Pembayaran (hanya proyek/recap) --}}
    <div class="mb-3 p-3 border rounded bg-amber-50 hidden" id="payment-proof-amount-wrap">
        <label class="block text-text-primary mb-1">Nominal Pembayaran <span class="text-error">*</span></label>
        <input type="text" name="amount" id="payment-proof-amount" inputmode="numeric" value="Rp 0"
            class="w-full border rounded p-2" placeholder="Rp 0">
        <p class="text-xs text-text-secondary mt-1">Nominal ini diisi manual untuk invoice proyek dan rekap proyek.</p>
    </div>

    {{-- Section: Tanggal Pembayaran --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Pembayaran</label>
        <input type="date" name="payment_date" value="{{ now()->toDateString() }}"
            class="w-full border rounded p-2">
        <p class="text-xs text-text-secondary mt-1">Isi manual jika tanggal pembayaran berbeda dari hari ini.</p>
    </div>

    {{-- Section: File Upload --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Bukti Gambar <span class="text-error">*</span></label>
        <input type="file" name="proof_image" accept="image/jpeg,image/png,image/gif,image/webp"
            class="w-full border rounded p-2" required>
        <p class="text-xs text-text-secondary mt-1">Format: JPG, PNG, GIF, WEBP. Maksimal 5 MB.</p>
    </div>
</x-modal>
