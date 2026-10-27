{{-- Modal Edit Barang Masuk (gaya Filament): satu record = satu item.
     Hook JS (incoming-items/index.js): [id^="editModal-"] form,
     #items-list-{id} (kartu item), #items-json-{id} (JSON item diisi saat submit). --}}
<x-fi.modal id="editModal-{{ $record->id_stock_in }}" heading="Edit Barang Masuk"
    :description="'ID: ' . $record->id_stock_in . ' — perubahan jumlah/harga ikut menyesuaikan stok barang.'"
    icon="pencil-square" :action="route('stock-in.update', $record->id_stock_in)" method="PUT" submitLabel="Update"
    width="2xl">

    {{-- Field: Tanggal Penerimaan --}}
    <x-fi.field label="Tanggal" for="edit-date-{{ $record->id_stock_in }}" required>
        <x-fi.input type="date" id="edit-date-{{ $record->id_stock_in }}" name="date"
            :value="$record->date->format('Y-m-d')" required />
    </x-fi.field>

    {{-- Item Barang (satu kartu) --}}
    <div id="items-container-{{ $record->id_stock_in }}">
        <div id="items-list-{{ $record->id_stock_in }}" class="items-list">
            @include('components.inventory.stock-in.item-row', ['items' => $allItems, 'record' => $record])
        </div>
    </div>

    {{-- Field: Keterangan --}}
    <x-fi.field label="Keterangan" for="edit-notes-{{ $record->id_stock_in }}">
        <x-fi.textarea id="edit-notes-{{ $record->id_stock_in }}" name="notes" rows="3"
            placeholder="Masukkan keterangan (opsional)">{{ $record->notes }}</x-fi.textarea>
    </x-fi.field>

    {{-- Hidden Input: JSON Items --}}
    <input type="hidden" name="items" id="items-json-{{ $record->id_stock_in }}" value="[]">
</x-fi.modal>
