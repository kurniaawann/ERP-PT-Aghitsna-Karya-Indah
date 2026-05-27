<x-modal id="editModal-{{ $paymentProof->id }}" title="Edit Bukti Pembayaran"
    action="{{ route('payment-proofs.update', $paymentProof->id) }}" method="PUT" buttonText="Update"
    enctype="multipart/form-data">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Modul <span class="text-error">*</span></label>
        <select name="module_type" id="payment-proof-module-edit-{{ $paymentProof->id }}"
            class="w-full border rounded p-2" required>
            @foreach ($moduleOptions as $module)
                <option value="{{ $module['value'] }}"
                    {{ $paymentProof->module_type === $module['value'] ? 'selected' : '' }}>{{ $module['label'] }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kategori Bukti <span class="text-error">*</span></label>
        <select name="invoice_type" id="payment-proof-invoice-type-edit-{{ $paymentProof->id }}"
            class="w-full border rounded p-2" required>
            @foreach ($invoiceTypeOptions as $invoiceType)
                <option value="{{ $invoiceType['value'] }}"
                    {{ $paymentProof->invoice_type === $invoiceType['value'] ? 'selected' : '' }}>
                    {{ $invoiceType['label'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Invoice <span class="text-error">*</span></label>
        <select name="invoice_number" id="payment-proof-invoice-number-edit-{{ $paymentProof->id }}"
            class="w-full border rounded p-2" required size="8">
            <option value="">Pilih kategori</option>
        </select>
        <p class="text-xs text-text-secondary mt-1">Gulir daftar invoice untuk menampilkan data berikutnya.</p>
    </div>

    <div class="mb-3 p-3 bg-surface-secondary rounded border">
        <div id="payment-proof-stage-wrap-edit-{{ $paymentProof->id }}">
            <label class="block text-text-primary mb-1">Tahap Pembayaran</label>
            <p id="payment-proof-stage-edit-{{ $paymentProof->id }}" class="font-semibold text-primary">Pembayaran ke
                {{ $paymentProof->payment_stage ?? '-' }}</p>
        </div>
        <input type="hidden" name="payment_stage" id="payment-proof-stage-input-edit-{{ $paymentProof->id }}"
            value="{{ $paymentProof->payment_stage }}">
    </div>

    <div class="mb-3 p-3 border rounded bg-amber-50 hidden"
        id="payment-proof-amount-wrap-edit-{{ $paymentProof->id }}">
        <label class="block text-text-primary mb-1">Nominal Pembayaran <span class="text-error">*</span></label>
        <input type="number" name="amount" id="payment-proof-amount-edit-{{ $paymentProof->id }}" min="1"
            step="1" value="{{ $paymentProof->amount ?? '' }}" class="w-full border rounded p-2"
            placeholder="Masukkan nominal pembayaran" required>
        <p id="payment-proof-amount-help-edit-{{ $paymentProof->id }}" class="text-xs text-text-secondary mt-1">
            Nominal akan divalidasi terhadap sisa tagihan invoice yang dipilih.
        </p>
        <p class="text-xs text-text-secondary mt-1">Nominal ini hanya bisa diisi manual untuk invoice proyek.</p>
    </div>

    <div class="mb-3 p-3 border rounded bg-blue-50">
        <label class="block text-text-primary mb-1">File Saat Ini</label>
        <a href="{{ asset($paymentProof->file_path) }}" target="_blank"
            class="text-blue-600 hover:underline text-sm">Lihat gambar</a>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Ganti Gambar (Opsional)</label>
        <input type="file" name="proof_image" accept="image/jpeg,image/png,image/gif,image/webp"
            class="w-full border rounded p-2">
        <p class="text-xs text-text-secondary mt-1">Kosongkan jika tidak ingin mengganti file.</p>
    </div>
</x-modal>
