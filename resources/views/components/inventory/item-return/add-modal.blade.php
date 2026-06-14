{{-- Modal Tambah Return Barang --}}
<x-modal id="addModal" title="Tambah Return Barang" action="{{ route('item-return.store') }}" method="POST"
    buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tipe Return <span class="text-error">*</span></label>
        <select name="return_type" id="addReturnType"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required>
            <option value="">-- Pilih Tipe --</option>
            <option value="masuk">Return Barang Masuk (dari Supplier)</option>
            <option value="keluar">Return Barang Keluar (dari Proyek/Konsumen)</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Barang <span class="text-error">*</span></label>
        
        {{-- Hidden inputs used for form submission --}}
        <input type="hidden" name="id_item" id="addItemId">
        <input type="hidden" name="id_stock_in" id="addStockInId">
        <input type="hidden" name="id_stock_out" id="addStockOutId">

        {{-- Custom dropdown with infinite scroll and search --}}
        <div class="relative">
            <button type="button" id="addItemDropdownBtn" disabled
                class="w-full px-3 py-2 border border-border-strong rounded bg-surface-base flex items-center justify-between focus:outline-none focus:ring-1 focus:ring-primary disabled:bg-surface-secondary disabled:cursor-not-allowed transition-colors">
                <span id="addItemDropdownLabel" class="text-sm text-text-primary">-- Pilih Tipe Return Dulu --</span>
                <span class="text-text-secondary text-xs">▼</span>
            </button>

            <div id="addItemDropdownMenu"
                class="absolute z-50 mt-1 w-full bg-surface-base border border-border-light rounded shadow-lg hidden">
                <div class="p-2 border-b border-border-light">
                    <input type="text" id="addItemSearchInput"
                        class="w-full px-3 py-2 text-sm border border-border-strong rounded bg-surface-base text-text-input focus:outline-none focus:ring-1 focus:ring-primary"
                        placeholder="Cari nama/kode barang...">
                </div>
                <div id="addItemDropdownList" class="max-h-60 overflow-y-auto">
                    <div class="p-2 text-sm text-text-secondary text-center" id="addItemLoadingPlaceholder">
                        Memuat data...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah <span class="text-error">*</span></label>
        <input type="number" id="addQuantity" name="quantity" value="{{ old('quantity', 0) }}"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" min="1"
            required>
        <p id="addQuantityWarning" class="text-error text-sm mt-1 hidden">
            <i class="fa-solid fa-circle-exclamation"></i> Jumlah return tidak boleh melebihi stok yang tersedia
        </p>
        <p id="addAvailableStock" class="text-primary text-sm mt-1 text-xs"></p>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Alasan Return</label>
        <input type="text" name="alasan" value="{{ old('alasan') }}"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Rusak, Tidak sesuai, dll">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan</label>
        <textarea name="keterangan" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            rows="3" placeholder="Masukkan keterangan...">{{ old('keterangan') }}</textarea>
    </div>
</x-modal>
