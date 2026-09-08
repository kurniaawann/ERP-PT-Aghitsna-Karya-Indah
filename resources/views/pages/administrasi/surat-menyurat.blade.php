{{-- =====================================================================
     Halaman: Surat Menyurat (konsolidasi submodul dalam satu halaman ber-tab)
     PT Aghitsna Karya Indah

     Tab yang tersedia (dari SuratMenyuratController):
     - document-receipt : Tanda Terima Dokumen
     - kwintansi        : Kwintansi
     - nota             : Nota
     - surat-jalan      : Surat Jalan
     - spk              : Surat Perintah Kerja

     Data aktif per tab disiapkan oleh controller submodul (indexData()).
     Hanya partial untuk tab yang aktif yang di-include.
     ===================================================================== --}}

@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Surat Menyurat')

@section('content')
    {{-- Tab Bar --}}
    <div class="bg-surface-base rounded-xl shadow-sm p-1.5 mb-4">
        <div class="flex flex-wrap gap-1.5 sm:grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            @foreach ($tabs as $key => $meta)
                <a href="{{ route('surat-menyurat.index', ['tab' => $key]) }}"
                    class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 border-2
                        {{ $tab === $key
                            ? 'bg-primary text-white border-primary shadow'
                            : 'bg-surface-base text-text-primary border-transparent hover:bg-primary-light hover:text-primary' }}">
                    <i class="fa-solid {{ $meta['icon'] }}"></i>
                    <span>{{ $meta['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Konten tab aktif --}}
    @if ($tab === 'document-receipt')
        @include('pages.administrasi.partials.document-receipt')
    @elseif ($tab === 'kwintansi')
        @include('pages.administrasi.partials.kwintansi')
    @elseif ($tab === 'nota')
        @include('pages.administrasi.partials.nota')
    @elseif ($tab === 'surat-jalan')
        @include('pages.administrasi.partials.surat-jalan')
    @elseif ($tab === 'spk')
        @include('pages.administrasi.partials.spk')
    @endif
@endsection