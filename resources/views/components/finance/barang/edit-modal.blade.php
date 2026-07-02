{{-- Modal Edit Invoice Item --}}
<x-modal id="editModal-{{ $invoice->invoice_number }}" title="Edit Invoice Item"
    action="{{ route('item-invoice.update', $invoice->invoice_number) }}" method="PUT" buttonText="Update">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">No Invoice</label>
        <input type="text" value="{{ $invoice->invoice_number }}"
            class="w-full border rounded p-2 bg-surface-hover cursor-not-allowed" readonly>
        <p class="text-xs text-text-secondary mt-1">No Invoice tidak dapat diubah</p>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Invoice <span class="text-error">*</span></label>
        <input type="date" name="invoice_date" value="{{ $invoice->invoice_date->format('Y-m-d') }}"
            class="w-full border rounded p-2" required>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kepada <span class="text-error">*</span></label>
        <input type="text" name="recipient" value="{{ $invoice->recipient }}" class="w-full border rounded p-2"
            required>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Hal / Keterangan</label>
        <input type="text" name="regarding" value="{{ $invoice->regarding }}" class="w-full border rounded p-2"
            placeholder="Opsional">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Proyek <span class="text-error">*</span></label>
        <textarea name="project_description" class="w-full border rounded p-2" rows="2" required>{{ $invoice->project_description }}</textarea>
    </div>

    <div id="barang-items-container-edit-{{ $invoice->invoice_number }}" class="mb-4">
        <label class="block text-text-primary font-semibold mb-2">Item-Item Invoice <span
                class="text-error">*</span></label>
        <div id="barang-items-list-edit-{{ $invoice->invoice_number }}" class="space-y-3">
            @php
                $existingItems = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
            @endphp
            @foreach ($existingItems as $index => $item)
                <div class="barang-item-row mb-3 p-3 border rounded bg-surface-secondary"
                    data-index="{{ $index }}">
                    <div class="flex items-center gap-2 mb-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="barang-from-stock accent-primary"
                                name="items[{{ $index }}][from_stock]" value="1"
                                {{ !empty($item['from_stock']) ? 'checked' : '' }}>
                            <span class="text-sm">Dari Stok</span>
                        </label>
                    </div>

                    <div class="relative mb-2 barang-select-wrapper"
                        style="display: {{ !empty($item['from_stock']) ? 'block' : 'none' }};">
                        <select name="items[{{ $index }}][id_item]"
                            class="barang-item-select w-full border rounded p-2">
                            <option value="">-- Pilih Barang --</option>
                            @foreach ($items as $stockItem)
                                <option value="{{ $stockItem->id_item }}" data-name="{{ $stockItem->name_item }}"
                                    data-capital="{{ $stockItem->capital_price }}"
                                    data-selling="{{ $stockItem->selling_price }}"
                                    data-stock="{{ $stockItem->quantity }}"
                                    {{ ($item['id_item'] ?? null) == $stockItem->id_item ? 'selected' : '' }}>
                                    {{ $stockItem->name_item }} (Stok: {{ $stockItem->quantity }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <input type="text" name="items[{{ $index }}][name_item]"
                        value="{{ $item['name_item'] ?? '' }}" class="barang-item-name w-full border rounded p-2 mb-2"
                        placeholder="Nama Barang *" required {{ !empty($item['from_stock']) ? 'readonly' : '' }}>

                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" name="items[{{ $index }}][quantity]"
                            value="{{ $item['quantity'] ?? 0 }}" class="barang-item-qty border rounded p-2"
                            placeholder="Qty *" min="1" required>
                        <input type="text" inputmode="numeric" name="items[{{ $index }}][capital_price]"
                            value="Rp {{ number_format($item['capital_price'] ?? 0, 0, ',', '.') }}"
                            class="barang-item-capital border rounded p-2" placeholder="Harga Modal *" required
                            {{ !empty($item['from_stock']) ? 'readonly' : '' }}>
                        <input type="text" inputmode="numeric" name="items[{{ $index }}][selling_price]"
                            value="Rp {{ number_format($item['selling_price'] ?? 0, 0, ',', '.') }}"
                            class="barang-item-selling border rounded p-2" placeholder="Harga Jual *" required
                            {{ !empty($item['from_stock']) ? 'readonly' : '' }}>
                    </div>

                    <button type="button"
                        class="remove-barang-item mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                        <i class="fa-solid fa-trash"></i> Hapus Item
                    </button>
                </div>
            @endforeach
        </div>
        <button type="button"
            class="add-barang-item bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover w-full mt-2"
            data-target="barang-items-list-edit-{{ $invoice->invoice_number }}">
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
                    @foreach ($items as $stockItem)
                        <option value="{{ $stockItem->id_item }}" data-name="{{ $stockItem->name_item }}"
                            data-capital="{{ $stockItem->capital_price }}"
                            data-selling="{{ $stockItem->selling_price }}" data-stock="{{ $stockItem->quantity }}">
                            {{ $stockItem->name_item }} (Stok: {{ $stockItem->quantity }})
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
