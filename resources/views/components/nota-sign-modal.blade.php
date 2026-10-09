{{--
    Modal Lengkapi Tanda Tangan Nota (wajib sebelum download)

    Revisi klien (Super Admin & Admin): nota punya DUA tanda tangan seperti di
    PDF — Penerima/Tanda Terima (kiri) dan Hormat Kami (kanan, Data
    Penandatangan). Keduanya opsional saat nota dibuat, tetapi nota yang belum
    lengkap WAJIB dilengkapi sebelum di-download (tombol PDF, Export Dipilih,
    Export Semua, link nota di Reimbursement).

    Tampilan sama persis dengan form Tambah Nota: setiap nota dirender dari
    <template> berisi partial components.administrasi.nota.signature-fields
    (satu kolom: Penerima/Tanda Terima, Hormat Kami, Divisi). Bagian yang sudah
    terisi ditampilkan terkunci; yang kosong wajib diisi. Rincian dari POST
    nota.administrasi.unsigned, simpan ke POST nota.administrasi.sign —
    lihat resources/js/shared/nota-sign.js.

    Data: $executives, $divisions (App\View\Components\NotaSignModal).
--}}
<x-modal id="notaSignModal" title="Lengkapi Tanda Tangan Nota" onConfirm="confirmNotaSign()" buttonText="Simpan & Print" size="xl">
    <div id="nota-sign-config" class="hidden"
        data-sign-url="{{ route('nota.administrasi.sign') }}"
        data-unsigned-url="{{ route('nota.administrasi.unsigned') }}"
        data-csrf="{{ csrf_token() }}"></div>

    <div class="p-3 bg-warning-light border border-warning rounded-lg text-sm text-warning">
        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
        <span class="nota-sign-message">Nota ini belum lengkap tanda tangannya.</span>
        Nota wajib memiliki 2 tanda tangan &mdash; <strong>Penerima/Tanda Terima</strong> dan <strong>Hormat Kami</strong>
        &mdash; sebelum di-download.
    </div>

    @if ($executives->isEmpty())
        <div class="p-3 bg-error-light border border-error rounded-lg text-sm text-error">
            Belum ada Data Penandatangan. Tambahkan dulu di menu
            <a href="{{ route('executive.index') }}" class="font-semibold underline">Data Penandatangan</a>.
        </div>
    @endif

    <p class="nota-sign-loading text-sm text-text-secondary">
        <i class="fa-solid fa-spinner fa-spin mr-1"></i> Memuat data nota...
    </p>

    {{-- Bantuan isi cepat (hanya bila lebih dari satu nota) --}}
    <div class="nota-sign-actions hidden flex flex-wrap justify-end gap-x-4 gap-y-1 text-xs">
        <button type="button" class="nota-sign-copy-kepada text-primary hover:underline">Penerima kosong = "Kepada"</button>
        <button type="button" class="nota-sign-copy-signer text-primary hover:underline">Hormat Kami kosong = nota pertama</button>
    </div>

    <div class="nota-sign-rows"></div>

    <template id="nota-sign-row-template">
        @include('components.administrasi.nota.signature-fields', [
            'idPrefix' => 'notaSign__ROW__',
            'receiverLabel' => 'Penerima',
            'showHint' => false,
        ])
    </template>

    <p class="nota-sign-error hidden text-sm text-error"></p>
</x-modal>
