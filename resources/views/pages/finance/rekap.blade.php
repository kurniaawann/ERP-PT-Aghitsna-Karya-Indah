{{-- =====================================================================
     Halaman: Rekap (konsolidasi rekap dalam satu halaman ber-tab)
     PT Aghitsna Karya Indah

     Tab yang tersedia (dari RekapController, khusus Super Admin):
     - sales        : Rekap Penjualan
     - aluminium    : Rekap Alumunium
     - proyek       : Rekap Proyek
     - pengeluaran  : Rekap Pengeluaran

     Data aktif per tab disiapkan oleh controller submodul (indexData()).
     Hanya partial untuk tab yang aktif yang di-include. Partial yang sama
     dipakai halaman rekap masing-masing; di sini $tabs tersedia sehingga
     partial menampilkan judul "Rekap", tab (x-finance.recap-tabs) di antara
     header dan tabel, dan filter yang mengarah ke route('rekap.index') + tab.
     ===================================================================== --}}

@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Rekap')

@section('content')
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
