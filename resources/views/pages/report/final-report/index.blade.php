{{-- =====================================================================
     Halaman: Laporan Akhir (Final Report)
     Tujuan: Menyatukan Laporan Stok, Laporan Penjualan, dan Laporan
             Pengeluaran dalam satu halaman ber-tab. User memilih jenis
             laporan via tab; hanya laporan aktif yang dirender beserta
             filter dan dropdown export-nya masing-masing (export terpisah).
     Data dari FinalReportController@index:
     - $allowedTabs   : daftar tab yang boleh diakses user (aturan sidebar)
     - $tab           : tab aktif (stock|sales|expense)
     - $tabLabels     : label tampilan per tab
     - data laporan aktif sesuai tab (lihat partial masing-masing)
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Laporan Akhir')

@section('content')
    <div class="space-y-6">

        {{-- ==================== Header Halaman ==================== --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text-primary mb-1">📊 Laporan Akhir</h2>
                <p class="text-text-secondary">Pilih jenis laporan di bawah, lalu atur filter dan export sesuai kebutuhan.</p>
            </div>
        </div>

        {{-- ==================== Tab Pilihan Jenis Laporan ==================== --}}
        <div class="flex flex-wrap gap-2">
            @foreach ($allowedTabs as $t)
                <a href="{{ route('report.final', ['tab' => $t]) }}"
                    class="px-4 py-2 rounded-lg transition-colors duration-200 text-sm font-medium
                        {{ $tab === $t ? 'bg-primary text-white' : 'bg-surface-base text-text-primary border border-border-strong hover:bg-primary-light hover:text-primary' }}">
                    {{ $tabLabels[$t] }}
                </a>
            @endforeach
        </div>

        {{-- ==================== Konten Laporan Aktif ==================== --}}
        @if ($tab === 'stock')
            @include('pages.report.final-report.partials.stock')
        @elseif ($tab === 'expense')
            @include('pages.report.final-report.partials.expense')
        @else
            @include('pages.report.final-report.partials.sales')
        @endif

    </div>
@endsection
