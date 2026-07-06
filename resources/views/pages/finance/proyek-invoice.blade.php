@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - ' . (auth()->user()->isAdmin() ? 'Invoice' : 'Invoice Proyek'))

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">{{ auth()->user()->isAdmin() ? 'Invoice' : 'Invoice Proyek' }}</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('proyek-invoice.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" />
                <x-filters.year-filter :value="request('year')" />
                <x-filters.search-input :value="request('search')" placeholder="Cari no invoice atau kepada..." />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Invoice" />
                </div>
            </div>
        </div>

        {{-- Table Component --}}
        @include('components.finance.proyek.table', ['invoices' => $invoices])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$invoices" />

    {{-- Modal Tambah Invoice --}}
    @include('components.finance.proyek.add-modal')

    {{-- Modal Edit & Detail untuk setiap invoice --}}
    @foreach ($invoices as $invoice)
        @include('components.finance.proyek.edit-modal', ['invoice' => $invoice])
        @include('components.finance.proyek.detail-modal', ['invoice' => $invoice])
        @include('components.finance.proyek.delete-modal', ['invoice' => $invoice])
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- JavaScript --}}
    @include('partials.finance.proyek-scripts')
    @include('partials.shared.print-dropdown-script')
@endsection
