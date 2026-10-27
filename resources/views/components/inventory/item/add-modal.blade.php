{{-- Modal Tambah Barang (gaya Filament) --}}
<x-fi.modal id="addModal" heading="Tambah Barang" description="Lengkapi data barang baru di bawah ini."
    icon="cube" :action="route('item.store')" method="POST" submitLabel="Simpan">

    {{-- Field: Nama Barang --}}
    <x-fi.field label="Nama Barang" for="add-name-item" required>
        <x-fi.input id="add-name-item" name="name_item" placeholder="Masukkan nama barang" required maxlength="255"
            oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')" oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Jumlah Stok --}}
    <x-fi.field label="Jumlah" for="add-quantity" required>
        <x-fi.input type="number" id="add-quantity" name="quantity" value="0" placeholder="Masukkan jumlah"
            prefix-icon="archive-box" required min="0"
            oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')" oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Harga Modal --}}
    <x-fi.field label="Harga Modal" for="add-capital-price" required>
        <x-fi.input name="capital_price" value="Rp 0" placeholder="Rp 0" prefix-icon="banknotes"
            required inputmode="numeric" id="add-capital-price"
            oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Harga Jual --}}
    <x-fi.field label="Harga Jual" for="add-selling-price" required>
        <x-fi.input name="selling_price" value="Rp 0" placeholder="Rp 0" prefix-icon="banknotes"
            required inputmode="numeric" id="add-selling-price"
            oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')" oninput="this.setCustomValidity('')" />

        {{-- Peringatan: Harga Jual harus lebih besar dari Harga Modal --}}
        <p id="add-price-warning" class="hidden text-sm text-red-600">
            <x-fi.icon name="exclamation-circle" class="me-1 inline-block h-4 w-4 align-[-3px]" />
            <span class="font-semibold">Peringatan:</span> Harga modal tidak boleh lebih besar atau sama dengan harga
            jual!
        </p>
    </x-fi.field>

    {{-- Field: Keterangan --}}
    <x-fi.field label="Keterangan" for="add-keterangan">
        <x-fi.input id="add-keterangan" name="keterangan" placeholder="Masukkan keterangan" maxlength="255" />
    </x-fi.field>
</x-fi.modal>
