{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN LINK NOTA SUMBER REIMBURSE
     Menampilkan "Nota: NTP-012/AKI/26" untuk pengajuan otomatis dari Nota.
     Link membuka pratinjau PDF nota di dalam halaman (data-preview, tanpa
     tab baru). Route PDF nota hanya bisa diakses Super Admin & Admin; role
     lain melihat kode nota sebagai teks biasa.
     Parameter: $reimburse (Reimburse dengan id_nota terisi)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if (Auth::user()->hasRole(['superadmin', 'admin']))
    <a href="{{ route('nota.administrasi.export.pdf.single', $reimburse->id_nota) }}" data-preview
        data-preview-title="Nota {{ $reimburse->id_nota }}"
        class="inline-flex items-center gap-1 text-xs text-primary hover:text-primary-hover underline whitespace-nowrap"
        title="Lihat PDF nota">
        <i class="fa-solid fa-file-pdf"></i>
        Nota: {{ $reimburse->id_nota }}
    </a>
@else
    <span class="text-xs text-text-secondary whitespace-nowrap">Nota: {{ $reimburse->id_nota }}</span>
@endif
