{{-- =====================================================================
     Halaman: Laporan Akhir (Final Report)
     Tujuan: Menyatukan Laporan Stok, Penjualan, Pengeluaran (Kas Kantor),
             Semen, Rekap Proyek, dan Keuangan Proyek dalam satu halaman
             ber-tab (gaya Filament). Header menampilkan satu tombol Export
             yang menyesuaikan tab aktif (tab Rekap Proyek & Keuangan Proyek
             tidak punya export global — export per proyek ada di menu Aksi).
     Data dari FinalReportController@index:
     - $allowedTabs, $tab, $tabLabels + data laporan aktif per tab.
     ===================================================================== --}}
@php
    $tabMeta = [
        'stock' => ['icon' => 'archive-box', 'label' => 'Laporan Stok'],
        'sales' => ['icon' => 'chart-bar', 'label' => 'Laporan Penjualan'],
        'expense' => ['icon' => 'wallet', 'label' => 'Laporan Pengeluaran'],
        'cement' => ['icon' => 'truck', 'label' => 'Laporan Semen'],
        'recap' => ['icon' => 'clipboard-document-list', 'label' => 'Rekap Proyek'],
        'financial' => ['icon' => 'document-currency-dollar', 'label' => 'Keuangan Proyek'],
    ];

    // Role admin: "Laporan Pengeluaran" disebut "Kas Kantor" (revisi klien)
    if (auth()->user()?->isAdmin()) {
        $tabMeta['expense']['label'] = 'Kas Kantor';
    }

    $exportRoutes = [
        'stock' => [
            'pdf' => route('stock-report.export.pdf'),
            'excel' => route('stock-report.export.excel'),
        ],
        'sales' => [
            'pdf' => route('report.sales.export.pdf'),
            'excel' => route('report.sales.export.excel'),
        ],
        'expense' => [
            'pdf' => route('report.expense.export.pdf'),
            'excel' => route('report.expense.export.excel'),
        ],
        'cement' => [
            'pdf' => route('report.cement.export.pdf'),
            'excel' => route('report.cement.export.excel'),
        ],
    ];

    // Filter aktif yang dibawa ke export (pindahkan param 'tab').
    $exportQuery = request()->except(['tab']);

    // Tab Rekap Proyek & Keuangan Proyek tidak punya export global
    // (export dilakukan per proyek pada baris tabel).
    $activeExport = $exportRoutes[$tab] ?? null;

    $tabItems = collect($allowedTabs)->mapWithKeys(fn ($t) => [$t => [
        'label' => $tabMeta[$t]['label'],
        'icon' => $tabMeta[$t]['icon'],
        'url' => route('report.final', ['tab' => $t]),
    ]])->all();
@endphp

@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Laporan Akhir')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Export (menyesuaikan tab aktif) --}}
        <x-fi.page-header heading="Laporan Akhir"
            :breadcrumbs="['Report' => null, 'Laporan Akhir' => route('report.final'), $tabMeta[$tab]['label'] => null]">
            @if ($activeExport)
                <x-slot:actions>
                    {{-- Laporan Pengeluaran / Kas Kantor: wajib pilih penandatangan --}}
                    <x-fi.export-dropdown :pdfRoute="$activeExport['pdf']" :excelRoute="$activeExport['excel']"
                        :queryParams="$exportQuery"
                        :requireSigner="$tab === 'expense' && app(\App\Services\Report\ReportSignerService::class)->isRequired()" />
                </x-slot:actions>
            @endif
        </x-fi.page-header>

        {{-- SECTION: Tab Jenis Laporan (hanya tab yang diizinkan untuk role user) --}}
        <x-fi.tabs :tabs="$tabItems" :active="$tab" />

        {{-- SECTION: Konten Laporan Aktif --}}
        @if ($tab === 'stock')
            @include('pages.report.final-report.partials.stock')
        @elseif ($tab === 'expense')
            @include('pages.report.final-report.partials.expense')
        @elseif ($tab === 'cement')
            @include('pages.report.final-report.partials.cement')
        @elseif ($tab === 'recap')
            @include('pages.report.final-report.partials.recap')
        @elseif ($tab === 'financial')
            @include('pages.report.final-report.partials.financial')
        @else
            @include('pages.report.final-report.partials.sales')
        @endif

    </div>
@endsection
