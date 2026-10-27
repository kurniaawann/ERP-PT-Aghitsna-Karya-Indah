{{-- =====================================================================
     Halaman: Barang Keluar (Stock Out) — read-only
     Tujuan: Menampilkan daftar barang keluar dengan filter bulan/tahun +
             pencarian, dan export PDF/Excel. Tidak ada aksi tambah/edit/
             hapus (data dibuat otomatis dari Rekap Penjualan — SalesRecapObserver).
     Tampilan memakai komponen bergaya Filament (resources/views/components/fi).
     Data dari ItemStockOutController@index:
     - $stockOuts : LengthAwarePaginator hasil baseQuery (search, month, year)
                    + eager load relasi item, salesRecap, returns.
     Komponen yang di-include:
     - components.inventory.stock-out.table : tabel data barang keluar (+ filter,
                                              pencarian, paginasi)
     - x-fi.page-header, x-fi.export-dropdown
     JS: @vite('resources/js/pages/inventory/outgoing-items/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Barang Keluar')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi (Export) --}}
        <x-fi.page-header heading="Barang Keluar"
            :breadcrumbs="['Inventory' => null, 'Barang Keluar' => route('stock-out.index')]">
            <x-slot:actions>
                {{-- Dropdown Export (PDF & Excel): queryParams membawa filter aktif
                     (search, month, year) agar export konsisten dengan daftar tampil. --}}
                <x-fi.export-dropdown :excelRoute="route('stock-out.export.excel')" :pdfRoute="route('stock-out.export.pdf')"
                    :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]" />
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tabel Data Barang Keluar (read-only) --}}
        @include('components.inventory.stock-out.table', ['stockOuts' => $stockOuts])
    </div>

    {{-- SECTION: Scripts --}}
    {{-- Modul outgoing-items/index.js (halaman read-only; export ditangani shared/print.js). --}}
    @push('scripts')
        @vite('resources/js/pages/inventory/outgoing-items/index.js')
    @endpush
@endsection
