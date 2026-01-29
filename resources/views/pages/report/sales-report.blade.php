@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Laporan Penjualan')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Laporan Penjualan</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian dan Filter --}}
            <form method="GET" action="{{ route('sales-report.index') }}"
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
                    <x-buttons.print-dropdown :excelRoute="route('sales-report.export.excel')" :pdfRoute="route('sales-report.export.pdf')" :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]" />

                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Laporan" />
                </div>
            </div>
        </div>

        {{-- Table Component --}}
        @include('components.sales-report.table', [
            'salesReports' => $salesReports,
            'grandTotals' => $grandTotals,
        ])

        {{-- Pagination --}}
        <x-pagination :paginator="$salesReports" />
    </div>

    {{-- Modal Tambah --}}
    @include('components.sales-report.add-modal', ['items' => $items])

    {{-- Modal Edit dan Status hanya untuk sale yang belum lunas --}}
    @foreach ($salesReports as $sale)
        @if (!$sale->isLunas())
            @include('components.sales-report.edit-modal', ['sale' => $sale, 'items' => $items])
            @include('components.sales-report.status-modal', ['sale' => $sale])
        @endif
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah Anda yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- JavaScript --}}
    @include('partials.report.sales-report-scripts')
@endsection
