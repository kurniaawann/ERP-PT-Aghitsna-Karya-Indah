@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Bukti Kas Keluar')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Bukti Kas Keluar</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('cash-out-proof.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari bukti kas keluar..." />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.print-dropdown-with-selected :pdfRoute="route('cash-out-proof.export.pdf')" :queryParams="['search' => request('search')]" />

                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah BKK" />
                </div>
            </div>
        </div>

        {{-- Table Component --}}
        @include('components.administrasi.cash-out-proof.table', ['cashOuts' => $cashOuts])

        {{-- Pagination --}}
        <x-pagination :paginator="$cashOuts" />
    </div>

    {{-- Modal Tambah --}}
    @include('components.administrasi.cash-out-proof.add-modal')

    {{-- Modal Edit untuk setiap cash out --}}
    @foreach ($cashOuts as $cashOut)
        @include('components.administrasi.cash-out-proof.edit-modal', ['cashOut' => $cashOut])
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- JavaScript --}}
    @include('partials.administrasi.cash-out-proof-scripts')
    @include('partials.shared.print-dropdown-script')
@endsection
