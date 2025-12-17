{{-- Modal Edit Tanda Terima Dokumen --}}
<x-modal id="editModal-{{ $document->id_document }}" title="Edit Tanda Terima Dokumen"
    action="{{ route('document-receipt.update', $document->id_document) }}" method="POST" buttonText="Update">
    @method('PUT')

    <div class="mb-3">
        <label class="block text-text-primary mb-1">ID Dokumen</label>
        <input type="text" name="id_document" class="w-full border rounded p-2 bg-surface-hover"
            value="{{ $document->id_document }}" readonly>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Telah Terima Dari <span class="text-error">*</span></label>
        <input type="text" name="received_from" class="w-full border rounded p-2"
            placeholder="Masukkan nama pemberi dokumen" value="{{ $document->received_from }}" required maxlength="255"
            oninvalid="this.setCustomValidity('Telah terima dari tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Perihal <span class="text-error">*</span></label>
        <input type="text" name="regarding" class="w-full border rounded p-2" placeholder="Masukkan perihal dokumen"
            value="{{ $document->regarding }}" required maxlength="255"
            oninvalid="this.setCustomValidity('Perihal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Berupa <span class="text-error">*</span></label>
        <input type="text" name="form_of" class="w-full border rounded p-2" placeholder="Masukkan berupa apa dokumen"
            value="{{ $document->form_of }}" required maxlength="255"
            oninvalid="this.setCustomValidity('Berupa tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Hari / Tanggal <span class="text-error">*</span></label>
            <input type="date" name="receipt_date" class="w-full border rounded p-2"
                value="{{ $document->receipt_date ? \Carbon\Carbon::parse($document->receipt_date)->format('Y-m-d') : '' }}"
                required oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
                oninput="this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Jam <span class="text-error">*</span></label>
            <input type="time" name="receipt_time" class="w-full border rounded p-2"
                value="{{ \Carbon\Carbon::parse($document->receipt_time)->format('H:i') }}" required
                oninvalid="this.setCustomValidity('Jam tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Lokasi</label>
        <input type="text" name="location" class="w-full border rounded p-2" placeholder="Depok (default)"
            value="{{ $document->location }}" maxlength="100">
        <small class="text-gray-500 text-xs">Kosongkan jika di Depok</small>
    </div>
</x-modal>
