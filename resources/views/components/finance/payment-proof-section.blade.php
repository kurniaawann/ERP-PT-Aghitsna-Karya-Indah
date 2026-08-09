@php
    $paymentProofs = collect($paymentProofs ?? []);
@endphp

<div class="mb-4 p-4 border rounded-lg bg-surface-secondary">
    <div class="flex flex-col gap-1 mb-3">
        <label class="block text-sm font-semibold text-text-primary">Bukti Pembayaran</label>
        <p class="text-xs text-text-label">
            Upload gambar bukti pembayaran. File akan diperkecil otomatis supaya tidak terlalu besar.
        </p>
    </div>

    @if ($paymentProofs->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
            @foreach ($paymentProofs as $proof)
                <div class="overflow-hidden rounded-lg border bg-white">
                    <a href="{{ asset('storage/' . $proof->file_path) }}" target="_blank" rel="noopener noreferrer">
                        <img src="{{ asset('storage/' . $proof->file_path) }}" alt="Bukti pembayaran"
                            class="h-48 w-full object-cover">
                    </a>
                    <div class="p-3 space-y-2">
                        <div>
                            <p class="text-sm font-semibold text-text-primary truncate">{{ $proof->file_name }}</p>
                            <p class="text-xs text-text-label">
                                {{ optional($proof->payment_date ?? $proof->created_at)->format('d M Y') }}
                                @if ($proof->file_size)
                                    • {{ number_format($proof->file_size / 1024, 1, ',', '.') }} KB
                                @endif
                            </p>
                        </div>

                        <div class="flex items-center justify-between gap-2">
                            <a href="{{ asset('storage/' . $proof->file_path) }}" target="_blank" rel="noopener noreferrer"
                                class="text-sm text-blue-600 hover:underline">
                                Lihat
                            </a>

                            <form action="{{ route('payment-proofs.destroy', $proof->id) }}" method="POST"
                                onsubmit="return confirm('Hapus bukti pembayaran ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:underline">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="mb-4 rounded-lg border border-dashed border-border-strong bg-white p-4 text-sm text-text-label">
            Belum ada bukti pembayaran yang tersimpan.
        </div>
    @endif

    <form action="{{ route('payment-proofs.store') }}" method="POST" enctype="multipart/form-data"
        class="space-y-3 rounded-lg border bg-white p-3">
        @csrf
        <input type="hidden" name="invoice_type" value="{{ $invoiceType }}">
        <input type="hidden" name="invoice_number" value="{{ $invoice->invoice_number }}">

        <div>
            <label class="block text-sm font-medium text-text-primary mb-1">Tanggal Pembayaran</label>
            <input type="date" name="payment_date" value="{{ now()->toDateString() }}"
                class="block w-full rounded border border-border-strong p-2 text-sm">
            <p class="mt-1 text-xs text-text-label">Isi manual jika tanggal pembayaran berbeda dari hari ini.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-1">Upload Gambar</label>
            <input type="file" name="proof_image" accept="image/jpeg,image/png,image/gif,image/webp" required
                class="block w-full rounded border border-border-strong p-2 text-sm">
            <p class="mt-1 text-xs text-text-label">Format: JPG, PNG, GIF, WEBP. Maksimal 5 MB.</p>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Upload Bukti
            </button>
        </div>
    </form>
</div>
