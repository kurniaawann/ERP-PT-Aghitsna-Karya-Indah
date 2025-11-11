{{-- Modal Edit Item --}}
<x-modal id="editModal-{{ $item->id_item }}" title="Edit Barang" action="{{ route('item.update', $item->id_item) }}"
    method="PUT" buttonText="Update">

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">ID Barang</label>
        <input type="text" value="{{ $item->id_item }}" class="w-full border rounded p-2 bg-gray-100 cursor-not-allowed"
            readonly>
        <p class="text-xs text-gray-500 mt-1">ID Barang tidak dapat diubah</p>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Nama Barang <span class="text-error">*</span></label>
        <input type="text" name="name_item" value="{{ $item->name_item }}" class="w-full border rounded p-2" required
            maxlength="255">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Jumlah <span class="text-error">*</span></label>
        <input type="number" name="quantity" value="{{ $item->quantity }}" class="w-full border rounded p-2" required
            min="0">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Harga Modal <span class="text-error">*</span></label>
        <input type="number" name="capital_price" value="{{ $item->capital_price ?? 0 }}"
            class="w-full border rounded p-2" required min="0" step="0.01"
            id="edit-capital-price-{{ $item->id_item }}">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Harga Jual <span class="text-error">*</span></label>
        <input type="number" name="selling_price" value="{{ $item->selling_price ?? 0 }}"
            class="w-full border rounded p-2" required min="0" step="0.01"
            id="edit-selling-price-{{ $item->id_item }}">
        <p id="edit-price-warning-{{ $item->id_item }}" class="text-error text-sm mt-1 hidden">
            <span class="font-semibold">⚠️ Peringatan:</span> Harga modal tidak boleh lebih besar atau sama dengan harga
            jual!
        </p>
    </div>
</x-modal>
