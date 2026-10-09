{{--
    Tanda Tangan Nota — 2 tanda tangan, urutan sama dengan PDF, disusun satu
    kolom (field selebar penuh, bertumpuk ke bawah):
    1. Penerima (sewa/jual) / Tanda Terima (proyek) — kiri, nama diketik.
    2. Hormat Kami — kanan, dipilih dari Data Penandatangan.
    3. Divisi Hormat Kami — opsional.
    Keduanya opsional saat nota dibuat/diedit, tetapi WAJIB lengkap sebelum
    nota di-download.

    Dipakai di form Tambah/Edit Nota DAN modal "Lengkapi Tanda Tangan Nota"
    (components/nota-sign-modal, sebagai <template> per nota) agar tampilannya
    selalu sama.

    Variabel:
    - $idPrefix      : prefix id field (mis. "addModal", "editModal-NTA-001/AKI/26")
    - $receiverLabel : "Penerima" | "Tanda Terima"
    - $nota          : Nota (modal edit) atau null
    - $title         : judul kotak (opsional)
    - $showHint      : tampilkan keterangan opsional/wajib (default true)
--}}
@php
    $nota = $nota ?? null;
@endphp
<div class="nota-signature-fields mb-3 p-3 border border-border rounded-lg bg-surface-secondary">
    <p class="text-sm font-semibold text-text-primary">
        <i class="fa-solid fa-signature text-primary mr-1"></i>
        <span class="nota-signature-title">{{ $title ?? 'Tanda Tangan Nota (2)' }}</span>
    </p>
    @if ($showHint ?? true)
        <p class="text-xs text-text-secondary">Opsional saat membuat nota, tetapi kedua tanda tangan wajib lengkap sebelum
            nota di-download.</p>
    @endif

    {{-- -mb-3: meniadakan margin bawah field terakhir agar jarak bawah kotak = p-3 --}}
    <div class="mt-3 -mb-3">
        {{-- 1. Penerima / Tanda Terima (kiri) --}}
        <div class="mb-3">
            <label class="block text-text-primary mb-1" for="{{ $idPrefix }}-penerima">
                <span class="nota-signature-receiver-label">{{ $receiverLabel }}</span>
                <span class="text-xs text-text-label">(kiri &mdash; ketik nama)</span>
            </label>
            <input type="text" name="penerima" id="{{ $idPrefix }}-penerima"
                class="w-full border rounded p-2 bg-surface-base text-text-input focus:border-primary focus:ring-2 focus:ring-primary-light"
                placeholder="Ketik nama..." maxlength="255" value="{{ $nota?->penerima }}"
                oninput="this.setCustomValidity('')">
        </div>

        {{-- 2. Hormat Kami (kanan) --}}
        <x-forms.searchable-select
            name="petinggi_id"
            id="{{ $idPrefix }}-petinggi_id"
            label="Hormat Kami (kanan — pilih penanda tangan)"
            placeholder="Cari penanda tangan..."
            :options="$executives->map(fn($e) => ['value' => (string) $e->id, 'label' => $e->name . ($e->position ? ' — ' . $e->position : '')])->values()"
            selected="{{ $nota?->penandatangan['id'] ?? '' }}" />

        {{-- 3. Divisi Hormat Kami (opsional) --}}
        <x-forms.searchable-select
            name="divisi"
            id="{{ $idPrefix }}-divisi"
            label="Divisi Hormat Kami (opsional)"
            placeholder="Cari divisi..."
            :options="$divisions->map(fn($d) => ['value' => $d->name, 'label' => $d->name])->values()"
            selected="{{ $nota?->penandatangan['divisi'] ?? '' }}" />
    </div>
</div>
