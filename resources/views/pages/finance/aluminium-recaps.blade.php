{{-- =====================================================================
     Halaman: Rekap Alumunium
     Tujuan: Menampilkan rekap/ringkasan invoice alumunium dengan summary
             cards, filter bulan/tahun & pencarian, serta export ke
             Excel/PDF untuk periode yang sedang aktif.
     Data dari RecapAlumuniumController@index:
     - $invoices   : Paginator InvoiceAlumunium dari
                     RecapAlumuniumService::getPaginatedInvoices(),
                     difilter oleh request('search'), ('month'), ('year').
     - $totals     : Objek ringkasan hasil RecapAlumuniumService::buildTotals()
                     (total qty, nominal, dsb) untuk seluruh data periode.
     - $periodTitle: Label periode (bulan/tahun) yang sedang difilter
                     (dipakai di export & summary cards).
     Komponen yang di-include:
     - x-filters.month-filter / year-filter / search-input : toolbar filter & pencarian
     - x-buttons.print-dropdown                            : tombol export Excel/PDF
     - x-finance.aluminium-recaps.summary-cards            : kartu ringkasan rekap
     - x-finance.aluminium-recaps.table                    : tabel rekap invoice
     - x-finance.aluminium-invoices.detail-modal           : modal detail per invoice
     - x-pagination                                        : navigasi halaman
     JS: Tidak ada module khusus (hanya modal bawaan).
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Rekap Alumunium')

@section('content')
    {{-- ==================== Kontainer Utama Halaman ==================== --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Rekap Alumunium</h1>

        {{-- ==================== Section: Toolbar Filter & Aksi ==================== --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('recap-alumunium.index') }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari invoice, penerima, atau proyek..." responsive="custom" />
            </form>

            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">
                    <x-buttons.print-dropdown
                        :excelRoute="route('recap-alumunium.export.excel')"
                        :pdfRoute="route('recap-alumunium.export.pdf')"
                        :queryParams="[
                            'search' => request('search'),
                            'month' => request('month'),
                            'year' => request('year'),
                        ]"
                        responsive="custom"
                    />
                </div>
            </div>
        </div>

        <x-finance.aluminium-recaps.summary-cards :totals="$totals" />

        {{-- Tabel rekap invoice; menerima juga $totals untuk menampilkan
             subtotal/kolom jumlah di akhir tabel. --}}
        <x-finance.aluminium-recaps.table :invoices="$invoices" :totals="$totals" />
    </div>

    {{-- ==================== Section: Pagination ==================== --}}
    <x-pagination :paginator="$invoices" />

    {{-- ==================== Section: Modals (Detail per Invoice) ==================== --}}
    {{-- Satu modal detail dibuat untuk tiap invoice pada halaman ini,
         sehingga klik "detail" langsung membuka data yang sesuai. --}}
    @foreach ($invoices as $invoice)
        <x-finance.aluminium-invoices.detail-modal :invoice="$invoice" />
    @endforeach
@endsection
