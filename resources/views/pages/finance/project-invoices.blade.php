@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - ' . (auth()->user()->isAdmin() ? 'Invoice' : 'Invoice Proyek'))

@section('content')
    {{-- Header Invoice Proyek --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">{{ auth()->user()->isAdmin() ? 'Invoice' : 'Invoice Proyek' }}</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('proyek-invoice.index') }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari no invoice atau kepada..." responsive="custom" />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Invoice" responsive="custom" />
                </div>
            </div>
        </div>

        {{-- Table Component --}}
        <x-finance.project-invoices.table :invoices="$invoices" />
    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$invoices" />

    {{-- Modal Tambah Invoice --}}
    <x-finance.project-invoices.add-modal :paymentAccounts="$paymentAccounts" />

    {{-- Modal Edit & Detail untuk setiap invoice --}}
    @foreach ($invoices as $invoice)
        <x-finance.project-invoices.edit-modal :invoice="$invoice" :paymentAccounts="$paymentAccounts" />
        <x-finance.project-invoices.detail-modal :invoice="$invoice" />
        <x-finance.project-invoices.delete-modal :invoice="$invoice" />
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- JavaScript --}}
    @vite('resources/js/pages/finance/project-invoices/index.js')
    @include('partials.shared.print-dropdown-script')
@endsection
