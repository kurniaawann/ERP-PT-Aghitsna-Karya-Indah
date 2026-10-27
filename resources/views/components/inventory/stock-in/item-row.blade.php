{{-- Satu kartu item pada modal Barang Masuk (gaya repeater Filament).
     Variabel: $items (pilihan barang), $record (opsional → mode edit).

     Hook JS incoming-items/index.js (jangan diubah): .item-row,
     .item-from-stock / .item-from-stock-edit, .item-select-wrapper (display
     di-toggle JS), .item-search-input, .item-dropdown, .item-options,
     .item-option[data-value|name|capital|stock|search], .no-results,
     .item-select-hidden, .item-name (display di-toggle JS), .item-qty,
     .item-capital, .remove-item (display di-toggle JS).
     Kartu baru di modal tambah dibuat JS (buildNewItemRowHtml /
     buildItemDropdownHtml) dengan markup yang SAMA dengan file ini. --}}
@php
    $isEdit = isset($record);
@endphp

<div class="item-row rounded-xl bg-white shadow-sm ring-1 ring-gray-950/10">
    {{-- Header kartu: judul + tombol hapus item (hanya modal tambah) --}}
    <div class="flex min-h-[3rem] items-center justify-between gap-3 rounded-t-xl border-b border-gray-100 bg-gray-50 px-4 py-2">
        @if ($isEdit)
            <span class="text-sm font-medium text-gray-950">Detail Barang</span>
        @else
            <span class="fi-repeater-item-title text-sm font-medium text-gray-950"></span>
            <button type="button" title="Hapus item"
                class="remove-item h-8 w-8 rounded-lg p-1.5 text-gray-400 transition duration-75 hover:bg-red-50 hover:text-red-600">
                <x-fi.icon name="trash" class="h-5 w-5" />
                <span class="sr-only">Hapus item</span>
            </button>
        @endif
    </div>

    <div class="grid gap-y-4 p-4">
        {{-- Sumber barang: dicentang = pilih dari Data Barang; tidak = barang baru --}}
        <label class="flex cursor-pointer items-start gap-3">
            <input type="checkbox" class="{{ $isEdit ? 'item-from-stock-edit' : 'item-from-stock' }} fi-checkbox mt-1"
                @checked($isEdit)>
            <span class="grid">
                <span class="text-sm font-medium text-gray-950">Barang sudah ada di Data Barang</span>
                <span class="text-sm text-gray-500">Bila tidak dicentang, nama yang diisi akan dibuat sebagai barang baru.</span>
            </span>
        </label>

        {{-- Pilih barang (pencarian) --}}
        <div class="item-select-wrapper relative" @unless ($isEdit) style="display: none;" @endunless>
            <span class="mb-2 block text-sm font-medium leading-6 text-gray-950">Barang<sup class="ms-0.5 font-medium text-red-600">*</sup></span>
            <div class="relative">
                <x-fi.icon name="magnifying-glass" class="pointer-events-none absolute start-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                <input type="text" class="item-search-input fi-control ps-10" placeholder="Cari nama barang..."
                    autocomplete="off" aria-label="Cari barang" value="{{ $isEdit ? $record->item?->name_item : '' }}">
            </div>

            <div class="item-dropdown absolute z-50 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-lg bg-white p-1 shadow-lg ring-1 ring-gray-950/5">
                <div class="item-options">
                    @foreach ($items as $item)
                        <div class="item-option cursor-pointer rounded-md px-3 py-2 hover:bg-gray-50"
                            data-value="{{ $item->id_item }}" data-name="{{ $item->name_item }}"
                            data-capital="{{ $item->capital_price }}" data-stock="{{ $item->quantity }}"
                            data-search="{{ strtolower($item->name_item) }}">
                            <div class="text-sm font-medium text-gray-950">{{ $item->name_item }}</div>
                            <div class="text-sm text-gray-500">{{ $item->id_item }} · Stok: {{ $item->quantity }} unit</div>
                        </div>
                    @endforeach
                </div>
                <div class="no-results hidden px-3 py-6 text-center text-sm text-gray-500">
                    Tidak ada barang ditemukan
                </div>
            </div>
        </div>

        <input type="hidden" class="item-select-hidden" value="{{ $isEdit ? $record->id_item : '' }}">

        {{-- Nama barang baru (disembunyikan saat memilih dari Data Barang) --}}
        <div class="item-name-field grid gap-y-2">
            <span class="text-sm font-medium leading-6 text-gray-950">Nama Barang Baru<sup class="ms-0.5 font-medium text-red-600">*</sup></span>
            <input type="text" class="item-name fi-control" placeholder="Masukkan nama barang" aria-label="Nama barang baru"
                required @if ($isEdit) style="display: none;" value="{{ $record->item?->name_item }}" @endif
                oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </div>

        {{-- Jumlah & Harga Modal --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="grid content-start gap-y-2">
                <span class="text-sm font-medium leading-6 text-gray-950">Jumlah (unit)<sup class="ms-0.5 font-medium text-red-600">*</sup></span>
                <input type="number" class="item-qty fi-control" placeholder="0" aria-label="Jumlah" required min="1"
                    value="{{ $isEdit ? $record->quantity : 1 }}"
                    oninvalid="this.setCustomValidity('Qty tidak boleh kosong')" oninput="this.setCustomValidity('')">
            </div>

            <div class="grid content-start gap-y-2">
                <span class="text-sm font-medium leading-6 text-gray-950">Harga Modal / unit<sup class="ms-0.5 font-medium text-red-600">*</sup></span>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-sm text-gray-500">Rp</span>
                    <input type="text" inputmode="numeric" class="item-capital fi-control ps-10" placeholder="0"
                        aria-label="Harga modal per unit" required min="0"
                        value="{{ $isEdit ? number_format($record->capital_price, 0, ',', '.') : '' }}"
                        oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')"
                        oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                </div>
            </div>
        </div>
    </div>
</div>
