{{-- Modal Tambah Pengembalian Barang (gaya Filament).
     Hook JS (item-returns/index.js, jangan diubah): #addModal form +
     button[type=submit], #addReturnType, #addItemSearch (readonly, fokus →
     buka dropdown), #add-item-dropdown, #addItemFilter, #add-item-options
     (opsi dibuat JS: renderAddDropdownItems), #add-item-load-more > button,
     #add-item-no-results, #addItemId, #addStockInId, #addStockOutId,
     #addQuantity, #addQuantityWarning, #addAvailableStock. --}}
<x-fi.modal id="addModal" heading="Tambah Pengembalian Barang"
    description="Catat barang yang diretur ke supplier atau yang kembali dari proyek/konsumen."
    icon="arrow-uturn-left" :action="route('item-return.store')" submitLabel="Simpan" width="xl">

    {{-- Field: Tipe Pengembalian (menentukan sumber data barang & arah stok) --}}
    <x-fi.field label="Tipe Pengembalian" for="addReturnType" required
        hint="Retur Barang Masuk mengurangi stok; Retur Barang Keluar menambah stok.">
        <x-fi.select name="return_type" id="addReturnType" placeholder="-- Pilih Tipe --" required
            :options="[
                'masuk' => 'Retur Barang Masuk — dikembalikan ke supplier',
                'keluar' => 'Retur Barang Keluar — kembali dari proyek/konsumen',
            ]" />
    </x-fi.field>

    {{-- Field: Barang (dropdown dengan pencarian; isi mengikuti tipe) --}}
    <x-fi.field label="Barang" for="addItemSearch" required>
        <div class="relative">
            <input type="text" id="addItemSearch" class="fi-control cursor-pointer pe-9"
                placeholder="-- Pilih Barang --" autocomplete="off" readonly>
            <x-fi.icon name="chevron-down" class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

            <div id="add-item-dropdown"
                class="absolute z-50 mt-1 hidden w-full overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-gray-950/5">
                <div class="border-b border-gray-100 p-2">
                    <div class="relative">
                        <x-fi.icon name="magnifying-glass" class="pointer-events-none absolute start-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                        <input type="text" id="addItemFilter" class="fi-control ps-10"
                            placeholder="Ketik ID / nama barang..." autocomplete="off" aria-label="Cari barang">
                    </div>
                </div>
                <div id="add-item-options" class="max-h-60 overflow-y-auto p-1"></div>
                <div id="add-item-load-more" class="hidden border-t border-gray-100 p-2 text-center">
                    <button type="button" class="text-sm font-semibold text-primary hover:underline">Muat lebih banyak</button>
                </div>
                <div id="add-item-no-results" class="hidden px-3 py-6 text-center text-sm text-gray-500">
                    Tidak ada barang. Pastikan tipe pengembalian sudah dipilih.
                </div>
            </div>
        </div>
        <input type="hidden" name="id_item" id="addItemId">
        <input type="hidden" name="id_stock_in" id="addStockInId">
        <input type="hidden" name="id_stock_out" id="addStockOutId">
    </x-fi.field>

    {{-- Jumlah & Tanggal --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <x-fi.field label="Jumlah" for="addQuantity" required>
            <x-fi.input type="number" id="addQuantity" name="quantity" :value="old('quantity', 0)" min="1"
                suffix="unit" required />
            <p id="addQuantityWarning" class="hidden text-sm text-red-600">
                <x-fi.icon name="exclamation-circle" class="-mt-0.5 inline h-4 w-4" />
                Jumlah melebihi batas yang dapat dikembalikan
            </p>
            <p id="addAvailableStock" class="text-sm text-gray-500 empty:hidden"></p>
        </x-fi.field>

        <x-fi.field label="Tanggal" for="addDate" required>
            <x-fi.input type="date" id="addDate" name="date" :value="old('date', date('Y-m-d'))" required />
        </x-fi.field>
    </div>

    {{-- Field: Alasan --}}
    <x-fi.field label="Alasan" for="addReason">
        <x-fi.input id="addReason" name="reason" :value="old('reason')" placeholder="Mis. rusak, tidak sesuai pesanan" />
    </x-fi.field>

    {{-- Field: Keterangan --}}
    <x-fi.field label="Keterangan" for="addNotes">
        <x-fi.textarea id="addNotes" name="notes" rows="3"
            placeholder="Masukkan keterangan (opsional)">{{ old('notes') }}</x-fi.textarea>
    </x-fi.field>
</x-fi.modal>
