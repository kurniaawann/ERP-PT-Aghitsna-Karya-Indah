{{-- Modal Tambah Barang --}}
<x-modal id="addModal" title="Tambah Barang" action="{{ route('item.store') }}" method="POST" buttonText="Simpan">

    {{-- Field: Nama Barang --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Barang <span class="text-error">*</span></label>
        <input type="text" name="name_item" class="w-full border rounded p-2" placeholder="Masukkan nama barang"
            required maxlength="255" oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Jumlah Stok --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah <span class="text-error">*</span></label>
        <input type="number" name="quantity" value="0" class="w-full border rounded p-2"
            placeholder="Masukkan jumlah" required min="0"
            oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Harga Modal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Harga Modal <span class="text-error">*</span></label>
        <input type="text" name="capital_price" value="Rp 0" class="w-full border rounded p-2" placeholder="Rp 0"
            required inputmode="numeric" id="add-capital-price"
            oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Harga Jual --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Harga Jual <span class="text-error">*</span></label>
        <input type="text" name="selling_price" value="Rp 0" class="w-full border rounded p-2" placeholder="Rp 0"
            required inputmode="numeric" id="add-selling-price"
            oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')" oninput="this.setCustomValidity('')">

        {{-- Peringatan: Harga Jual harus lebih besar dari Harga Modal --}}
        <p id="add-price-warning" class="text-error text-sm mt-1 hidden">
            <span class="font-semibold">Peringatan:</span> Harga modal tidak boleh lebih besar atau sama dengan harga
            jual!
        </p>
    </div>
</x-modal>
