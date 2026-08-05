{{-- =====================================================================
     Halaman: Data Barang (Inventory)
     Tujuan: Halaman CRUD master data barang. Menampilkan daftar barang
             (pencarian & paginasi) serta menyediakan aksi tambah, edit,
             hapus massal, dan export PDF/Excel.
     Data dari ItemController@index:
     - $items : LengthAwarePaginator hasil ItemService::getPaginatedSearch()
                (terfilter request('search')).
     Komponen yang di-include:
     - components.inventory.item.table       : tabel data barang
     - components.inventory.item.add-modal   : modal tambah barang
     - components.inventory.item.edit-modal  : modal edit barang (per item)
     - x-filters.search-input, x-buttons.*, x-pagination, x-modal
     JS: @vite('resources/js/pages/inventory/items/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Barang')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- SECTION: Header Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Barang</h1>

        {{-- SECTION: Filter & Toolbar Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian: submit GET ke route('item.index') dengan param 'search'.
                 Komponen x-filters.search-input menahan nilai request('search') saat submit. --}}
            <form method="GET" action="{{ route('item.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari barang..." />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 xl:mt-0 w-full xl:w-auto">
                <div class="flex flex-col xl:flex-row gap-2 w-full xl:w-auto">

                    {{-- Dropdown Export (PDF & Excel) --}}
                    <x-buttons.print-dropdown :excelRoute="route('item.export.excel')" :pdfRoute="route('item.export.pdf')" />

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah Barang --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Data" />
                </div>
            </div>
        </div>

        {{-- SECTION: Tabel Data Barang --}}
        {{-- Tabel di-include dari komponen terpisah; menerima $items sebagai data utama.
             Checkbox di tiap baris (name=selected_items[]) dipakai untuk hapus massal. --}}
        @include('components.inventory.item.table', ['items' => $items])

    </div>

    {{-- SECTION: Pagination --}}
    {{-- Paginasi memakai komponen x-pagination dengan data $items (LengthAwarePaginator) --}}
    <x-pagination :paginator="$items" />

    {{-- SECTION: Modal Tambah Barang --}}
    @include('components.inventory.item.add-modal')

    {{-- SECTION: Modal Edit Barang (satu modal per item) --}}
    {{-- Satu modal dibuat per item dengan id unik 'editModal-{id_item}'
         agar nilai form & validasi terpisah per barang. --}}
    @foreach ($items as $item)
        @include('components.inventory.item.edit-modal', ['item' => $item])
    @endforeach

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    {{-- Saat dikonfirmasi, memanggil submitDeleteForm() (didefinisikan di JS items/index.js)
         yang mengirim request hapus massal ke route('items.destroySelected'). --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- SECTION: Scripts (JavaScript Modular) --}}
    {{-- Modul items/index.js menangani: pencarian, dropdown export, select-all,
         hapus massal, dan interaksi modal tambah/edit. --}}
    @push('scripts')
        @vite('resources/js/pages/inventory/items/index.js')
    @endpush
@endsection
