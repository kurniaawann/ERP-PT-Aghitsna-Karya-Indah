{{-- ==================== Modal Tambah Rekap Penjualan ==================== --}}
<x-modal id="addModal" title="Tambah Rekap Penjualan" action="{{ route('recap-sales.store') }}" method="POST"
    buttonText="Simpan">

    {{-- Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="date" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Nama Proyek --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Proyek <span class="text-error">*</span></label>
        <input type="text" name="name_proyek" class="w-full border rounded p-2" placeholder="Contoh: PROYEK KAHFI"
            required maxlength="255" oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Daftar Item --}}
    <div id="items-container" class="mb-4">
        <label class="block text-text-primary font-semibold mb-2">Item-Item Barang <span
                class="text-error">*</span></label>
        <div id="items-list">
            <div class="item-row mb-3 p-3 border rounded bg-surface-secondary">
                {{-- Checkbox Dari Stok --}}
                <div class="flex items-center gap-2 mb-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" class="item-from-stock accent-primary">
                        <span class="text-sm">Dari Stok</span>
                    </label>
                </div>

                {{-- Searchable Dropdown (muncul saat dari stok dicentang) --}}
                <div class="relative mb-2 item-select-wrapper" style="display: none;">
                    <input type="text"
                        class="item-search-input w-full border rounded-lg p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light"
                        placeholder="Cari barang..." autocomplete="off">
                    <i class="fa-solid fa-search absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>

                    <div
                        class="item-dropdown absolute z-50 w-full bg-white border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">
                        <div class="item-options">
                            <div class="p-2 text-sm text-text-secondary hover:bg-surface-secondary cursor-pointer border-b"
                                data-value="">
                                -- Pilih Barang --
                            </div>
                            @foreach ($items as $item)
                                <div class="p-3 hover:bg-primary-light cursor-pointer border-b border-border-light item-option"
                                    data-value="{{ $item->id_item }}" data-name="{{ $item->name_item }}"
                                    data-capital="{{ $item->capital_price }}" data-selling="{{ $item->selling_price }}"
                                    data-stock="{{ $item->quantity }}" data-search="{{ strtolower($item->name_item) }}">
                                    <div class="font-medium text-text-heading">{{ $item->name_item }}</div>
                                    <div class="text-xs text-text-secondary mt-1">
                                        Stok: <span class="font-semibold text-primary">{{ $item->quantity }}</span>
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

                <input type="hidden" class="item-select-hidden">

                {{-- Nama Barang --}}
                <input type="text" class="item-name w-full border rounded p-2 mb-2" placeholder="Nama Barang *"
                    required oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">

                {{-- Qty, Harga Modal, Harga Jual --}}
                <div class="grid grid-cols-3 gap-2">
                    <input type="number" class="item-qty border rounded p-2" placeholder="Qty *" required
                        min="1" value="1" oninvalid="this.setCustomValidity('Qty tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" class="item-capital border rounded p-2" placeholder="Rp 0"
                        required oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')"
                        oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" class="item-selling border rounded p-2" placeholder="Rp 0"
                        required oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')"
                        oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                </div>

                {{-- Stock Warning --}}
                <p class="stock-warning text-error text-sm mt-2 hidden">
                    <span class="font-semibold">Peringatan Stok:</span> <span class="stock-warning-text">Stok Barang
                        Tidak Cukup! Silahkan Sesuaikan Dengan Stok Yang Tersedia.</span>
                </p>

                {{-- Price Warning --}}
                <p class="price-warning text-error text-sm mt-2 hidden">
                    <span class="font-semibold">Peringatan:</span> Harga modal tidak boleh lebih besar atau sama
                    dengan harga jual!
                </p>

                {{-- Tombol Hapus Item --}}
                <button type="button"
                    class="remove-item mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                    <i class="fa-solid fa-trash"></i> Hapus Item
                </button>
            </div>
        </div>

        {{-- Tombol Tambah Item --}}
        <button type="button" id="add-item"
            class="bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover w-full">
            <i class="fa-solid fa-plus"></i> Tambah Item
        </button>
    </div>

    {{-- Hidden field untuk JSON items --}}
    <input type="hidden" name="items" id="items-json" value="[]">
</x-modal>
