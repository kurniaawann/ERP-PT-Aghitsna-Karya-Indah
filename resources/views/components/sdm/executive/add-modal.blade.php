{{-- ============================================================
     Modal Tambah Penandatangan (gaya Filament)
     Form untuk menambah data penandatangan baru ke dalam sistem.
     Fields:
       - name (required)    : Nama penandatangan, maks 150 karakter
       - position (opsional): Jabatan, maks 150 karakter
       - signature_image (opsional): Gambar tanda tangan
     JS: previewSignature() (resources/js/pages/sdm/executive/index.js)
     ============================================================ --}}
<x-fi.modal id="addModal" heading="Tambah Penandatangan" icon="pencil-square" :action="route('executive.store')"
    enctype="multipart/form-data">

    {{-- Nama Penandatangan (wajib diisi) --}}
    <x-fi.field label="Nama Penandatangan" for="add-executive-name" required>
        <x-fi.input id="add-executive-name" name="name" placeholder="Masukkan nama penandatangan" required maxlength="150"
            oninvalid="this.setCustomValidity('Nama penandatangan tidak boleh kosong')" oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Jabatan (opsional) --}}
    <x-fi.field label="Jabatan" for="add-executive-position">
        <x-fi.input id="add-executive-position" name="position" placeholder="cth: Direktur Utama" maxlength="150" />
    </x-fi.field>

    {{-- Tanda Tangan (opsional) --}}
    <x-fi.field label="Tanda Tangan" for="signature-image-input"
        hint="Gambar tanda tangan (JPG/PNG/GIF/WEBP, maks 5MB). PNG transparan didukung.">
        <input type="file" name="signature_image" id="signature-image-input"
            accept="image/jpeg,image/png,image/gif,image/webp"
            onchange="previewSignature(this, 'signature-image-preview')"
            class="block w-full rounded-lg bg-white text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/10 file:me-3 file:cursor-pointer file:rounded-s-lg file:border-0 file:border-e file:border-gray-200 file:bg-gray-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary">
        <img id="signature-image-preview" alt="Pratinjau tanda tangan"
            class="hidden h-16 w-auto max-w-full rounded-lg bg-white object-contain p-1 ring-1 ring-gray-950/10">
    </x-fi.field>
</x-fi.modal>
