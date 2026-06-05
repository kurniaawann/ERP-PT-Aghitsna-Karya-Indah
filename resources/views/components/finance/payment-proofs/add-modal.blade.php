<x-modal id="addModal" title="Tambah Bukti Pembayaran" action="{{ route('payment-proofs.store') }}" method="POST"
    buttonText="Simpan" enctype="multipart/form-data">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Modul <span class="text-error">*</span></label>
        <select name="module_type" id="payment-proof-module-create" class="w-full border rounded p-2" required>
            @foreach ($moduleOptions as $module)
                <option value="{{ $module['value'] }}">{{ $module['label'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kategori Bukti <span class="text-error">*</span></label>
        <select name="invoice_type" id="payment-proof-invoice-type-create" class="w-full border rounded p-2" required>
            <option value="">Pilih kategori</option>
            @foreach ($invoiceTypeOptions as $invoiceType)
                <option value="{{ $invoiceType['value'] }}">{{ $invoiceType['label'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Invoice <span class="text-error">*</span></label>
        <select name="invoice_number" id="payment-proof-invoice-number-create" class="w-full border rounded p-2"
            required disabled size="8">
            <option value="">Pilih kategori dulu</option>
        </select>
        <p class="text-xs text-text-secondary mt-1">Gulir daftar invoice untuk menampilkan data berikutnya.</p>
    </div>

    <div class="mb-3 p-3 bg-surface-secondary rounded border">
        <div id="payment-proof-stage-wrap-create">
            <label class="block text-text-primary mb-1">Tahap Pembayaran</label>
            <p id="payment-proof-stage-create" class="font-semibold text-primary">-</p>
        </div>
        <input type="hidden" name="payment_stage" id="payment-proof-stage-input-create">
    </div>

    <div class="mb-3 p-3 border rounded bg-amber-50 hidden" id="payment-proof-amount-wrap-create">
        <label class="block text-text-primary mb-1">Nominal Pembayaran <span class="text-error">*</span></label>
        <input type="number" name="amount" id="payment-proof-amount-create" min="1" step="1"
            class="w-full border rounded p-2" placeholder="Masukkan nominal pembayaran" required>
        <p id="payment-proof-amount-help-create" class="text-xs text-text-secondary mt-1">Pilih invoice terlebih dahulu
            agar sisa tagihan tampil di sini.</p>
        <p class="text-xs text-text-secondary mt-1">Nominal ini hanya bisa diisi manual untuk invoice proyek.</p>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Bukti Gambar <span class="text-error">*</span></label>
        <input type="file" name="proof_image" accept="image/jpeg,image/png,image/gif,image/webp"
            class="w-full border rounded p-2" required>
        <p class="text-xs text-text-secondary mt-1">Gambar akan di-resize otomatis supaya tidak terlalu besar.</p>
    </div>
</x-modal>
