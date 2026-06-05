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
        <select name="id_item" id="addItemSelect"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required>
            <option value="">-- Pilih Barang --</option>
        </select>
        <!-- Hidden reference ID -->
        <input type="hidden" name="id_stock_in" id="addStockInId">
        <input type="hidden" name="id_stock_out" id="addStockOutId">
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
