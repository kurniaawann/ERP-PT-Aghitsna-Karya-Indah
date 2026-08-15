{{-- =====================================================================
     Halaman: DO Semen & Invoice Semen (Inventory)
     Tujuan: Halaman utama pengelolaan data semen yang ber-tab:
             1. "DO Semen" — CRUD delivery order semen (pencarian, filter
                bulan/tahun, bulk hapus, export PDF/Excel).
             2. "Invoice Semen" — invoice semen (di-include dari partial
                pages.inventory.partials.semen-invoice-content), aktif saat
                ?tab=semen-invoice.

     Data dari CementDeliveryOrderController@index (bergantung tab aktif):
     - Tab do-semen: $cementDeliveryOrders (LengthAwarePaginator hasil
                 CementDeliveryOrderService::getPaginatedSearch())
     - Tab semen-invoice: $invoices (Paginator InvoiceSemen), $paymentAccounts,
                 $executives
     - $tab : tab aktif (do-semen|semen-invoice)

     Komponen yang di-include:
     - components.inventory.cement-do.table       : tabel data DO semen
     - components.inventory.cement-do.add-modal   : modal tambah data DO semen
     - components.inventory.cement-do.edit-modal  : modal edit data DO semen
     - pages.inventory.partials.semen-invoice-content : konten tab Invoice Semen
     - x-filters.search-input, x-buttons.*, x-pagination, x-modal

     JS yang di-load (sesuai tab aktif):
     - @vite('resources/js/pages/inventory/cement-do/index.js')          (tab do-semen)
     - @vite('resources/js/pages/finance/semen-invoices/index.js')       (tab semen-invoice)
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - DO Semen')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-text-primary mb-1">DO Semen</h1>
            <p class="text-text-secondary text-sm">
                Kelola delivery order semen dan invoice semen dalam satu halaman.
            </p>
        </div>
    </div>

    {{-- ============================================================
         SECTION: Tab Menu (DO Semen | Invoice Semen)
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 my-6">
        <a href="{{ route('cement-do.index') }}"
            class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl border transition-colors duration-200 text-sm font-semibold
                {{ $tab === 'do-semen' ? 'bg-primary text-white border-primary' : 'bg-surface-base text-text-primary border-border-strong hover:bg-primary-light hover:text-primary' }}">
            <i class="fa-solid fa-truck"></i>
            <span>DO Semen</span>
        </a>
        <a href="{{ route('cement-do.index', ['tab' => 'semen-invoice']) }}"
            class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl border transition-colors duration-200 text-sm font-semibold
                {{ $tab === 'semen-invoice' ? 'bg-primary text-white border-primary' : 'bg-surface-base text-text-primary border-border-strong hover:bg-primary-light hover:text-primary' }}">
            <i class="fa-solid fa-file-invoice"></i>
            <span>Invoice Semen</span>
        </a>
    </div>

    @if ($tab === 'semen-invoice')
        @include('pages.inventory.partials.semen-invoice-content')
    @else
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

    {{-- SECTION: Scripts (JavaScript Modular) --}}
    @push('scripts')
        @vite('resources/js/pages/inventory/cement-do/index.js')
    @endpush
    @endif
@endsection