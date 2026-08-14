{{-- Section: Add Payment Proof Modal --}}
<x-modal id="addModal" title="Tambah Bukti Pembayaran" action="{{ route('payment-proofs.store') }}" method="POST"
    buttonText="Simpan" enctype="multipart/form-data">

    {{-- Section: Module Selection --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Modul <span class="text-error">*</span></label>
        <select name="module_type" id="payment-proof-module-create" class="w-full border rounded p-2" required>
            @foreach ($moduleOptions as $module)
                <option value="{{ $module['value'] }}">{{ $module['label'] }}</option>
            @endforeach
        </select>
    </div>

    {{-- Section: Invoice Type Selection --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kategori Bukti <span class="text-error">*</span></label>
        <select name="invoice_type" id="payment-proof-invoice-type-create" class="w-full border rounded p-2" required>
            <option value="">Pilih kategori</option>
            @foreach ($invoiceTypeOptions as $invoiceType)
                <option value="{{ $invoiceType['value'] }}">{{ $invoiceType['label'] }}</option>
            @endforeach
        </select>
    </div>

    {{-- Section: Invoice Number Selection --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Invoice <span class="text-error">*</span></label>
        <select name="invoice_number" id="payment-proof-invoice-number-create" class="w-full border rounded p-2"
            required disabled onfocus="this.size=8" onchange="this.size=1; this.blur()" onblur="this.size=1">
            <option value="">Pilih kategori dulu</option>
        </select>
        <p class="text-xs text-text-secondary mt-1">Gulir daftar invoice untuk menampilkan data berikutnya.</p>
    </div>

    {{-- Section: Payment Stage --}}
    <div id="payment-proof-stage-wrap-create" class="mb-3 p-3 bg-surface-secondary rounded border">
        <label class="block text-text-primary mb-1">Tahap Pembayaran</label>
        <p id="payment-proof-stage-create" class="font-semibold text-primary">-</p>
        <input type="hidden" name="payment_stage" id="payment-proof-stage-input-create">
    </div>

    {{-- Section: Amount Input --}}
    <div class="mb-3 p-3 border rounded bg-amber-50 hidden" id="payment-proof-amount-wrap-create">
        <label class="block text-text-primary mb-1">Nominal Pembayaran <span class="text-error">*</span></label>
        <input type="text" name="amount" id="payment-proof-amount-create" inputmode="numeric" value="Rp 0"
            class="w-full border rounded p-2" placeholder="Rp 0" required>
        <p id="payment-proof-amount-help-create" class="text-xs text-text-secondary mt-1">Pilih invoice terlebih dahulu
            agar sisa tagihan tampil di sini.</p>
        <p id="payment-proof-amount-warning-create" class="text-error text-sm mt-1 hidden"></p>
        <p class="text-xs text-text-secondary mt-1">Nominal ini hanya bisa diisi manual untuk invoice proyek dan rekap proyek. Rekap penjualan otomatis lunas mengikuti sisa tagihan (tidak bisa dicicil).</p>
    </div>

    {{-- Section: Payment Date --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Pembayaran</label>
        <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}"
            class="w-full border rounded p-2">
        <p class="text-xs text-text-secondary mt-1">Isi manual jika tanggal pembayaran berbeda dari hari ini.</p>
    </div>

    {{-- Section: File Upload --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Bukti Gambar <span class="text-error">*</span></label>
        <input type="file" name="proof_image" accept="image/jpeg,image/png,image/gif,image/webp"
            class="w-full border rounded p-2" required>
        <p class="text-xs text-text-secondary mt-1">Gambar akan di-resize otomatis supaya tidak terlalu besar.</p>
    </div>
</x-modal>
