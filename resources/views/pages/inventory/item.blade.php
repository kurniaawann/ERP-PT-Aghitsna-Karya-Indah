{{-- =====================================================================
     Halaman: Data Barang (Inventory)
     Tujuan: Halaman CRUD master data barang. Menampilkan daftar barang
             (pencarian & paginasi) serta menyediakan aksi tambah, edit,
             hapus massal, dan export PDF/Excel.
     Tampilan memakai komponen bergaya Filament (resources/views/components/fi).
     Data dari ItemController@index:
     - $items : LengthAwarePaginator hasil ItemService::getPaginatedSearch()
                (terfilter request('search')).
     Komponen yang di-include:
     - components.inventory.item.table       : tabel data barang (+ pencarian,
                                               hapus massal, paginasi)
     - components.inventory.item.add-modal   : modal tambah barang
     - components.inventory.item.edit-modal  : modal edit barang (per item)
     - x-fi.page-header, x-fi.export-dropdown, x-fi.button, x-fi.modal
     JS: @vite('resources/js/pages/inventory/items/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Barang')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi (Export & Tambah) --}}
        <x-fi.page-header heading="Data Barang" :breadcrumbs="['Inventory' => null, 'Data Barang' => route('item.index')]">
            <x-slot:actions>
                {{-- Dropdown Export (PDF & Excel) --}}
                <x-fi.export-dropdown :excelRoute="route('item.export.excel')" :pdfRoute="route('item.export.pdf')" />

                {{-- Tombol Tambah Barang --}}
                <x-fi.button icon="plus" onclick="openModal('addModal')">Tambah Barang</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tabel Data Barang --}}
        {{-- Tabel di-include dari komponen terpisah; menerima $items sebagai data utama.
             Checkbox di tiap baris (name=selected_items[]) dipakai untuk hapus massal. --}}
        @include('components.inventory.item.table', ['items' => $items])
    </div>

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
    <x-fi.modal id="deleteModal" heading="Hapus Data Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-fi.modal>

    {{-- SECTION: Scripts (JavaScript Modular) --}}
    {{-- Modul items/index.js menangani: select-all, status tombol hapus massal,
         validasi & format harga pada modal tambah/edit. --}}
    @push('scripts')
        @vite('resources/js/pages/inventory/items/index.js')
    @endpush
@endsection
