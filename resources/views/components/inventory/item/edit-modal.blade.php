{{-- Modal Edit Barang (gaya Filament) --}}
<x-fi.modal id="editModal-{{ $item->id_item }}" heading="Edit Barang" :description="$item->name_item"
    icon="pencil-square" :action="route('item.update', $item->id_item)" method="PUT" submitLabel="Update">

    {{-- Field: ID Barang (Readonly) --}}
    <x-fi.field label="ID Barang" hint="ID Barang tidak dapat diubah">
        <x-fi.input :value="$item->id_item" prefix-icon="lock-closed" readonly />
    </x-fi.field>

    {{-- Field: Nama Barang --}}
    <x-fi.field label="Nama Barang" for="edit-name-item-{{ $item->id_item }}" required>
        <x-fi.input id="edit-name-item-{{ $item->id_item }}" name="name_item" :value="$item->name_item" required
            maxlength="255" oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
            oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Jumlah Stok --}}
    <x-fi.field label="Jumlah" for="edit-quantity-{{ $item->id_item }}" required>
        <x-fi.input type="number" id="edit-quantity-{{ $item->id_item }}" name="quantity" :value="$item->quantity"
            prefix-icon="archive-box" required min="0"
            oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')" oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Harga Modal --}}
    <x-fi.field label="Harga Modal" for="edit-capital-price-{{ $item->id_item }}" required>
        <x-fi.input name="capital_price" value="Rp {{ number_format($item->capital_price ?? 0, 0, ',', '.') }}"
            prefix-icon="banknotes" required inputmode="numeric" id="edit-capital-price-{{ $item->id_item }}"
            oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Harga Jual --}}
    <x-fi.field label="Harga Jual" for="edit-selling-price-{{ $item->id_item }}" required>
        <x-fi.input name="selling_price" value="Rp {{ number_format($item->selling_price ?? 0, 0, ',', '.') }}"
            prefix-icon="banknotes" required inputmode="numeric" id="edit-selling-price-{{ $item->id_item }}"
            oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')" oninput="this.setCustomValidity('')" />

        {{-- Peringatan: Harga Jual harus lebih besar dari Harga Modal --}}
        <p id="edit-price-warning-{{ $item->id_item }}" class="hidden text-sm text-red-600">
            <x-fi.icon name="exclamation-circle" class="me-1 inline-block h-4 w-4 align-[-3px]" />
            <span class="font-semibold">Peringatan:</span> Harga modal tidak boleh lebih besar atau sama dengan harga
            jual!
        </p>
    </x-fi.field>

    {{-- Field: Keterangan --}}
    <x-fi.field label="Keterangan" for="edit-keterangan-{{ $item->id_item }}">
        <x-fi.input id="edit-keterangan-{{ $item->id_item }}" name="keterangan" :value="$item->keterangan"
            placeholder="Masukkan keterangan" maxlength="255" />
    </x-fi.field>
</x-fi.modal>
