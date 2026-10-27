{{-- Modal Tambah Barang Masuk (gaya Filament): tanggal + repeater kartu item + keterangan.
     Hook JS (incoming-items/index.js): #addModal form, #items-list, #add-item,
     #items-json (JSON item diisi saat submit), name="date", name="notes". --}}
<x-fi.modal id="addModal" heading="Tambah Barang Masuk"
    description="Stok dan harga modal rata-rata barang akan diperbarui otomatis."
    icon="inbox-arrow-down" :action="route('stock-in.store')" method="POST" submitLabel="Simpan" width="2xl">

    {{-- Field: Tanggal Penerimaan --}}
    <x-fi.field label="Tanggal" for="add-date" required>
        <x-fi.input type="date" id="add-date" name="date" :value="date('Y-m-d')" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Repeater Item Barang (kartu baru ditambah lewat tombol "Tambah Item" — JS) --}}
    <div id="items-container" class="grid gap-y-2">
        <span class="text-sm font-medium leading-6 text-gray-950">
            Item Barang<sup class="ms-0.5 font-medium text-red-600">*</sup>
        </span>

        <div id="items-list" class="fi-repeater-list grid gap-y-3">
            @include('components.inventory.stock-in.item-row', ['items' => $items])
        </div>

        <x-fi.button id="add-item" color="gray" icon="plus" class="w-full">Tambah Item</x-fi.button>
    </div>

    {{-- Field: Keterangan --}}
    <x-fi.field label="Keterangan" for="add-notes">
        <x-fi.textarea id="add-notes" name="notes" rows="3" placeholder="Masukkan keterangan (opsional)" />
    </x-fi.field>

    {{-- Hidden Input: JSON Items --}}
    <input type="hidden" name="items" id="items-json" value="[]">
</x-fi.modal>
