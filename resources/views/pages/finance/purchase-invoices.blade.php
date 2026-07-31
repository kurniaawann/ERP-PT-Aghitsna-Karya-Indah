@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Faktur Pembelian')

@section('content')
    {{-- ==================== Header Faktur Pembelian ==================== --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Faktur Pembelian</h1>

        {{-- ==================== Toolbar Filter & Aksi ==================== --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian dan Filter --}}
            <form method="GET" action="{{ route('purchase-invoice.index') }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">

                {{-- Filter Bulan --}}
                <x-filters.month-filter :value="request('month')" responsive="custom" />

                {{-- Filter Tahun --}}
                <x-filters.year-filter :value="request('year')" responsive="custom" />

                {{-- Search Input --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari material, barang, atau NPWP..." responsive="custom" />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">
                    {{-- Tombol Print/Export --}}
                    <x-buttons.print-dropdown :excelRoute="route('purchase-invoice.export-excel')" :pdfRoute="route('purchase-invoice.export-pdf')" :queryParams="[
                        'search' => request('search'),
                        'month' => request('month'),
                        'year' => request('year'),
                    ]" responsive="custom" />

                    {{-- Tombol Hapus --}}
                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />

                    {{-- Tombol Tambah --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Faktur" responsive="custom" />
                </div>
            </div>
        </div>

        {{-- ==================== Tabel Data ==================== --}}
        <x-finance.purchase-invoices.table :invoices="$invoices" />
    </div>

    {{-- ==================== Pagination ==================== --}}
    <x-pagination :paginator="$invoices" />

    {{-- ==================== Modal Tambah Faktur ==================== --}}
    <x-finance.purchase-invoices.add-modal />

    {{-- ==================== Modal Edit untuk setiap faktur ==================== --}}
    @foreach ($invoices as $invoice)
        <x-finance.purchase-invoices.edit-modal :invoice="$invoice" />
    @endforeach

    {{-- ==================== Modal Konfirmasi Bulk Delete ==================== --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- ==================== JavaScript ==================== --}}
    @vite('resources/js/pages/finance/purchase-invoices/index.js')
@endsection
