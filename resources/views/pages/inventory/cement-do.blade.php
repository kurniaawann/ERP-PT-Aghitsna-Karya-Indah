{{-- =====================================================================
     Halaman: DO Semen (Inventory)
     Tujuan: Halaman utama pengelolaan data semen. Invoice Semen kini
             digenerate langsung dari baris DO Semen (tombol "Buat Invoice"
             pada tiap baris DO) sehingga tidak ada lagi tab terpisah.

     Data dari CementDeliveryOrderController@index:
     - $cementDeliveryOrders : LengthAwarePaginator hasil
                 CementDeliveryOrderService::getPaginatedSearch()
     - $paymentAccounts      : Rekening pembayaran aktif (untuk modal
                 "Buat Invoice").
     - $executives           : Petinggi untuk dropdown penandatangan
                 invoice (modal "Buat Invoice").

     Komponen yang di-include:
     - components.inventory.cement-do.table         : tabel DO semen
     - components.inventory.cement-do.add-modal     : modal tambah DO
     - components.inventory.cement-do.edit-modal    : modal edit DO
     - components.inventory.cement-do.generate-invoice-modal : modal
                 "Buat Invoice" per DO
     - x-filters.search-input, x-buttons.*, x-pagination, x-modal

     JS yang di-load:
     - @vite('resources/js/pages/inventory/cement-do/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - DO Semen')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-text-primary mb-1">DO Semen</h1>
            <p class="text-text-secondary text-sm">
                Kelola delivery order semen dan buat invoice semen langsung dari DO.
            </p>
        </div>
    </div>

    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- SECTION: Filter & Toolbar Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Filter & Pencarian: submit GET ke route('cement-do.index'). --}}
            <form method="GET" action="{{ route('cement-do.index') }}" id="filterForm"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">

                {{-- Filter Bulan: onchange langsung submit form (#filterForm) = auto filter --}}
                <x-filters.month-filter :value="request('month')"
                    onchange="document.getElementById('filterForm').submit()" />

                {{-- Filter Tahun: onchange langsung submit form (#filterForm) = auto filter --}}
                <x-filters.year-filter :value="request('year')"
                    onchange="document.getElementById('filterForm').submit()" />

                <x-filters.search-input :value="request('search')" placeholder="Cari data DO Semen..." />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 xl:mt-0 w-full xl:w-auto">
                <div class="flex flex-col xl:flex-row gap-2 w-full xl:w-auto">

                    {{-- Dropdown Export (PDF & Excel) --}}
                    <x-buttons.print-dropdown :excelRoute="route('cement-do.export.excel')"
                        :pdfRoute="route('cement-do.export.pdf')" />

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

    {{-- SECTION: Modal "Buat Invoice" (satu modal per baris DO) --}}
    @foreach ($cementDeliveryOrders as $cementDeliveryOrder)
        @include('components.inventory.cement-do.generate-invoice-modal', [
            'cementDeliveryOrder' => $cementDeliveryOrder,
            'paymentAccounts' => $paymentAccounts,
            'executives' => $executives,
        ])
    @endforeach

    {{-- SECTION: Scripts (JavaScript Modular) --}}
    @push('scripts')
        @vite('resources/js/pages/inventory/cement-do/index.js')
    @endpush
@endsection