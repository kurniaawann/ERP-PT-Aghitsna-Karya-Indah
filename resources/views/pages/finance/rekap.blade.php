{{-- =====================================================================
     Halaman: Rekap (konsolidasi rekap dalam satu halaman ber-tab)
     PT Aghitsna Karya Indah

     Tab yang tersedia (dari RekapController, khusus Super Admin):
     - sales        : Rekap Penjualan
     - aluminium    : Rekap Alumunium
     - proyek       : Rekap Proyek
     - pengeluaran  : Rekap Pengeluaran

     Data aktif per tab disiapkan oleh controller submodul (indexData()).
     Hanya partial untuk tab yang aktif yang di-include.
     ===================================================================== --}}

@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Rekap')

@section('content')
    {{-- Tab Bar --}}
    <div class="bg-surface-base rounded-xl shadow-sm p-1.5 mb-4">
        <div class="flex flex-wrap gap-1.5 sm:grid sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($tabs as $key => $meta)
                <a href="{{ route('rekap.index', ['tab' => $key]) }}"
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
    @if ($tab === 'sales')
        @include('pages.finance.partials.rekap-sales')
    @elseif ($tab === 'aluminium')
        @include('pages.finance.partials.rekap-aluminium')
    @elseif ($tab === 'proyek')
        @include('pages.finance.partials.rekap-proyek')
    @elseif ($tab === 'pengeluaran')
        @include('pages.finance.partials.rekap-pengeluaran')
    @endif
@endsection