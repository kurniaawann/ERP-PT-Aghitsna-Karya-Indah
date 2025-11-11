{{-- Modal Tambah Item --}}
<x-modal id="addModal" title="Tambah Barang" action="{{ route('item.store') }}" method="POST" buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Nama Barang <span class="text-error">*</span></label>
        <input type="text" name="name_item" class="w-full border rounded p-2" placeholder="Masukkan nama barang"
            required maxlength="255">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Jumlah <span class="text-error">*</span></label>
        <input type="number" name="quantity" value="0" class="w-full border rounded p-2"
            placeholder="Masukkan jumlah" required min="0">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Harga Modal <span class="text-error">*</span></label>
        <input type="number" name="capital_price" value="0" class="w-full border rounded p-2"
            placeholder="Masukkan harga modal" required min="0" step="0.01" id="add-capital-price">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Harga Jual <span class="text-error">*</span></label>
        <input type="number" name="selling_price" value="0" class="w-full border rounded p-2"
            placeholder="Masukkan harga jual" required min="0" step="0.01" id="add-selling-price">
        <p id="add-price-warning" class="text-error text-sm mt-1 hidden">
            <span class="font-semibold">⚠️ Peringatan:</span> Harga modal tidak boleh lebih besar atau sama dengan harga
            jual!
        </p>
    </div>
</x-modal>
