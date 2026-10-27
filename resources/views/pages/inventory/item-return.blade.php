{{-- =====================================================================
     Halaman: Pengembalian Barang (Item Return)
     Tujuan: Menampilkan daftar return barang (tipe masuk & keluar) dengan
             filter tipe/bulan/tahun + pencarian, serta CRUD return
             (tambah, edit, hapus satu / massal) dan export PDF/Excel.
     Tampilan memakai komponen bergaya Filament (resources/views/components/fi).
     Data dari ItemReturnController@index:
     - $returns      : LengthAwarePaginator hasil baseQuery (search, return_type,
                       month, year) + eager load item, stockOut, stockIn.
     - $items        : seluruh master barang (ItemService::getAll()).
     - $stockOuts    : seluruh catatan barang keluar (StockService::getAllStockOuts()).
     - $stockIns     : seluruh catatan barang masuk (StockInService::getAllStockIns()).
     - $maxQuantities: array [id_return => max qty return] hasil computeMaxQuantities()
                       untuk validasi di modal edit.
     Komponen yang di-include:
     - components.inventory.item-return.table      (tabel + filter, pencarian,
                                                    hapus massal, paginasi)
     - components.inventory.item-return.add-modal  (dengan $items, $stockOuts, $stockIns)
     - components.inventory.item-return.edit-modal (per record + $maxQuantities)
     - x-fi.page-header, x-fi.export-dropdown, x-fi.button, x-fi.modal,
       x-fi.delete-row-modal (hapus 1 data dari menu Aksi)
     JS: inline window.ITEM_RETURN_DATA + @vite('resources/js/pages/inventory/item-returns/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Pengembalian Barang')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi (Export & Tambah) --}}
        <x-fi.page-header heading="Pengembalian Barang"
            :breadcrumbs="['Inventory' => null, 'Pengembalian Barang' => route('item-return.index')]">
            <x-slot:actions>
                {{-- Dropdown Export (PDF & Excel): queryParams membawa filter aktif
                     (search, month, year, return_type) agar export konsisten dengan daftar tampil. --}}
                <x-fi.export-dropdown :excelRoute="route('item-return.export.excel')" :pdfRoute="route('item-return.export.pdf')"
                    :queryParams="[
                        'search' => request('search'),
                        'month' => request('month'),
                        'year' => request('year'),
                        'return_type' => request('return_type'),
                    ]" />

                {{-- Tombol Tambah Pengembalian --}}
                <x-fi.button icon="plus" onclick="openModal('addModal')">Tambah Pengembalian</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tabel Data Pengembalian --}}
        {{-- Tabel di-include dari komponen terpisah; menerima $returns (LengthAwarePaginator, 15 per halaman). --}}
        @include('components.inventory.item-return.table', ['returns' => $returns])
    </div>

    {{-- SECTION: Modal Tambah Pengembalian --}}
    {{-- Alur form modal (ditangani JS item-returns/index.js):
         1) Pilih Tipe Pengembalian (masuk/keluar) — menentukan sumber stok.
         2) Pilih Barang → dropdown stok sumber relevan difilter dari window.ITEM_RETURN_DATA
            ($stockIns untuk tipe masuk, $stockOuts untuk tipe keluar).
         3) Validasi quantity: client-side (#addQuantityWarning) dan server-side
            (ItemReturnService::createReturn() melempar RuntimeException bila melebihi stok). --}}
    @include('components.inventory.item-return.add-modal', [
        'items' => $items,
        'stockOuts' => $stockOuts,
        'stockIns' => $stockIns,
    ])

    {{-- SECTION: Modal Edit Pengembalian (satu modal per record) --}}
    {{-- maxQuantity dihitung pre-compute di controller (computeMaxQuantities) untuk validasi
         jumlah return saat edit: sisa stok sumber + quantity return lama. --}}
    @foreach ($returns as $record)
        @include('components.inventory.item-return.edit-modal', [
            'record' => $record,
            'stockOuts' => $stockOuts,
            'stockIns' => $stockIns,
            'maxQuantity' => $maxQuantities[$record->id_return] ?? 0,
        ])
    @endforeach

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    {{-- Konfirmasi memanggil submitDeleteForm() → submit form #deleteForm
         (route('item-return.bulk-delete')). --}}
    <x-fi.modal id="deleteModal" heading="Hapus Pengembalian Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus semua pengembalian yang dipilih? Perubahan stok dari pengembalian
        tersebut akan dibatalkan. Data yang dihapus tidak dapat dikembalikan.
    </x-fi.modal>

    {{-- SECTION: Modal Konfirmasi Hapus 1 Pengembalian (menu Aksi ⋮ tiap baris) --}}
    {{-- Tiap pemicu membawa data-delete-row-action = route('item-return.destroy', id). --}}
    <x-fi.delete-row-modal heading="Hapus Pengembalian" field="selected_returns"
        warning="Perubahan stok dari pengembalian ini akan dibatalkan (stok barang dikembalikan seperti sebelum retur)." />

    {{-- SECTION: Scripts (Data untuk JavaScript) --}}
    {{-- window.ITEM_RETURN_DATA menyuplai data stok sumber (stockIns/stockOuts) & master item
         ke modul item-returns/index.js untuk logika dropdown & validasi quantity client-side. --}}
    @push('scripts')
        <script>
            window.ITEM_RETURN_DATA = {
                stockIns: {!! json_encode($stockIns->map(fn($s) => [
                    'id_stock_in' => $s->id_stock_in,
                    'id_item' => $s->id_item,
                    'quantity' => $s->quantity,
                ])) !!},
                stockOuts: {!! json_encode($stockOuts->map(fn($s) => [
                    'id_stock_out' => $s->id_stock_out,
                    'id_item' => $s->id_item,
                    'quantity' => $s->quantity,
                ])) !!},
                items: {!! json_encode($items->map(fn($i) => [
                    'id_item' => $i->id_item,
                    'name_item' => $i->name_item,
                ])) !!}
            };
        </script>

        @vite('resources/js/pages/inventory/item-returns/index.js')
    @endpush
@endsection
