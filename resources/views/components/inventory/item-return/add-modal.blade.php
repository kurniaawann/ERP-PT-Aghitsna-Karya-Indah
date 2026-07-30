{{-- Modal Tambah Return Barang --}}
<x-modal id="addModal" title="Tambah Return Barang" action="{{ route('item-return.store') }}" method="POST"
    buttonText="Simpan">

    {{-- Field: Tipe Return --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tipe Return <span class="text-error">*</span></label>
        <select name="return_type" id="addReturnType"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required>
            <option value="">-- Pilih Tipe --</option>
            <option value="masuk">Return Barang Masuk (dari Supplier)</option>
            <option value="keluar">Return Barang Keluar (dari Proyek/Konsumen)</option>
        </select>
    </div>

    {{-- Field: Barang (Searchable) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Barang <span class="text-error">*</span></label>
        <div class="relative">
            <input type="text" id="addItemSearch"
                class="w-full border border-border-strong rounded p-2 pr-8 bg-surface-base text-text-input focus:border-primary focus:ring-1 focus:ring-primary"
                placeholder="-- Pilih Barang --" autocomplete="off" readonly>
            <i class="fa-solid fa-chevron-down absolute right-2.5 top-2.5 text-xs text-text-tertiary pointer-events-none"></i>
            <div id="add-item-dropdown"
                class="absolute z-50 w-full bg-surface-base border border-border-strong rounded-lg shadow-lg mt-1 hidden">
                <div class="p-2 border-b border-border-light">
                    <input type="text" id="addItemFilter"
                        class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input text-sm focus:border-primary focus:ring-1 focus:ring-primary"
                        placeholder="Ketik untuk mencari..." autocomplete="off">
                </div>
                <div id="add-item-options" class="max-h-60 overflow-y-auto divide-y divide-border-light"></div>
                <div id="add-item-load-more" class="p-2 text-center border-t border-border-light hidden">
                    <button type="button" class="text-sm text-primary hover:underline font-medium">Muat Lebih Banyak</button>
                </div>
                <div id="add-item-no-results" class="p-4 text-center text-sm text-text-secondary hidden">
                    Tidak ada data ditemukan
                </div>
            </div>
        </div>
        <input type="hidden" name="id_item" id="addItemId">
        <input type="hidden" name="id_stock_in" id="addStockInId">
        <input type="hidden" name="id_stock_out" id="addStockOutId">
    </div>

    {{-- Field: Jumlah --}}
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

    {{-- Field: Alasan Return --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Alasan Return</label>
        <input type="text" name="reason" value="{{ old('reason') }}"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Rusak, Tidak sesuai, dll">
    </div>

    {{-- Field: Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required>
    </div>

    {{-- Field: Keterangan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan</label>
        <textarea name="notes" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            rows="3" placeholder="Masukkan keterangan...">{{ old('notes') }}</textarea>
    </div>
</x-modal>
