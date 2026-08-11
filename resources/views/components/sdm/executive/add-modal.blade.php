{{-- ============================================================
     Modal Tambah Petinggi
     Form untuk menambah data petinggi baru ke dalam sistem.
     Fields:
       - name (required)    : Nama petinggi, maks 150 karakter
       - position (opsional): Jabatan, maks 150 karakter
       - signature_image (opsional): Gambar tanda tangan
     ============================================================ --}}
<x-modal id="addModal" title="Tambah Petinggi" action="{{ route('executive.store') }}" method="POST"
    buttonText="Simpan" enctype="multipart/form-data">

    {{-- Nama Petinggi (wajib diisi) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Petinggi <span class="text-error">*</span></label>
        <input type="text" name="name" class="w-full border rounded p-2" placeholder="Masukkan nama petinggi" required
            maxlength="150" oninvalid="this.setCustomValidity('Nama petinggi tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Jabatan (opsional) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jabatan</label>
        <input type="text" name="position" class="w-full border rounded p-2" placeholder="cth: Direktur Utama"
            maxlength="150">
    </div>

    {{-- Tanda Tangan (opsional) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanda Tangan</label>
        <input type="file" name="signature_image" id="signature-image-input"
            accept="image/jpeg,image/png,image/gif,image/webp"
            class="w-full border rounded p-2"
            onchange="previewSignature(this, 'signature-image-preview')">
        <p class="text-xs text-text-secondary mt-1">Gambar tanda tangan (JPG/PNG/GIF/WEBP, maks 5MB). PNG
            transparan didukung.</p>
        <img id="signature-image-preview" alt="Pratinjau tanda tangan" class="hidden mt-2 h-16 w-auto max-w-full border border-border rounded bg-white object-contain">
    </div>
</x-modal>
