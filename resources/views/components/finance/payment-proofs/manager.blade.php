{{-- =====================================================================
     Komponen: Payment Proof Manager (gaya Filament)
     Dipakai di halaman Edit Invoice (Barang/Alumunium/Proyek, di dalam
     x-fi.section → bare) dan di modal Edit rekap (Rekap Proyek / Rekap
     Penjualan, dengan judul sendiri).
     Tujuan: Menampilkan daftar bukti pembayaran milik sebuah invoice/rekap,
             dengan tombol "Upload" (membuka modal upload terpisah) dan
             "Hapus" (membuka modal hapus terpisah).
     Alasan desain: form Edit membungkus seluruh isi. Untuk menghindari
     nested <form> (invalid html), upload & hapus dipindah ke modal terpisah
     yang dirender di luar form (lihat file upload-modal & delete-modal yang
     di-include di halaman modul).

     Parameter yang dipakai:
     - $proofs       : Collection bukti pembayaran (paymentProofs).
     - $invoiceType  : invoice_type ('proyek'|'alumunium'|'barang'|'recap'|'rekap_penjualan').
     - $invoiceKey   : Nilai invoice_number / id yang dikirim saat upload.
     - $manualAmount : bool, true bila nominal pembayaran diisi manual
                       (proyek/recap). Default false.
     - $bare         : bool, true = tanpa judul & bingkai (sudah di dalam
                       x-fi.section berjudul). Default false.
     ===================================================================== --}}
@php
    $proofs = collect($proofs ?? []);
    $manualAmount = $manualAmount ?? in_array($invoiceType ?? '', ['proyek', 'recap'], true);
    $bare = $bare ?? false;
@endphp

<div @class(['grid gap-y-4', 'mb-4 rounded-xl bg-white p-4 ring-1 ring-gray-950/10' => ! $bare])>
    @unless ($bare)
        <div>
            <p class="text-sm font-medium leading-6 text-gray-950">Bukti Pembayaran</p>
            <p class="text-sm text-gray-500">
                Kelola gambar bukti pembayaran. File akan diperkecil otomatis supaya tidak terlalu besar.
            </p>
        </div>
    @endunless

    @if ($proofs->count() > 0)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($proofs as $proof)
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/10">
                    <a href="{{ asset('storage/' . $proof->file_path) }}" data-file-preview data-preview-title="Bukti Pembayaran"
                        class="block bg-gray-50">
                        <img src="{{ asset('storage/' . $proof->file_path) }}" alt="Bukti pembayaran {{ $proof->file_name }}"
                            class="h-36 w-full object-cover" loading="lazy">
                    </a>
                    <div class="grid gap-y-3 p-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-950" title="{{ $proof->file_name }}">{{ $proof->file_name }}</p>
                            <p class="text-sm text-gray-500">
                                {{ optional($proof->payment_date ?? $proof->created_at)->format('d M Y') }}
                                @if ($proof->amount)
                                    · <span class="font-medium text-emerald-600">Rp {{ number_format($proof->amount, 0, ',', '.') }}</span>
                                @endif
                            </p>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <a href="{{ asset('storage/' . $proof->file_path) }}" data-file-preview data-preview-title="Bukti Pembayaran"
                                class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary-hover">
                                <x-fi.icon name="eye" class="h-4 w-4" /> Lihat
                            </a>

                            <x-fi.link color="danger" icon="trash" onclick="openPaymentProofDelete('{{ $proof->id }}')">
                                Hapus
                            </x-fi.link>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center rounded-xl border border-dashed border-gray-300 px-6 py-8 text-center">
            <div class="mb-3 rounded-full bg-gray-100 p-3">
                <x-fi.icon name="photo" class="h-6 w-6 text-gray-500" />
            </div>
            <p class="text-sm font-medium text-gray-950">Belum ada bukti pembayaran</p>
            <p class="mt-1 text-sm text-gray-500">File akan diperkecil otomatis supaya tidak terlalu besar.</p>
        </div>
    @endif

    <div>
        <x-fi.button color="gray" icon="arrow-up-tray"
            onclick="openPaymentProofUpload('{{ $invoiceType }}', '{{ addslashes($invoiceKey) }}', {{ $manualAmount ? 'true' : 'false' }})">
            Upload Bukti Pembayaran
        </x-fi.button>
    </div>
</div>
