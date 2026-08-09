@php
    $moduleLabel = collect($moduleOptions)->firstWhere('value', $paymentProof->module_type)['label'] ?? $paymentProof->module_type;
    $invoiceTypeLabel = collect($invoiceTypeOptions)->firstWhere('value', $paymentProof->invoice_type)['label'] ?? $paymentProof->invoice_type;
    $lookup = data_get($invoiceLookup, $paymentProof->module_type . '.' . $paymentProof->invoice_type . '.' . $paymentProof->invoice_number, []);
    $invoiceLabel = $lookup['label'] ?? $paymentProof->invoice_number;
@endphp

{{-- Section: Edit Payment Proof Modal --}}
<x-modal id="editModal-{{ $paymentProof->id }}" title="Edit Bukti Pembayaran"
    action="{{ route('payment-proofs.update', $paymentProof->id) }}" method="PUT" buttonText="Update"
    enctype="multipart/form-data">

    {{-- Section: Module (read-only) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Modul</label>
        <input type="text" value="{{ $moduleLabel }}" class="w-full border rounded p-2 bg-gray-100" readonly>
    </div>

    {{-- Section: Invoice Type (read-only) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kategori Bukti</label>
        <input type="text" value="{{ $invoiceTypeLabel }}" class="w-full border rounded p-2 bg-gray-100" readonly>
    </div>

    {{-- Section: Invoice Number (read-only) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Invoice</label>
        <input type="text" value="{{ $invoiceLabel }}" class="w-full border rounded p-2 bg-gray-100" readonly>
    </div>

    {{-- Section: Payment Stage (read-only) --}}
    <div class="mb-3 p-3 bg-surface-secondary rounded border">
        <label class="block text-text-primary mb-1">Tahap Pembayaran</label>
        <p class="font-semibold text-primary">Pembayaran ke {{ $paymentProof->payment_stage ?? '-' }}</p>
    </div>

    {{-- Section: Amount (read-only) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nominal Pembayaran</label>
        <input type="text"
            value="Rp {{ number_format($paymentProof->amount ?? 0, 0, ',', '.') }}"
            class="w-full border rounded p-2 bg-gray-100" readonly>
    </div>

    {{-- Section: Payment Date --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Pembayaran</label>
        <input type="date" name="payment_date"
            value="{{ optional($paymentProof->payment_date ?? $paymentProof->created_at)->format('Y-m-d') }}"
            class="w-full border rounded p-2">
        <p class="text-xs text-text-secondary mt-1">Ubah tanggal pembayaran sesuai kebutuhan.</p>
    </div>

    {{-- Section: Current File --}}
    <div class="mb-3 p-3 border rounded bg-blue-50">
        <label class="block text-text-primary mb-1">File Saat Ini</label>
        <a href="{{ asset('storage/' . $paymentProof->file_path) }}" target="_blank"
            class="text-blue-600 hover:underline text-sm">{{ $paymentProof->file_name }}</a>
    </div>

    {{-- Section: Replace File (opsional, untuk ubah tanggal tanpa ganti gambar) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Ganti Gambar</label>
        <input type="file" name="proof_image" accept="image/jpeg,image/png,image/gif,image/webp"
            class="w-full border rounded p-2">
        <p class="text-xs text-text-secondary mt-1">Format: JPG, PNG, GIF, WEBP. Maksimal 5 MB. Kosongkan jika hanya
            mengubah tanggal.</p>
    </div>
</x-modal>
