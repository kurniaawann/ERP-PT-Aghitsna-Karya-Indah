{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN LINK NOTA SUMBER REIMBURSE
     Menampilkan "Nota: NTP-012/AKI/26" untuk pengajuan otomatis dari Nota.
     Link membuka pratinjau PDF nota di dalam halaman (data-preview, tanpa
     tab baru). Route PDF nota hanya bisa diakses Super Admin & Admin; role
     lain melihat kode nota sebagai teks biasa.
     Nota milik user login yang tanda tangannya belum lengkap → data-nota-sign
     (modal Lengkapi Tanda Tangan Nota wajib sebelum PDF dibuka).
     Parameter: $reimburse (Reimburse dengan id_nota terisi)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@php
    // Nota milik user login yang tanda tangannya belum lengkap (Penerima &
    // Hormat Kami) wajib dilengkapi dulu sebelum dibuka/di-download.
    $notaNeedsSign = $reimburse->nota
        && (string) $reimburse->nota->created_by === (string) Auth::id()
        && ! $reimburse->nota->isSigned();
@endphp
@if (Auth::user()->hasRole(['superadmin', 'admin']))
    @if ($notaNeedsSign)
        @once
            @push('modals')
                <x-nota-sign-modal />
            @endpush
        @endonce
    @endif
    <a href="{{ route('nota.administrasi.export.pdf.single', $reimburse->id_nota) }}" data-preview
        data-preview-title="Nota {{ $reimburse->id_nota }}"
        @if ($notaNeedsSign) data-nota-sign="{{ $reimburse->id_nota }}" @endif
        class="inline-flex items-center gap-1 whitespace-nowrap text-xs font-medium text-primary hover:underline"
        title="Lihat PDF nota">
        <x-fi.icon name="document-text" class="h-4 w-4" />
        Nota: {{ $reimburse->id_nota }}
    </a>
@else
    <span class="whitespace-nowrap text-xs text-gray-500">Nota: {{ $reimburse->id_nota }}</span>
@endif
