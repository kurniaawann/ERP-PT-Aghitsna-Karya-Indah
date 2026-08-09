{{-- Modal Tambah Kwintansi --}}
<x-modal id="addModal" title="Tambah Kwintansi" action="{{ route('kwintansi.store') }}" method="POST" buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Sudah Terima Dari <span class="text-error">*</span></label>
        <input type="text" name="received_from" class="w-full border rounded p-2" placeholder="Masukkan nama pemberi"
            required maxlength="255" oninvalid="this.setCustomValidity('Sudah terima dari tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Uang Pembayaran <span class="text-error">*</span></label>
        <textarea name="payment_for" class="w-full border rounded p-2" rows="3"
            placeholder="Masukkan keterangan pembayaran" required
            oninvalid="this.setCustomValidity('Uang pembayaran tidak boleh kosong')" oninput="this.setCustomValidity('')"></textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Jumlah (Rp) <span class="text-error">*</span></label>
            <input type="text" inputmode="numeric" name="amount" class="w-full border rounded p-2" placeholder="Rp 0"
                required oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')"
                oninput="formatCurrencyInput(this); this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Sisa (Rp)</label>
            <input type="text" inputmode="numeric" name="remaining" class="w-full border rounded p-2" placeholder="Rp 0"
                oninput="formatCurrencyInput(this)">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
            <input type="date" name="kwintansi_date" class="w-full border rounded p-2" required
                value="{{ date('Y-m-d') }}" oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
                oninput="this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Lokasi</label>
            <input type="text" name="location" class="w-full border rounded p-2" placeholder="Depok (default)"
                maxlength="100">
            <small class="text-gray-500 text-xs">Kosongkan jika di Depok</small>
        </div>
    </div>
</x-modal>
