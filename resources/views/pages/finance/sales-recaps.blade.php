{{-- =====================================================================
     Halaman: Rekap Penjualan (Sales Recaps)
     Tujuan: Menampilkan daftar rekap penjualan dengan filter bulan/tahun
             & pencarian proyek, export Excel/PDF, CRUD, ubah status
             (Lunas/Belum Lunas), dan hapus massal.
     Data dari RecapSalesController@index:
     - $salesRecaps: Paginator SalesRecap (10/halaman) hasil
                     RecapSalesService::buildFilteredQuery($request)
                     diurutkan created_at/date desc, difilter month/year/search.
     - $items      : Daftar item inventory (Items) diurutkan name_item,
                     dipakai dropdown modal tambah/edit dan
                     window._itemsData untuk autofill harga.
     - $grandTotals: Grand totals hasil RecapSalesService::getGrandTotals()
                     (kapital, penjualan, laba) dengan filter yang sama.
     Komponen yang di-include:
     - x-filters.month-filter / year-filter / search-input : toolbar filter & pencarian
     - x-buttons.print-dropdown / delete-button / add-button : tombol aksi
     - components.finance.sales-recaps.table               : tabel rekap + grand totals
     - components.finance.sales-recaps.add-modal / edit-modal / status-modal : modal CRUD
     - x-pagination                                        : navigasi halaman
     - x-modal                                             : konfirmasi hapus
     JS: @vite('resources/js/pages/finance/sales-recaps/index.js')
         (+ window._itemsData inline)
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Rekap Penjualan')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- ==================== Header Rekap Penjualan ==================== --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Rekap Penjualan</h1>

        {{-- ==================== Toolbar Filter & Aksi ==================== --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('recap-sales.index') }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.status-filter :value="request('status')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari proyek..." responsive="custom" />
            </form>

            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">
                    <x-buttons.print-dropdown :excelRoute="route('recap-sales.export.excel')" :pdfRoute="route('recap-sales.export.pdf')" :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year'), 'status' => request('status')]" responsive="custom" />
                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Laporan" responsive="custom" />
                </div>
            </div>
        </div>

        {{-- ==================== Tabel Rekap Penjualan ==================== --}}
        {{-- Tabel rekap; menerima $grandTotals untuk menampilkan baris
             total (kapital, penjualan, laba) di akhir tabel. --}}
        @include('components.finance.sales-recaps.table', [
            'salesRecaps' => $salesRecaps,
            'grandTotals' => $grandTotals,
        ])
    </div>

    {{-- ==================== Pagination ==================== --}}
    <x-pagination :paginator="$salesRecaps" />

    {{-- ==================== Modal Tambah ==================== --}}
    @include('components.finance.sales-recaps.add-modal', ['items' => $items])

    {{-- ==================== Modal Edit & Status (hanya untuk yang belum lunas) ==================== --}}
    {{-- Modal edit & ubah status hanya dirender untuk data yang belum lunas
         (!isLunas()); rekap yang sudah lunas bersifat read-only karena
         tidak boleh diubah lagi (dijaga juga di controller). --}}
    @foreach ($salesRecaps as $sale)
        @if (!$sale->isLunas())
            @include('components.finance.sales-recaps.edit-modal', ['sale' => $sale, 'items' => $items])
            @include('components.finance.sales-recaps.status-modal', ['sale' => $sale])
        @endif
    @endforeach

    {{-- ==================== Modal Konfirmasi Bulk Delete ==================== --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah Anda yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- ==================== JavaScript ==================== --}}
    {{-- Data item inventory di-expose ke window._itemsData sebagai array
         JSON untuk autofill harga (capital/selling) & quantity saat
         memilih item pada dropdown modal tambah/edit. --}}
    <script>
        window._itemsData = {!! json_encode($items->map(fn($item) => [
            'id_item' => $item->id_item,
            'name_item' => $item->name_item,
            'capital_price' => $item->capital_price,
            'selling_price' => $item->selling_price,
            'quantity' => $item->quantity,
        ])->values()) !!};
    </script>
    @vite(['resources/js/pages/finance/sales-recaps/index.js'])
@endsection
