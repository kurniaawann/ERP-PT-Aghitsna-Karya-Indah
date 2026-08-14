{{-- Modal Edit Barang --}}
<x-modal id="editModal-{{ $item->id_item }}" title="Edit Barang" action="{{ route('item.update', $item->id_item) }}"
    method="PUT" buttonText="Update">

    {{-- Field: ID Barang (Readonly) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">ID Barang</label>
        <input type="text" value="{{ $item->id_item }}"
            class="w-full border rounded p-2 bg-surface-hover cursor-not-allowed" readonly>
        <p class="text-xs text-text-secondary mt-1">ID Barang tidak dapat diubah</p>
    </div>

    {{-- Field: Nama Barang --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Barang <span class="text-error">*</span></label>
        <input type="text" name="name_item" value="{{ $item->name_item }}" class="w-full border rounded p-2" required
            maxlength="255" oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Jumlah Stok --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah <span class="text-error">*</span></label>
        <input type="number" name="quantity" value="{{ $item->quantity }}" class="w-full border rounded p-2" required
            min="0" oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Harga Modal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Harga Modal <span class="text-error">*</span></label>
        <input type="text" name="capital_price"
            value="Rp {{ number_format($item->capital_price ?? 0, 0, ',', '.') }}" class="w-full border rounded p-2"
            required inputmode="numeric" id="edit-capital-price-{{ $item->id_item }}"
            oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Harga Jual --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Harga Jual <span class="text-error">*</span></label>
        <input type="text" name="selling_price"
            value="Rp {{ number_format($item->selling_price ?? 0, 0, ',', '.') }}" class="w-full border rounded p-2"
            required inputmode="numeric" id="edit-selling-price-{{ $item->id_item }}"
            oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')" oninput="this.setCustomValidity('')">

        {{-- Peringatan: Harga Jual harus lebih besar dari Harga Modal --}}
        <p id="edit-price-warning-{{ $item->id_item }}" class="text-error text-sm mt-1 hidden">
            <span class="font-semibold">Peringatan:</span> Harga modal tidak boleh lebih besar atau sama dengan harga
            jual!
        </p>
    </div>

    {{-- Field: Keterangan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan</label>
        <input type="text" name="keterangan" value="{{ $item->keterangan }}" class="w-full border rounded p-2" placeholder="Masukkan keterangan"
            maxlength="255">
    </div>
</x-modal>
