{{-- Modal Tambah Faktur Pembelian --}}
<x-modal id="addModal" title="Tambah Faktur Pembelian" action="{{ route('purchase-invoice.store') }}" method="POST"
    buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="date" value="{{ date('Y-m-d') }}" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Material <span class="text-error">*</span></label>
        <input type="text" name="material_name" class="w-full border rounded p-2"
            placeholder="Contoh: PT. MANGGALA DIPO PRATAMA" required
            oninvalid="this.setCustomValidity('Nama material tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">NPWP <span class="text-error">*</span></label>
        <input type="text" name="npwp" class="w-full border rounded p-2"
            placeholder="Contoh: 80.948.827.3-047.000" required
            oninvalid="this.setCustomValidity('NPWP tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kode Nomor Seri Pajak <span class="text-error">*</span></label>
        <input type="text" name="tax_number_code" class="w-full border rounded p-2"
            placeholder="Contoh: 011.011-23.82258306" required
            oninvalid="this.setCustomValidity('Kode nomor seri pajak tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Barang <span class="text-error">*</span></label>
        <input type="text" name="item_name" class="w-full border rounded p-2" placeholder="Contoh: SEMEN 40 KG"
            required oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Harga Jual (Rp) <span class="text-error">*</span></label>
        <input type="text" id="addSellingPrice" name="selling_price" inputmode="numeric"
            class="w-full border rounded p-2" placeholder="Rp 0" required
            oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Persentase PPN (%) <span class="text-error">*</span></label>
        <input type="text" id="addPpnPercentage" name="ppn_percentage" inputmode="decimal"
            class="w-full border rounded p-2" placeholder="11" value="11" required
            oninvalid="this.setCustomValidity('Persentase PPN tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
        <p class="text-xs text-text-secondary mt-1">Default: 11%. Boleh pakai koma, contoh 11,25</p>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">PPN Pengenaan Pajak (Rp) <span class="text-error">*</span></label>
        <input type="text" id="addPpnTax" name="ppn_tax"
            class="w-full border rounded p-2 bg-surface-hover cursor-not-allowed" placeholder="Rp 0" readonly>
        <p class="text-xs text-text-secondary mt-1">Dihitung otomatis dari harga jual × persentase PPN</p>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan</label>
        <textarea name="notes" class="w-full border rounded p-2" rows="2" placeholder="Keterangan tambahan (opsional)"></textarea>
    </div>

</x-modal>
