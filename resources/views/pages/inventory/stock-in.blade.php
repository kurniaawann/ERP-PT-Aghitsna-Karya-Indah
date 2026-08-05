{{-- =====================================================================
     Halaman: Barang Masuk (Stock In)
     Tujuan: Menampilkan daftar barang masuk dengan filter bulan/tahun +
             pencarian, serta aksi tambah, edit, hapus massal, dan export
             PDF/Excel.
     Data dari ItemStockInController@index:
     - $stockIns : LengthAwarePaginator hasil baseQuery (search, month, year)
                   + eager load relasi item.
     - $items    : seluruh master barang untuk dropdown modal (ItemService::getAll()).
     Komponen yang di-include:
     - components.inventory.stock-in.table      : tabel data barang masuk
     - components.inventory.stock-in.add-modal  : modal tambah (menerima $items)
     - components.inventory.stock-in.edit-modal : modal edit per record
     - x-filters.*, x-buttons.*, x-pagination, x-modal
     JS: inline window.STOCK_IN_ITEMS + @vite('resources/js/pages/inventory/incoming-items/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Barang Masuk')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- SECTION: Header Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Barang Masuk</h1>

        {{-- SECTION: Filter, Pencarian & Toolbar Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Filter & Pencarian --}}
            <form method="GET" action="{{ route('stock-in.index') }}" id="filterForm"
                class="w-full xl:w-auto xl:flex-1 flex flex-col xl:flex-row gap-3">

                {{-- Filter Bulan: onchange langsung submit form (#filterForm) = auto filter --}}
                <x-filters.month-filter :value="request('month')" onchange="document.getElementById('filterForm').submit()" />

                {{-- Filter Tahun: onchange langsung submit form (#filterForm) = auto filter --}}
                <x-filters.year-filter :value="request('year')" onchange="document.getElementById('filterForm').submit()" />

                {{-- Input Pencarian: kata kunci via request('search') (scope search di model) --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari barang masuk..." />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 xl:mt-0 w-full xl:w-auto">
                <div class="flex flex-col xl:flex-row gap-2 w-full xl:w-auto">

                    {{-- Dropdown Export (PDF & Excel): queryParams membawa filter aktif
                         (search, month, year) agar export konsisten dengan daftar tampil. --}}
                    <x-buttons.print-dropdown :excelRoute="route('stock-in.export.excel')" :pdfRoute="route('stock-in.export.pdf')" :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]" />

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah Barang Masuk --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Barang Masuk" />
                </div>
            </div>
        </div>

        {{-- SECTION: Tabel Data Barang Masuk --}}
        {{-- Tabel di-include dari komponen terpisah; menerima $stockIns (LengthAwarePaginator). --}}
        @include('components.inventory.stock-in.table', ['stockIns' => $stockIns])

    </div>

    {{-- SECTION: Pagination --}}
    <x-pagination :paginator="$stockIns" />

    {{-- SECTION: Modal Tambah Barang Masuk --}}
    {{-- Modal menerima $items untuk dropdown pemilihan barang (multi-item via JS). --}}
    @include('components.inventory.stock-in.add-modal', ['items' => $items])

    {{-- SECTION: Modal Edit Barang Masuk (satu modal per record) --}}
    {{-- Satu modal dibuat per record dengan id unik; $allItems untuk pilihan barang saat edit. --}}
    @foreach ($stockIns as $record)
        @include('components.inventory.stock-in.edit-modal', ['record' => $record, 'allItems' => $items])
    @endforeach

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    {{-- Konfirmasi memanggil submitDeleteForm() → hapus massal ke route('stock-ins.destroySelected'). --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- SECTION: Scripts (Data Items untuk JavaScript) --}}
    {{-- window.STOCK_IN_ITEMS menyuplai data item (id, nama, harga modal, stok) ke modul
         incoming-items/index.js untuk perhitungan nilai & validasi pada modal tambah/edit. --}}
    @push('scripts')
        <script>
            window.STOCK_IN_ITEMS = {!! json_encode($items->map(fn($item) => [
                'id_item' => $item->id_item,
                'name_item' => $item->name_item,
                'capital_price' => $item->capital_price,
                'quantity' => $item->quantity,
            ])) !!};
        </script>

        @vite('resources/js/pages/inventory/incoming-items/index.js')
    @endpush
@endsection
