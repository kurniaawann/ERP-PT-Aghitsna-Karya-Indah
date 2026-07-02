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
        <label class="block text-text-primary mb-1">Proyek <span class="text-error">*</span></label>
        <textarea name="project_description" class="w-full border rounded p-2" rows="2"
            placeholder="" required></textarea>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Hal / Keterangan</label>
        <input type="text" name="regarding" class="w-full border rounded p-2" placeholder="Opsional">
    </div>

    <div id="barang-items-container-add" class="mb-4">
        <label class="block text-text-primary font-semibold mb-2">Item-Item Invoice <span
                class="text-error">*</span></label>
        <div id="barang-items-list-add" class="space-y-3">
            <div class="barang-item-row mb-3 p-3 border rounded bg-surface-secondary">
                <div class="flex items-center gap-2 mb-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" class="barang-from-stock accent-primary">
                        <span class="text-sm">Dari Stok</span>
                    </label>
                </div>

                <div class="relative mb-2 barang-select-wrapper" style="display: none;">
                    <input type="text"
                        class="barang-search-input w-full border rounded-lg p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light"
                        placeholder="Cari barang..." autocomplete="off">
                    <i class="fa-solid fa-search absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>

                    <div
                        class="barang-dropdown absolute z-50 w-full bg-white border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">
                        <div class="barang-options">
                            <div class="p-2 text-sm text-text-secondary hover:bg-surface-secondary cursor-pointer border-b"
                                data-value="">
                                -- Pilih Barang --
                            </div>
                            @foreach ($items as $item)
                                <div class="p-3 hover:bg-primary-light cursor-pointer border-b border-border-light barang-option"
                                    data-value="{{ $item->id_item }}" data-name="{{ $item->name_item }}"
                                    data-capital="{{ $item->capital_price }}" data-selling="{{ $item->selling_price }}"
                                    data-stock="{{ $item->quantity }}"
                                    data-search="{{ strtolower($item->name_item) }}">
                                    <div class="font-medium text-text-heading">{{ $item->name_item }}</div>
                                    <div class="text-xs text-text-secondary mt-1">
                                        Stok: <span class="font-semibold text-primary">{{ $item->quantity }}</span>
                                        unit
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="barang-no-results p-4 text-center text-sm text-text-secondary hidden">
                            <i class="fa-solid fa-search mb-2 text-2xl text-text-placeholder"></i>
                            <p>Tidak ada barang ditemukan</p>
                        </div>
                    </div>
                </div>

                <input type="hidden" class="barang-select-hidden">

                <input type="text" class="barang-item-name w-full border rounded p-2 mb-2"
                    placeholder="Nama Barang *" required
                    oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">

                <div class="grid grid-cols-3 gap-2">
                    <input type="number" class="barang-item-qty border rounded p-2" placeholder="Qty *" required
                        min="1" value="1"
                        oninvalid="this.setCustomValidity('Qty tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" class="barang-item-capital border rounded p-2"
                        placeholder="Rp 0" required
                        oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')"
                        oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" class="barang-item-selling border rounded p-2"
                        placeholder="Rp 0" required
                        oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')"
                        oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                </div>

                <p class="barang-stock-warning text-error text-sm mt-2 hidden">
                    <span class="font-semibold">⚠️ Peringatan Stok:</span> <span class="barang-stock-warning-text">Stok
                        Barang Tidak Cukup! Silahkan Sesuaikan Dengan Stok Yang Tersedia.</span>
                </p>

                <p class="barang-price-warning text-error text-sm mt-2 hidden">
                    <span class="font-semibold">⚠️ Peringatan:</span> Harga modal tidak boleh lebih besar atau sama
                    dengan harga jual!
                </p>

                <button type="button"
                    class="remove-barang-item mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                    <i class="fa-solid fa-trash"></i> Hapus Item
                </button>
            </div>
        </div>

        <button type="button"
            class="add-barang-item bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover w-full mt-2">
            <i class="fa-solid fa-plus"></i> Tambah Item
        </button>
    </div>

    <input type="hidden" name="items" id="barang-items-json" value="[]">
</x-modal>
