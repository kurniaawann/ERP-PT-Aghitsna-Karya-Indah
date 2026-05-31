@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Invoice Item')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Invoice Item</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('item-invoice.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" />
                <x-filters.year-filter :value="request('year')" />
                <x-filters.search-input :value="request('search')" placeholder="Cari invoice, penerima, atau keterangan..." />
            </form>

            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.print-dropdown :excelRoute="route('item-invoice.export.excel')" :pdfRoute="route('item-invoice.export.pdf')" :queryParams="[
                        'search' => request('search'),
                        'month' => request('month'),
                        'year' => request('year'),
                    ]" />

                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Invoice" />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-5">
            <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
                <p class="text-xs uppercase tracking-wide text-text-secondary">Total Invoice</p>
                <p class="text-xl font-bold text-text-primary mt-1">Rp
                    {{ number_format($totals->total_invoice ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
                <p class="text-xs uppercase tracking-wide text-text-secondary">Jumlah Invoice</p>
                <p class="text-xl font-bold text-success mt-1">{{ $totals->invoice_count ?? 0 }} invoice</p>
            </div>
            <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
                <p class="text-xs uppercase tracking-wide text-text-secondary">Lunas</p>
                <p class="text-xl font-bold text-success mt-1">{{ $totals->paid_count ?? 0 }} invoice</p>
            </div>
        </div>

        @include('components.finance.barang.table', ['invoices' => $invoices])

        <x-pagination :paginator="$invoices" />
    </div>

    @include('components.finance.barang.add-modal', ['items' => $items])

    @foreach ($invoices as $invoice)
        @include('components.finance.barang.edit-modal', ['invoice' => $invoice, 'items' => $items])
        @include('components.finance.barang.detail-modal', ['invoice' => $invoice])
    @endforeach

    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    @include('partials.finance.barang-scripts', ['items' => $items])
@endsection
