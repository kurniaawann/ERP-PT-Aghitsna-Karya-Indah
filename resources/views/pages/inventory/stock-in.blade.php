{{-- =====================================================================
     Halaman: Barang Masuk (Stock In)
     Tujuan: Menampilkan daftar barang masuk dengan filter bulan/tahun +
             pencarian, serta aksi tambah, edit, hapus (satu / massal), dan
             export PDF/Excel.
     Tampilan memakai komponen bergaya Filament (resources/views/components/fi).
     Data dari ItemStockInController@index:
     - $stockIns : LengthAwarePaginator hasil baseQuery (search, month, year)
                   + eager load relasi item.
     - $items    : seluruh master barang untuk dropdown modal (ItemService::getAll()).
     Komponen yang di-include:
     - components.inventory.stock-in.table      : tabel data barang masuk (+ filter,
                                                  pencarian, hapus massal, paginasi)
     - components.inventory.stock-in.add-modal  : modal tambah (menerima $items)
     - components.inventory.stock-in.edit-modal : modal edit per record
     - x-fi.page-header, x-fi.export-dropdown, x-fi.button, x-fi.modal,
       x-fi.delete-row-modal (hapus 1 data dari menu Aksi)
     JS: inline window.STOCK_IN_ITEMS + @vite('resources/js/pages/inventory/incoming-items/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Barang Masuk')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi (Export & Tambah) --}}
        <x-fi.page-header heading="Barang Masuk"
            :breadcrumbs="['Inventory' => null, 'Barang Masuk' => route('stock-in.index')]">
            <x-slot:actions>
                {{-- Dropdown Export (PDF & Excel): queryParams membawa filter aktif
                     (search, month, year) agar export konsisten dengan daftar tampil. --}}
                <x-fi.export-dropdown :excelRoute="route('stock-in.export.excel')" :pdfRoute="route('stock-in.export.pdf')"
                    :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]" />

                {{-- Tombol Tambah Barang Masuk --}}
                <x-fi.button icon="plus" onclick="openModal('addModal')">Tambah Barang Masuk</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tabel Data Barang Masuk --}}
        {{-- Tabel di-include dari komponen terpisah; menerima $stockIns (LengthAwarePaginator). --}}
        @include('components.inventory.stock-in.table', ['stockIns' => $stockIns])
    </div>

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
    <x-fi.modal id="deleteModal" heading="Hapus Barang Masuk Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus semua barang masuk yang dipilih? Stok barang terkait akan dikurangi
        sesuai jumlahnya. Data yang dihapus tidak dapat dikembalikan.
    </x-fi.modal>

    {{-- SECTION: Modal Konfirmasi Hapus 1 Barang Masuk (menu Aksi ⋮ tiap baris) --}}
    {{-- Tiap pemicu membawa data-delete-row-action = route('stock-in.destroy', id). --}}
    <x-fi.delete-row-modal heading="Hapus Barang Masuk" field="selected_stock_ins"
        warning="Stok barang terkait akan dikurangi sesuai jumlah barang masuk ini." />

    {{-- SECTION: Scripts (Data Items untuk JavaScript) --}}
    {{-- window.STOCK_IN_ITEMS menyuplai data item (id, nama, harga modal, stok) ke modul
         incoming-items/index.js untuk baris item baru pada modal tambah. --}}
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
