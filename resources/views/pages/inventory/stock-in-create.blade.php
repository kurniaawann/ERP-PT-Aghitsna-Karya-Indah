{{-- =====================================================================
     Halaman: Tambah Barang Masuk (Inventory)
     Tujuan: Pengganti modal tambah Barang Masuk — kartu item (multi barang),
             tanggal & keterangan, serta ringkasan total dalam satu halaman.
             Edit tetap memakai modal di halaman daftar (1 record = 1 item).

     Data dari ItemStockInController@create:
     - $items : seluruh master barang untuk dropdown pilih barang
                (ItemService::getAll()).

     Submit ke route('stock-in.store') (POST): kartu item diserialisasi JS
     menjadi JSON di #items-json. Berhasil → kembali ke daftar Barang Masuk;
     gagal → kembali ke halaman ini dengan old() (kartu item ikut dipulihkan).

     JS: inline window.STOCK_IN_ITEMS + @vite('resources/js/pages/inventory/incoming-items/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@php
    // Kartu item awal: JSON item lama (penyimpanan gagal) → 1 kartu kosong
    $oldItems = json_decode((string) old('items', ''), true);
    $oldItems = is_array($oldItems) ? array_values(array_filter($oldItems, 'is_array')) : [];
@endphp

@section('title', 'PT Aghitsna Karya Indah - Tambah Barang Masuk')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman --}}
        <x-fi.page-header heading="Tambah Barang Masuk"
            :breadcrumbs="['Inventory' => null, 'Barang Masuk' => route('stock-in.index'), 'Tambah' => null]" />

        {{-- SECTION: Ringkasan error validasi server --}}
        <x-fi.validation-errors />

        <form id="stock-in-create-form" method="POST" action="{{ route('stock-in.store') }}" class="flex flex-col gap-y-6">
            @csrf

            {{-- Desktop: kartu item di kiri (2 kolom), Informasi & Ringkasan di kanan.
                 Mobile: Informasi → Item Barang → Ringkasan. --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:grid-rows-[auto_1fr]">

                {{-- SECTION: Informasi (tanggal & keterangan berlaku untuk semua item) --}}
                <x-fi.section heading="Informasi" class="lg:col-start-3 lg:row-start-1">
                    <div class="grid gap-y-5">
                        <x-fi.field label="Tanggal" for="add-date" required>
                            <x-fi.input type="date" id="add-date" name="date" :value="old('date', date('Y-m-d'))" required
                                oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                        </x-fi.field>

                        <x-fi.field label="Keterangan" for="add-notes">
                            <x-fi.textarea id="add-notes" name="notes" rows="3" maxlength="500"
                                placeholder="Masukkan keterangan (opsional)">{{ old('notes') }}</x-fi.textarea>
                        </x-fi.field>
                    </div>
                </x-fi.section>

                {{-- SECTION: Item Barang (repeater kartu; kartu baru ditambah JS) --}}
                <x-fi.section heading="Item Barang" description="Stok dan harga modal rata-rata barang akan diperbarui otomatis."
                    class="lg:col-span-2 lg:col-start-1 lg:row-span-2 lg:row-start-1">
                    <div id="items-list" class="fi-repeater-list grid gap-y-3">
                        @forelse ($oldItems as $old)
                            @include('components.inventory.stock-in.item-row', ['items' => $items, 'old' => $old])
                        @empty
                            @include('components.inventory.stock-in.item-row', ['items' => $items])
                        @endforelse
                    </div>

                    <x-fi.button id="add-item" color="gray" icon="plus" class="mt-3 w-full">Tambah Item</x-fi.button>
                </x-fi.section>

                {{-- SECTION: Ringkasan (dihitung ulang incoming-items/index.js; menempel saat scroll) --}}
                <x-fi.section heading="Ringkasan" class="self-start lg:sticky lg:top-0 lg:col-start-3 lg:row-start-2">
                    <dl class="grid gap-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">Jumlah item</dt>
                            <dd class="font-medium tabular-nums text-gray-950" data-stock-in-summary="items">0</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">Total unit</dt>
                            <dd class="font-medium tabular-nums text-gray-950" data-stock-in-summary="units">0 unit</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-gray-200 pt-3">
                            <dt class="font-semibold text-gray-950">Total nilai modal</dt>
                            <dd class="text-base font-semibold tabular-nums text-gray-950" data-stock-in-summary="capital">Rp 0</dd>
                        </div>
                    </dl>
                </x-fi.section>
            </div>

            {{-- Hidden Input: JSON Items (diisi serializeItemsData() saat submit) --}}
            <input type="hidden" name="items" id="items-json" value="[]">

            {{-- SECTION: Aksi Form --}}
            <div class="flex flex-wrap items-center gap-3">
                <x-fi.button type="submit">Simpan</x-fi.button>
                <x-fi.button color="gray" :href="route('stock-in.index')">Batal</x-fi.button>
            </div>
        </form>
    </div>

    {{-- SECTION: Scripts (Data Items untuk JavaScript) --}}
    {{-- window.STOCK_IN_ITEMS menyuplai data item (id, nama, harga modal, stok) ke modul
         incoming-items/index.js untuk kartu item baru (tombol "Tambah Item"). --}}
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
