@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Faktur Pembelian')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Faktur Pembelian</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian dan Filter --}}
            <form method="GET" action="{{ route('purchase-invoice.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">

                {{-- Filter Bulan --}}
                <x-filters.month-filter :value="request('month')" />

                {{-- Filter Tahun --}}
                <x-filters.year-filter :value="request('year')" />

                {{-- Search Input --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari material, barang, atau NPWP..." />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.print-dropdown :excelRoute="route('purchase-invoice.export-excel')" :pdfRoute="route('purchase-invoice.export-pdf')" :queryParams="[
                        'search' => request('search'),
                        'month' => request('month'),
                        'year' => request('year'),
                    ]" />

                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Faktur" />
                </div>
            </div>
        </div>

        {{-- Table Component --}}
        @include('components.finance.pembelian.table', ['invoices' => $invoices])

        {{-- Pagination --}}
        <x-pagination :paginator="$invoices" />
    </div>

    {{-- Modal Tambah Faktur --}}
    @include('components.finance.pembelian.add-modal')

    {{-- Modal Edit & Detail untuk setiap faktur --}}
    @foreach ($invoices as $invoice)
        @include('components.finance.pembelian.edit-modal', ['invoice' => $invoice])
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- JavaScript --}}
    @include('partials.finance.purchase-invoice-scripts')
    @include('partials.shared.print-dropdown-script')
@endsection
