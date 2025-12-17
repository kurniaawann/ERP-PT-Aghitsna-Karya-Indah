{{-- Modal Tambah Tanda Terima Dokumen --}}
<x-modal id="addModal" title="Tambah Tanda Terima Dokumen" action="{{ route('document-receipt.store') }}" method="POST"
    buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Telah Terima Dari <span class="text-error">*</span></label>
        <input type="text" name="received_from" class="w-full border rounded p-2"
            placeholder="Masukkan nama pemberi dokumen" required maxlength="255"
            oninvalid="this.setCustomValidity('Telah terima dari tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Perihal <span class="text-error">*</span></label>
        <input type="text" name="regarding" class="w-full border rounded p-2" placeholder="Masukkan perihal dokumen"
            required maxlength="255" oninvalid="this.setCustomValidity('Perihal tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Berupa <span class="text-error">*</span></label>
        <input type="text" name="form_of" class="w-full border rounded p-2" placeholder="Masukkan berupa apa dokumen"
            required maxlength="255" oninvalid="this.setCustomValidity('Berupa tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Hari / Tanggal <span class="text-error">*</span></label>
            <input type="date" name="receipt_date" class="w-full border rounded p-2" required
                oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Jam <span class="text-error">*</span></label>
            <input type="time" name="receipt_time" class="w-full border rounded p-2" required
                oninvalid="this.setCustomValidity('Jam tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Lokasi</label>
        <input type="text" name="location" class="w-full border rounded p-2" placeholder="Depok (default)"
            maxlength="100">
        <small class="text-gray-500 text-xs">Kosongkan jika di Depok</small>
    </div>
</x-modal>
