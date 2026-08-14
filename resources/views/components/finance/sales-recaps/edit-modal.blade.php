{{-- ==================== Modal Edit Rekap Penjualan ==================== --}}
<x-modal id="editModal-{{ $sale->id_sales_recap }}" title="Edit Rekap Penjualan"
    action="{{ route('recap-sales.update', $sale->id_sales_recap) }}" method="PUT" buttonText="Update">

    {{-- ID Laporan (readonly) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">ID Laporan</label>
        <input type="text" value="{{ $sale->id_sales_recap }}"
            class="w-full border rounded p-2 bg-surface-hover cursor-not-allowed" readonly>
    </div>

    {{-- Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="date" value="{{ $sale->date->format('Y-m-d') }}"
            class="w-full border rounded p-2" required oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Nama Proyek --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Proyek</label>
        <input type="text" name="name_proyek" value="{{ $sale->name_proyek }}" class="w-full border rounded p-2"
            maxlength="255">
    </div>

    {{-- Daftar Item --}}
    <div id="items-container-edit-{{ $sale->id_sales_recap }}" class="mb-4">
        <label class="block text-text-primary font-semibold mb-2">Item-Item Barang</label>
        <div id="items-list-edit-{{ $sale->id_sales_recap }}">
            @php
                $existingItems = is_string($sale->items) ? json_decode($sale->items, true) : $sale->items;
            @endphp
            @foreach ($existingItems as $index => $item)
                @php
                    $isFromStock = !empty($item['from_stock']) && $item['from_stock'] !== 'false';
                    $displayName = $item['name_item'] ?? '';
                    if ($isFromStock && empty($displayName) && !empty($item['id_item'])) {
                        $stockItem = $items->firstWhere('id_item', $item['id_item']);
                        $displayName = $stockItem ? $stockItem->name_item : '';
                    }
                @endphp
                <div class="item-row-edit mb-3 p-3 border rounded bg-surface-secondary"
                    data-index="{{ $index }}">
                    {{-- Checkbox Dari Stok --}}
                    <div class="flex items-center gap-2 mb-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="item-from-stock-edit accent-primary"
                                {{ $isFromStock ? 'checked' : '' }}>
                            <span class="text-sm">Dari Stok</span>
                        </label>
                    </div>

                {{-- Searchable Dropdown --}}
                <div class="relative mb-2 item-select-wrapper-edit"
                    style="display: {{ $isFromStock ? 'block' : 'none' }};">
                    <input type="text"
                        class="item-search-input-edit w-full border rounded-lg p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light"
                        placeholder="Cari barang..." autocomplete="off"
                        value="{{ $displayName }}">
                        <i class="fa-solid fa-search absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>

                        <div
                            class="item-dropdown-edit absolute z-50 w-full bg-white border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">
                            <div class="item-options-edit">
                                <div class="p-2 text-sm text-text-secondary hover:bg-surface-secondary cursor-pointer border-b"
                                    data-value="">
                                    -- Pilih Barang --
                                </div>
                                @foreach ($items as $stockItem)
                                    <div class="p-3 hover:bg-primary-light cursor-pointer border-b border-border-light item-option-edit"
                                        data-value="{{ $stockItem->id_item }}" data-name="{{ $stockItem->name_item }}"
                                        data-capital="{{ $stockItem->capital_price }}"
                                        data-selling="{{ $stockItem->selling_price }}"
                                        data-stock="{{ $stockItem->quantity }}"
                                        data-search="{{ strtolower($stockItem->name_item) }}">
                                        <div class="font-medium text-text-heading">
                                            {{ $stockItem->name_item }}
                                        </div>
                                        <div class="text-xs text-text-secondary mt-1">
                                            Stok: <span
                                                class="font-semibold text-primary">{{ $stockItem->quantity }}</span>
                                            unit
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="no-results-edit p-4 text-center text-sm text-text-secondary hidden">
                                <i class="fa-solid fa-search mb-2 text-2xl text-text-placeholder"></i>
                                <p>Tidak ada barang ditemukan</p>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" class="item-select-hidden-edit" value="{{ $item['id_item'] ?? '' }}">

                    {{-- Nama Barang --}}
                    <input type="text" name="items[{{ $index }}][name_item]"
                        value="{{ $displayName }}" class="item-name-edit w-full border rounded p-2 mb-2"
                        placeholder="Nama Barang *"
                        style="display: {{ $isFromStock ? 'none' : 'block' }};"
                        {{ $isFromStock ? 'readonly' : '' }}
                        {{ $isFromStock ? '' : 'required' }}
                        oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">

                    {{-- Qty, Harga Modal, Harga Jual --}}
                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" name="items[{{ $index }}][quantity]"
                            value="{{ $item['quantity'] ?? 0 }}" class="item-qty-edit border rounded p-2"
                            placeholder="Qty *" required min="1"
                            oninvalid="this.setCustomValidity('Qty tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="text" inputmode="numeric" name="items[{{ $index }}][capital_price]"
                            value="Rp {{ number_format($item['capital_price'] ?? 0, 0, ',', '.') }}"
                            class="item-capital-edit border rounded p-2" placeholder="Rp 0"
                            {{ $isFromStock ? 'readonly' : '' }}
                            required oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')"
                            oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                        <input type="text" inputmode="numeric" name="items[{{ $index }}][selling_price]"
                            value="Rp {{ number_format($item['selling_price'] ?? 0, 0, ',', '.') }}"
                            class="item-selling-edit border rounded p-2" placeholder="Rp 0"
                            {{ $isFromStock ? 'readonly' : '' }}
                            required oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')"
                            oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                    </div>

                    {{-- Stock Warning --}}
                    <p class="stock-warning-edit text-error text-sm mt-2 hidden">
                        <span class="font-semibold">Peringatan Stok:</span> <span
                            class="stock-warning-text-edit">Stok Barang Tidak Cukup! Silahkan Sesuaikan Dengan Stok
                            Yang Tersedia.</span>
                    </p>

                    {{-- Price Warning --}}
                    <p class="price-warning-edit text-error text-sm mt-2 hidden">
                        <span class="font-semibold">Peringatan:</span> Harga modal tidak boleh lebih besar atau sama
                        dengan harga jual!
                    </p>

                    {{-- Hidden fields --}}
                    <input type="hidden" name="items[{{ $index }}][from_stock]" class="from-stock-hidden"
                        value="{{ $isFromStock ? 'true' : 'false' }}">
                    <input type="hidden" name="items[{{ $index }}][id_item]" class="id-item-hidden"
                        value="{{ $item['id_item'] ?? '' }}">

                    {{-- Tombol Hapus Item --}}
                    <button type="button"
                        class="remove-item-edit mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                        <i class="fa-solid fa-trash"></i> Hapus Item
                    </button>
                </div>
            @endforeach
        </div>

        {{-- Tombol Tambah Item --}}
        <button type="button"
            class="add-item-edit bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover w-full"
            data-sale-id="{{ $sale->id_sales_recap }}">
            <i class="fa-solid fa-plus"></i> Tambah Item
        </button>
    </div>
</x-modal>
