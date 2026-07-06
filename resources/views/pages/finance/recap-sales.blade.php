@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Rekap Penjualan')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Rekap Penjualan</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian dan Filter --}}
            <form method="GET" action="{{ route('recap-sales.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">

                {{-- Filter Bulan --}}
                <x-filters.month-filter :value="request('month')" />

                {{-- Filter Tahun --}}
                <x-filters.year-filter :value="request('year')" />

                {{-- Search Input --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari proyek..." />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.print-dropdown :excelRoute="route('recap-sales.export.excel')" :pdfRoute="route('recap-sales.export.pdf')" :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]" />

                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Laporan" />
                </div>
            </div>
        </div>

        {{-- Table Component --}}
        @include('components.recap-sales.table', [
            'salesRecaps' => $salesRecaps,
            'grandTotals' => $grandTotals,
        ])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$salesRecaps" />

    {{-- Modal Tambah --}}
    @include('components.recap-sales.add-modal', ['items' => $items])

    {{-- Modal Edit dan Status hanya untuk sale yang belum lunas --}}
    @foreach ($salesRecaps as $sale)
        @if (!$sale->isLunas())
            @include('components.recap-sales.edit-modal', ['sale' => $sale, 'items' => $items])
            @include('components.recap-sales.status-modal', ['sale' => $sale])
        @endif
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah Anda yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- JavaScript --}}
    @include('partials.finance.recap-sales-scripts')
    {{-- @include('partials.shared.print-dropdown-script') --}}
@endsection
