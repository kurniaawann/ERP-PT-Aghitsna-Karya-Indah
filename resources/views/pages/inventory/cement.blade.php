{{-- =====================================================================
     Halaman: Data Semen (Inventory)
     Tujuan: Tampilan rekap (read-only) semua baris Data Semen yang sudah
             diinput melalui modul DO Semen. Menampilkan daftar data semen
             (pencarian & paginasi) beserta export PDF/Excel.
     Data dari CementController@index:
     - $cements : LengthAwarePaginator hasil CementService::getPaginatedSearch()
                 (terfilter request('search')).
     Komponen yang di-include:
     - components.inventory.cement.table       : tabel data semen (read-only)
     - x-filters.search-input, x-buttons.*, x-pagination
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Semen')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- SECTION: Header Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Semen</h1>

        {{-- SECTION: Toolbar Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian: submit GET ke route('cement.index') dengan param 'search'. --}}
            <form method="GET" action="{{ route('cement.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari data semen..." />
            </form>

            {{-- Tombol Aksi: Export PDF & Excel --}}
            <div class="flex items-center gap-2 mt-2 xl:mt-0 w-full xl:w-auto">
                <x-buttons.print-dropdown :excelRoute="route('cement.export.excel')"
                    :pdfRoute="route('cement.export.pdf')" />
            </div>
        </div>

        {{-- Keterangan bahwa data diinput lewat modul DO Semen --}}
        <p class="mb-4 text-sm text-text-secondary">
            Data semen diinput pada modul <strong>DO Semen</strong> (1 DO dapat memuat banyak baris data semen).
            Halaman ini hanya menampilkan rekapan seluruh data semen.
        </p>

        {{-- SECTION: Tabel Data Semen --}}
        @include('components.inventory.cement.table', ['cements' => $cements])

    </div>

    {{-- SECTION: Pagination --}}
    <x-pagination :paginator="$cements" />

    {{-- SECTION: Scripts (JavaScript Modular) --}}
    @push('scripts')
        @vite('resources/js/pages/inventory/cement/index.js')
    @endpush
@endsection
