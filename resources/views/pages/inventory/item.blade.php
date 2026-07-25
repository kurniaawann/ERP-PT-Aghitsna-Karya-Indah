@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Barang')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- Header Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Barang</h1>

        {{-- Toolbar: Pencarian & Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('item.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari barang..." />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col lg:flex-row gap-2 w-full lg:w-auto">

                    {{-- Dropdown Export (PDF & Excel) --}}
                    <x-buttons.print-dropdown :excelRoute="route('item.export.excel')" :pdfRoute="route('item.export.pdf')" />

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah Barang --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Data" />
                </div>
            </div>
        </div>

        {{-- Tabel Data Barang --}}
        @include('components.inventory.item.table', ['items' => $items])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$items" />

    {{-- Modal Tambah Barang --}}
    @include('components.inventory.item.add-modal')

    {{-- Modal Edit Barang (satu modal per item) --}}
    @foreach ($items as $item)
        @include('components.inventory.item.edit-modal', ['item' => $item])
    @endforeach

    {{-- Modal Konfirmasi Hapus Massal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- JavaScript Modular --}}
    @push('scripts')
        @vite('resources/js/pages/inventory/items/index.js')
    @endpush
@endsection
