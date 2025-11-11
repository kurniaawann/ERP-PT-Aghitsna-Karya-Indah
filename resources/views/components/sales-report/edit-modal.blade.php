{{-- Edit Modal for Sales Report - Component for individual sale --}}
{{-- Usage: @include('components.sales-report.edit-modal', ['sale' => $sale, 'items' => $items]) --}}

<x-modal id="editModal-{{ $sale->id_sales_report }}" title="Edit Laporan Penjualan"
    action="{{ route('sales-report.update', $sale->id_sales_report) }}" method="PUT" buttonText="Update">

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">ID Laporan</label>
        <input type="text" value="{{ $sale->id_sales_report }}"
            class="w-full border rounded p-2 bg-gray-100 cursor-not-allowed" readonly>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="date" value="{{ $sale->date->format('Y-m-d') }}"
            class="w-full border rounded p-2" required>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Nama Proyek <span class="text-error">*</span></label>
        <input type="text" name="name_proyek" value="{{ $sale->name_proyek }}" class="w-full border rounded p-2"
            required>
    </div>

    <div id="items-container-edit-{{ $sale->id_sales_report }}" class="mb-4">
        <label class="block text-gray-700 font-semibold mb-2">Item-Item Barang</label>
        <div id="items-list-edit-{{ $sale->id_sales_report }}">
            @php
                $existingItems = is_string($sale->items) ? json_decode($sale->items, true) : $sale->items;
            @endphp
            @foreach ($existingItems as $index => $item)
                <div class="item-row-edit mb-3 p-3 border rounded bg-gray-50" data-index="{{ $index }}">
                    {{-- Checkbox Dari Stok --}}
                    <div class="flex items-center gap-2 mb-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="item-from-stock-edit accent-primary"
                                {{ !empty($item['from_stock']) && $item['from_stock'] !== 'false' ? 'checked' : '' }}>
                            <span class="text-sm">Dari Stok</span>
                        </label>
                    </div>

                    {{-- Custom Searchable Dropdown --}}
                    <div class="relative mb-2 item-select-wrapper-edit"
                        style="display: {{ !empty($item['from_stock']) && $item['from_stock'] !== 'false' ? 'block' : 'none' }};">
                        <input type="text"
                            class="item-search-input-edit w-full border rounded-lg p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light"
                            placeholder="Cari barang..." autocomplete="off"
                            value="{{ !empty($item['id_item']) ? $item['name_item'] : '' }}">
                        <i class="fa-solid fa-search absolute right-3 top-3 text-gray-400 pointer-events-none"></i>

                        <div
                            class="item-dropdown-edit absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">
                            <div class="item-options-edit">
                                <div class="p-2 text-sm text-gray-500 hover:bg-gray-50 cursor-pointer border-b"
                                    data-value="">
                                    -- Pilih Barang --
                                </div>
                                @foreach ($items as $stockItem)
                                    <div class="p-3 hover:bg-primary-light cursor-pointer border-b border-gray-100 item-option-edit"
                                        data-value="{{ $stockItem->id_item }}" data-name="{{ $stockItem->name_item }}"
                                        data-capital="{{ $stockItem->capital_price }}"
                                        data-selling="{{ $stockItem->selling_price }}"
                                        data-stock="{{ $stockItem->quantity }}"
                                        data-search="{{ strtolower($stockItem->name_item) }}">
                                        <div class="font-medium text-gray-800">
                                            {{ $stockItem->name_item }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Stok: <span
                                                class="font-semibold text-primary">{{ $stockItem->quantity }}</span>
                                            unit
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="no-results-edit p-4 text-center text-sm text-gray-500 hidden">
                                <i class="fa-solid fa-search mb-2 text-2xl text-gray-300"></i>
                                <p>Tidak ada barang ditemukan</p>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" class="item-select-hidden-edit" value="{{ $item['id_item'] ?? '' }}">

                    {{-- Input Nama Barang --}}
                    <input type="text" name="items[{{ $index }}][name_item]"
                        value="{{ $item['name_item'] ?? '' }}" class="item-name-edit w-full border rounded p-2 mb-2"
                        placeholder="Nama Barang *"
                        {{ !empty($item['from_stock']) && $item['from_stock'] !== 'false' ? 'readonly' : '' }}
                        required>

                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" name="items[{{ $index }}][quantity]"
                            value="{{ $item['quantity'] ?? 0 }}" class="item-qty-edit border rounded p-2"
                            placeholder="Qty *" required min="1">
                        <input type="number" name="items[{{ $index }}][capital_price]"
                            value="{{ $item['capital_price'] ?? 0 }}" class="item-capital-edit border rounded p-2"
                            placeholder="Harga Modal *"
                            {{ !empty($item['from_stock']) && $item['from_stock'] !== 'false' ? 'readonly' : '' }}
                            required min="0">
                        <input type="number" name="items[{{ $index }}][selling_price]"
                            value="{{ $item['selling_price'] ?? 0 }}" class="item-selling-edit border rounded p-2"
                            placeholder="Harga Jual *"
                            {{ !empty($item['from_stock']) && $item['from_stock'] !== 'false' ? 'readonly' : '' }}
                            required min="0">
                    </div>

                    <input type="hidden" name="items[{{ $index }}][from_stock]" class="from-stock-hidden"
                        value="{{ !empty($item['from_stock']) && $item['from_stock'] !== 'false' ? 'true' : 'false' }}">
                    <input type="hidden" name="items[{{ $index }}][id_item]" class="id-item-hidden"
                        value="{{ $item['id_item'] ?? '' }}">

                    <button type="button"
                        class="remove-item-edit mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                        <i class="fa-solid fa-trash"></i> Hapus Item
                    </button>
                </div>
            @endforeach
        </div>
        <button type="button"
            class="add-item-edit bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover w-full"
            data-sale-id="{{ $sale->id_sales_report }}">
            <i class="fa-solid fa-plus"></i> Tambah Item
        </button>
    </div>
</x-modal>
