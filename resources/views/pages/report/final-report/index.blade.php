{{-- =====================================================================
     Halaman: Laporan Akhir (Final Report)
     Tujuan: Menyatukan Laporan Stok, Laporan Penjualan, dan Laporan
             Pengeluaran dalam satu halaman ber-tab. Header menampilkan
             tombol "Print Laporan" tunggal yang menyesuaikan tab aktif,
             dan tab memakai segmented control yang besar & user-friendly.
     Data dari FinalReportController@index:
     - $allowedTabs, $tab, $tabLabels + data laporan aktif per tab.
     ===================================================================== --}}
@php
    $tabMeta = [
        'stock' => [
            'icon' => 'fa-boxes-stacked',
            'label' => 'Laporan Stok',
            'desc'  => 'Rekap stok barang masuk, keluar, retur & stok akhir',
        ],
        'sales' => [
            'icon' => 'fa-money-bill-trend-up',
            'label' => 'Laporan Penjualan',
            'desc'  => 'Rekap pendapatan dari penjualan per periode',
        ],
        'expense' => [
            'icon' => 'fa-arrow-trend-down',
            'label' => 'Laporan Pengeluaran',
            'desc'  => 'Rekap pengeluaran dan biaya operasional',
        ],
        'cement' => [
            'icon' => 'fa-cubes',
            'label' => 'Laporan Semen',
            'desc'  => 'Rekap data DO Semen & Data Semen',
        ],
    ];

    $exportRoutes = [
        'stock' => [
            'pdf'   => route('stock-report.export.pdf'),
            'excel' => route('stock-report.export.excel'),
        ],
        'sales' => [
            'pdf'   => route('report.sales.export.pdf'),
            'excel' => route('report.sales.export.excel'),
        ],
        'expense' => [
            'pdf'   => route('report.expense.export.pdf'),
            'excel' => route('report.expense.export.excel'),
        ],
        'cement' => [
            'pdf'   => route('report.cement.export.pdf'),
            'excel' => route('report.cement.export.excel'),
        ],
    ];

    // Filter aktif yang dibawa ke export (pindahkan param 'tab').
    $exportQuery = request()->except(['tab']);

    $activeMeta = $tabMeta[$tab];
    $activeExport = $exportRoutes[$tab];
@endphp

@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Laporan Akhir')

@section('content')
    <div class="space-y-6">

        {{-- ==================== Header Halaman ==================== --}}
        <div class="bg-gradient-to-r from-primary to-primary-hover rounded-2xl shadow p-6 sm:p-8 text-white">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-chart-column text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold">Laporan Akhir</h2>
                        <p class="text-white/80 text-sm mt-1">
                            <i class="fa-solid {{ $activeMeta['icon'] }} mr-1"></i>
                            Kelola laporan <strong class="text-white">{{ $activeMeta['label'] }}</strong>
                        </p>
                    </div>
                </div>

                {{-- Tombol Print Laporan: menyesuaikan tab aktif --}}
                <div class="w-full sm:w-56 lg:w-auto">
                    <x-buttons.print-dropdown
                        :pdfRoute="$activeExport['pdf']"
                        :excelRoute="$activeExport['excel']"
                        :queryParams="$exportQuery"
                        size="sm"
                    />
                </div>
            </div>
        </div>

        {{-- ==================== Tab Pilihan Jenis Laporan ==================== --}}
        <div class="bg-surface-base rounded-xl shadow-sm p-1.5">
            <div class="flex flex-wrap gap-1.5 sm:grid sm:grid-cols-4">
                @foreach ($allowedTabs as $t)
                    <a href="{{ route('report.final', ['tab' => $t]) }}"
                        class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 border-2
                            {{ $tab === $t
                                ? 'bg-primary text-white border-primary shadow'
                                : 'bg-surface-base text-text-primary border-transparent hover:bg-primary-light hover:text-primary' }}">
                        <i class="fa-solid {{ $tabMeta[$t]['icon'] }}"></i>
                        <span>{{ $tabMeta[$t]['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ==================== Konten Laporan Aktif ==================== --}}
        @if ($tab === 'stock')
            @include('pages.report.final-report.partials.stock')
        @elseif ($tab === 'expense')
            @include('pages.report.final-report.partials.expense')
        @elseif ($tab === 'cement')
            @include('pages.report.final-report.partials.cement')
        @else
            @include('pages.report.final-report.partials.sales')
        @endif

    </div>
@endsection
