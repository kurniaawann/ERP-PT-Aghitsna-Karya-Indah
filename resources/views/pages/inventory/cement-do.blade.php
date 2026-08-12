{{-- =====================================================================
     Halaman: DO Semen (Inventory)
     Tujuan: Halaman CRUD data delivery order semen. Menampilkan daftar
             DO semen (pencarian & paginasi) serta menyediakan aksi tambah,
             edit, hapus massal, dan export PDF/Excel.
     Data dari CementDeliveryOrderController@index:
     - $cementDeliveryOrders : LengthAwarePaginator hasil
                 CementDeliveryOrderService::getPaginatedSearch()
                 (terfilter request('search')).
     Komponen yang di-include:
     - components.inventory.cement-do.table       : tabel data DO semen
     - components.inventory.cement-do.add-modal   : modal tambah data DO semen
     - components.inventory.cement-do.edit-modal  : modal edit data DO semen
     - x-filters.search-input, x-buttons.*, x-pagination, x-modal
     JS: @vite('resources/js/pages/inventory/cement-do/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - DO Semen')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- SECTION: Header Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">DO Semen</h1>

        {{-- SECTION: Filter & Toolbar Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian: submit GET ke route('cement-do.index') dengan param 'search'. --}}
            <form method="GET" action="{{ route('cement-do.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari data DO Semen..." />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 xl:mt-0 w-full xl:w-auto">
                <div class="flex flex-col xl:flex-row gap-2 w-full xl:w-auto">

                    {{-- Dropdown Export (PDF & Excel) --}}
                    <x-buttons.print-dropdown :excelRoute="route('cement-do.export.excel')" :pdfRoute="route('cement-do.export.pdf')" />

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah Data DO Semen --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Data" />
                </div>
            </div>
        </div>

        {{-- SECTION: Tabel DO Semen --}}
        @include('components.inventory.cement-do.table', ['cementDeliveryOrders' => $cementDeliveryOrders])

    </div>

    {{-- SECTION: Pagination --}}
    <x-pagination :paginator="$cementDeliveryOrders" />

    {{-- SECTION: Modal Tambah DO Semen --}}
    @include('components.inventory.cement-do.add-modal')

    {{-- SECTION: Modal Edit DO Semen (satu modal per item) --}}
    @foreach ($cementDeliveryOrders as $cementDeliveryOrder)
        @include('components.inventory.cement-do.edit-modal', ['cementDeliveryOrder' => $cementDeliveryOrder])
    @endforeach

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- SECTION: Scripts (JavaScript Modular) --}}
    @push('scripts')
        @vite('resources/js/pages/inventory/cement-do/index.js')
    @endpush
@endsection
