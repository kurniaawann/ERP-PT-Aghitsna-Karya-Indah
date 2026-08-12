{{-- =====================================================================
     Halaman: Data Semen (Inventory)
     Tujuan: Halaman CRUD master data semen. Menampilkan daftar data
             semen (pencarian & paginasi) serta menyediakan aksi tambah,
             edit, hapus massal, dan export PDF/Excel.
     Data dari CementController@index:
     - $cements : LengthAwarePaginator hasil CementService::getPaginatedSearch()
                 (terfilter request('search')).
     Komponen yang di-include:
     - components.inventory.cement.table       : tabel data semen
     - components.inventory.cement.add-modal   : modal tambah data semen
     - components.inventory.cement.edit-modal  : modal edit data semen (per item)
     - x-filters.search-input, x-buttons.*, x-pagination, x-modal
     JS: @vite('resources/js/pages/inventory/cement/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Semen')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- SECTION: Header Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Semen</h1>

        {{-- SECTION: Filter & Toolbar Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian: submit GET ke route('cement.index') dengan param 'search'. --}}
            <form method="GET" action="{{ route('cement.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari data semen..." />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 xl:mt-0 w-full xl:w-auto">
                <div class="flex flex-col xl:flex-row gap-2 w-full xl:w-auto">

                    {{-- Dropdown Export (PDF & Excel) --}}
                    <x-buttons.print-dropdown :excelRoute="route('cement.export.excel')" :pdfRoute="route('cement.export.pdf')" />

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah Data Semen --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Data" />
                </div>
            </div>
        </div>

        {{-- SECTION: Tabel Data Semen --}}
        @include('components.inventory.cement.table', ['cements' => $cements])

    </div>

    {{-- SECTION: Pagination --}}
    <x-pagination :paginator="$cements" />

    {{-- SECTION: Modal Tambah Data Semen --}}
    @include('components.inventory.cement.add-modal')

    {{-- SECTION: Modal Edit Data Semen (satu modal per item) --}}
    @foreach ($cements as $cement)
        @include('components.inventory.cement.edit-modal', ['cement' => $cement])
    @endforeach

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- SECTION: Scripts (JavaScript Modular) --}}
    @push('scripts')
        @vite('resources/js/pages/inventory/cement/index.js')
    @endpush
@endsection
