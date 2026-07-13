{{-- Modal Edit Barang Masuk --}}
<x-modal id="editModal-{{ $record->id_stock_in }}" title="Edit Barang Masuk"
    action="{{ route('stock-in.update', $record->id_stock_in) }}" method="PUT" buttonText="Update">

    {{-- Field: Tanggal Penerimaan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="date" value="{{ $record->date->format('Y-m-d') }}"
            class="w-full border rounded p-2" required>
    </div>

    {{-- Container Item Barang --}}
    <div id="items-container-{{ $record->id_stock_in }}" class="mb-4">
        <label class="block text-text-primary font-semibold mb-2">Item-Item Barang <span
                class="text-error">*</span></label>

        {{-- Daftar Item --}}
        <div id="items-list-{{ $record->id_stock_in }}" class="items-list">
            <div class="item-row mb-3 p-3 border rounded bg-surface-secondary">

                {{-- Checkbox: Dari Stok --}}
                <div class="flex items-center gap-2 mb-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" class="item-from-stock-edit accent-primary" checked>
                        <span class="text-sm">Dari Stok</span>
                    </label>
                </div>

                {{-- Dropdown Pencarian Barang --}}
                <div class="relative mb-2 item-select-wrapper">
                    <input type="text"
                        class="item-search-input w-full border rounded-lg p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light"
                        placeholder="Cari barang..." autocomplete="off" value="{{ $record->item->name_item }}">
                    <i class="fa-solid fa-search absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>

                    <div
                        class="item-dropdown absolute z-50 w-full bg-white border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">
                        <div class="item-options">
                            <div class="p-2 text-sm text-text-secondary hover:bg-surface-secondary cursor-pointer border-b"
                                data-value="">
                                -- Pilih Barang --
                            </div>
                            @foreach ($allItems as $itemOption)
                                <div class="p-3 hover:bg-primary-light cursor-pointer border-b border-border-light item-option"
                                    data-value="{{ $itemOption->id_item }}" data-name="{{ $itemOption->name_item }}"
                                    data-capital="{{ $itemOption->capital_price }}"
                                    data-stock="{{ $itemOption->quantity }}"
                                    data-search="{{ strtolower($itemOption->name_item) }}"
                                    {{ $itemOption->id_item === $record->id_item ? 'selected' : '' }}>
                                    <div class="font-medium text-text-heading">{{ $itemOption->name_item }}</div>
                                    <div class="text-xs text-text-secondary mt-1">
                                        Stok: <span
                                            class="font-semibold text-primary">{{ $itemOption->quantity }}</span>
                                        unit
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="no-results p-4 text-center text-sm text-text-secondary hidden">
                            <i class="fa-solid fa-search mb-2 text-2xl text-text-placeholder"></i>
                            <p>Tidak ada barang ditemukan</p>
                        </div>
                    </div>
                </div>

                <input type="hidden" class="item-select-hidden" value="{{ $record->id_item }}">

                {{-- Field: Nama Barang --}}
                <input type="text" class="item-name w-full border rounded p-2 mb-2" placeholder="Nama Barang *"
                    required oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
                    oninput="this.setCustomValidity('')" value="{{ $record->item->name_item }}">

                {{-- Field: Qty & Harga Modal --}}
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" class="item-qty border rounded p-2" placeholder="Qty *" required
                        min="1" value="{{ $record->quantity }}"
                        oninvalid="this.setCustomValidity('Qty tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" class="item-capital border rounded p-2"
                        placeholder="Harga Modal *" required min="0"
                        value="{{ number_format($record->capital_price, 0, ',', '.') }}"
                        oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')"
                        oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                </div>

                {{-- Tombol Hapus Item (sembunyi di edit, hanya 1 item per record) --}}
                <button type="button"
                    class="remove-item mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full"
                    style="display: none;">
                    <i class="fa-solid fa-trash"></i> Hapus Item
                </button>
            </div>
        </div>

        {{-- Tombol Tambah Item (sembunyi di edit) --}}
        <button type="button" class="add-item"
            class="bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover w-full" style="display: none;">
            <i class="fa-solid fa-plus"></i> Tambah Item
        </button>
    </div>

    {{-- Field: Keterangan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan</label>
        <textarea name="notes" class="w-full border rounded p-2" rows="3">{{ $record->notes }}</textarea>
    </div>

    {{-- Hidden Input: JSON Items --}}
    <input type="hidden" name="items" id="items-json-{{ $record->id_stock_in }}" value="[]">
</x-modal>
