{{-- =====================================================================
     Komponen: Payment Proof Manager (dipakai di dalam modal Edit tiap modul)
     Tujuan: Menampilkan daftar bukti pembayaran milik sebuah invoice/rekap,
             dengan tombol "Upload" (membuka modal upload terpisah) dan
             "Hapus" (membuka modal hapus terpisah).
     Alasan desain: Modal Edit dibungkus satu <form> (x-modal). Untuk
     menghindari nested <form> (invalid html), upload & hapus dipindah ke
     modal terpisah yang dirender di luar form (lihat file upload-modal &
     delete-modal yang di-include di halaman modul).

     Parameter yang dipakai:
     - $proofs       : Collection bukti pembayaran (paymentProofs).
     - $invoiceType  : invoice_type ('proyek'|'alumunium'|'barang'|'recap'|'rekap_penjualan').
     - $invoiceKey   : Nilai invoice_number / id yang dikirim saat upload.
     - $manualAmount : bool, true bila nominal pembayaran diisi manual
                       (proyek/recap). Default false.
     ===================================================================== --}}
@php
    $proofs = collect($proofs ?? []);
    $manualAmount = $manualAmount ?? in_array($invoiceType ?? '', ['proyek', 'recap'], true);
@endphp

<div class="mb-4 p-4 border rounded-lg bg-surface-secondary">
    <div class="flex flex-col gap-1 mb-3">
        <label class="block text-sm font-semibold text-text-primary">Bukti Pembayaran</label>
        <p class="text-xs text-text-label">
            Kelola gambar bukti pembayaran. File akan diperkecil otomatis supaya tidak terlalu besar.
        </p>
    </div>

    @if ($proofs->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
            @foreach ($proofs as $proof)
                <div class="overflow-hidden rounded-lg border bg-white">
                    <a href="{{ asset('storage/' . $proof->file_path) }}" target="_blank" rel="noopener noreferrer">
                        <img src="{{ asset('storage/' . $proof->file_path) }}" alt="Bukti pembayaran"
                            class="h-36 w-full object-cover">
                    </a>
                    <div class="p-3 space-y-2">
                        <div>
                            <p class="text-sm font-semibold text-text-primary truncate">{{ $proof->file_name }}</p>
                            <p class="text-xs text-text-label">
                                {{ optional($proof->payment_date ?? $proof->created_at)->format('d M Y') }}
                                @if ($proof->amount)
                                    • Rp {{ number_format($proof->amount, 0, ',', '.') }}
                                @endif
                            </p>
                        </div>

                        <div class="flex items-center justify-between gap-2">
                            <a href="{{ asset('storage/' . $proof->file_path) }}" target="_blank" rel="noopener noreferrer"
                                class="text-sm text-blue-600 hover:underline">
                                Lihat
                            </a>

                            <button type="button"
                                onclick="openPaymentProofDelete('{{ $proof->id }}')"
                                class="text-sm text-red-600 hover:underline">
                                Hapus
                            </button>
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

    <div class="flex justify-end">
        <button type="button"
            onclick="openPaymentProofUpload('{{ $invoiceType }}', '{{ addslashes($invoiceKey) }}', {{ $manualAmount ? 'true' : 'false' }})"
            class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            <i class="fa-solid fa-upload"></i> Upload Bukti Pembayaran
        </button>
    </div>
</div>
