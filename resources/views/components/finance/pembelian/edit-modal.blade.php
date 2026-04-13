{{-- Modal Edit Faktur Pembelian --}}
<x-modal id="editModal-{{ $invoice->id }}" title="Edit Faktur Pembelian #{{ $invoice->id }}"
    action="{{ route('purchase-invoice.update', $invoice->id) }}" method="PUT" buttonText="Update">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">ID Faktur</label>
        <input type="text" value="{{ $invoice->id }}"
            class="w-full border rounded p-2 bg-surface-hover cursor-not-allowed" readonly>
        <p class="text-xs text-text-secondary mt-1">ID Faktur tidak dapat diubah</p>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="date" value="{{ $invoice->date->format('Y-m-d') }}"
            class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Material <span class="text-error">*</span></label>
        <input type="text" name="material_name" value="{{ $invoice->material_name }}" class="w-full border rounded p-2"
            required oninvalid="this.setCustomValidity('Nama material tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">NPWP <span class="text-error">*</span></label>
        <input type="text" name="npwp" value="{{ $invoice->npwp }}" class="w-full border rounded p-2"
            required oninvalid="this.setCustomValidity('NPWP tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kode Nomor Seri Pajak <span class="text-error">*</span></label>
        <input type="text" name="tax_number_code" value="{{ $invoice->tax_number_code }}" class="w-full border rounded p-2"
            required oninvalid="this.setCustomValidity('Kode nomor seri pajak tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Barang <span class="text-error">*</span></label>
        <input type="text" name="item_name" value="{{ $invoice->item_name }}" class="w-full border rounded p-2"
            required oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Harga Jual (Rp) <span class="text-error">*</span></label>
        <input type="number" name="selling_price" value="{{ $invoice->selling_price }}" class="w-full border rounded p-2"
            min="0" required oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">PPN Pengenaan Pajak (Rp) <span class="text-error">*</span></label>
        <input type="number" name="ppn_tax" value="{{ $invoice->ppn_tax }}" class="w-full border rounded p-2"
            min="0" required oninvalid="this.setCustomValidity('PPN pajak tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan</label>
        <textarea name="notes" class="w-full border rounded p-2" rows="2"
            placeholder="Keterangan tambahan (opsional)">{{ $invoice->notes }}</textarea>
    </div>

</x-modal>
