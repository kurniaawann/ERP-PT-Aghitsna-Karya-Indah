@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Rekap Proyek')

@section('content')
    {{-- ==================== Container Utama ==================== --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Rekap Proyek</h1>

        {{-- ==================== Toolbar Filter & Aksi ==================== --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('recap-proyek.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" />
                <x-filters.year-filter :value="request('year')" />
                <x-filters.search-input :value="request('search')" placeholder="Cari invoice, penerima, atau proyek..." />
            </form>

            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.print-dropdown
                        :excelRoute="route('recap-proyek.export.excel')"
                        :pdfRoute="route('recap-proyek.export.pdf')"
                        :queryParams="[
                            'search' => request('search'),
                            'month' => request('month'),
                            'year' => request('year'),
                        ]"
                    />
                </div>
            </div>
        </div>

        {{-- ==================== Ringkasan Rekap ==================== --}}
        <x-finance.project-recaps.summary-cards :totals="$totals" />

        {{-- ==================== Tabel Rekap Proyek ==================== --}}
        <x-finance.project-recaps.table :invoices="$invoices" :totals="$totals" />
    </div>

    {{-- ==================== Pagination ==================== --}}
    <x-pagination :paginator="$invoices" />

    {{-- ==================== Modal Detail Invoice ==================== --}}
    @foreach ($invoices as $invoice)
        @include('components.finance.project-invoices.detail-modal', ['invoice' => $invoice])
    @endforeach

    {{-- ==================== Script Print Dropdown ==================== --}}
    @include('partials.shared.print-dropdown-script')
@endsection
