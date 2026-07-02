{{-- Modal Tambah Invoice Item --}}
<x-modal id="addModal" title="Tambah Invoice Item" action="{{ route('item-invoice.store') }}" method="POST"
    buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Invoice <span class="text-error">*</span></label>
        <input type="date" name="invoice_date" class="w-full border rounded p-2" required>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kepada <span class="text-error">*</span></label>
        <input type="text" name="recipient" class="w-full border rounded p-2" placeholder="Nama penerima invoice"
            required>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Hal / Keterangan</label>
        <input type="text" name="regarding" class="w-full border rounded p-2" placeholder="Opsional">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Proyek <span class="text-error">*</span></label>
        <textarea name="project_description" class="w-full border rounded p-2" rows="2"
            placeholder="Contoh: Pengiriman material ke proyek A" required></textarea>
    </div>

    <div id="barang-items-container-add" class="mb-4">
        <label class="block text-text-primary font-semibold mb-2">Item-Item Invoice <span
                class="text-error">*</span></label>
        <div id="barang-items-list-add" class="space-y-3">
            <div class="barang-item-row mb-3 p-3 border rounded bg-surface-secondary">
                <div class="flex items-center gap-2 mb-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" class="barang-from-stock accent-primary" name="items[0][from_stock]"
                            value="1">
                        <span class="text-sm">Dari Stok</span>
                    </label>
                </div>

                <div class="relative mb-2 barang-select-wrapper" style="display: none;">
                    <select name="items[0][id_item]" class="barang-item-select w-full border rounded p-2">
                        <option value="">-- Pilih Barang --</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id_item }}" data-name="{{ $item->name_item }}"
                                data-capital="{{ $item->capital_price }}" data-selling="{{ $item->selling_price }}"
                                data-stock="{{ $item->quantity }}">
                                {{ $item->name_item }} (Stok: {{ $item->quantity }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <input type="text" name="items[0][name_item]" class="barang-item-name w-full border rounded p-2 mb-2"
                    placeholder="Nama Barang *" required>

                <div class="grid grid-cols-3 gap-2">
                    <input type="number" name="items[0][quantity]" class="barang-item-qty border rounded p-2"
                        placeholder="Qty *" min="1" value="1" required>
                    <input type="text" inputmode="numeric" name="items[0][capital_price]"
                        class="barang-item-capital border rounded p-2" placeholder="Harga Modal *" required>
                    <input type="text" inputmode="numeric" name="items[0][selling_price]"
                        class="barang-item-selling border rounded p-2" placeholder="Harga Jual *" required>
                </div>

                <button type="button"
                    class="remove-barang-item mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                    <i class="fa-solid fa-trash"></i> Hapus Item
                </button>
            </div>
        </div>

        <button type="button"
            class="add-barang-item bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover w-full mt-2"
            data-target="barang-items-list-add">
            <i class="fa-solid fa-plus"></i> Tambah Item
        </button>
    </div>

    <template class="barang-item-template">
        <div class="barang-item-row mb-3 p-3 border rounded bg-surface-secondary">
            <div class="flex items-center gap-2 mb-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="barang-from-stock accent-primary" value="1">
                    <span class="text-sm">Dari Stok</span>
                </label>
            </div>

            <div class="relative mb-2 barang-select-wrapper" style="display: none;">
                <select class="barang-item-select w-full border rounded p-2">
                    <option value="">-- Pilih Barang --</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id_item }}" data-name="{{ $item->name_item }}"
                            data-capital="{{ $item->capital_price }}" data-selling="{{ $item->selling_price }}"
                            data-stock="{{ $item->quantity }}">
                            {{ $item->name_item }} (Stok: {{ $item->quantity }})
                        </option>
                    @endforeach
                </select>
            </div>

            <input type="text" class="barang-item-name w-full border rounded p-2 mb-2" placeholder="Nama Barang *"
                required>

            <div class="grid grid-cols-3 gap-2">
                <input type="number" class="barang-item-qty border rounded p-2" placeholder="Qty *" min="1"
                    value="1" required>
                <input type="text" inputmode="numeric" class="barang-item-capital border rounded p-2"
                    placeholder="Harga Modal *" required>
                <input type="text" inputmode="numeric" class="barang-item-selling border rounded p-2"
                    placeholder="Harga Jual *" required>
            </div>

            <button type="button"
                class="remove-barang-item mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                <i class="fa-solid fa-trash"></i> Hapus Item
            </button>
        </div>
    </template>
</x-modal>
