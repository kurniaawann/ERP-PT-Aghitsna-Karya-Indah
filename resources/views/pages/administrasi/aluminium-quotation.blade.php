@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - ' . (auth()->user()?->isSuperAdmin() ? 'Penawaran' : 'Penawaran Proyek'))

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">
            {{ auth()->user()?->isSuperAdmin() ? 'Penawaran' : 'Penawaran Proyek' }}</h1>

        {{-- Success / Error Alerts --}}
        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('aluminium-quotation.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari nomor / penerima..." />
            </form>

            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">

                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Penawaran" />
                </div>
            </div>
        </div>

        {{-- Table --}}
        @include('components.administrasi.aluminium-quotation.table', ['quotations' => $quotations])

        {{-- Pagination --}}
        <x-pagination :paginator="$quotations" />
    </div>

    {{-- Add Modal --}}
    @include('components.administrasi.aluminium-quotation.add-modal')

    {{-- Edit Modals & Detail Modals --}}
    @foreach ($quotations as $quotation)
        @include('components.administrasi.aluminium-quotation.detail-modal', ['quotation' => $quotation])
        @include('components.administrasi.aluminium-quotation.edit-modal', ['quotation' => $quotation])
    @endforeach

    {{-- Delete Confirm Modal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus penawaran yang dipilih?
    </x-modal>

    {{-- Scripts --}}
    @include('partials.administrasi.aluminium-quotation-scripts')
    @include('partials.shared.print-dropdown-script')
@endsection
