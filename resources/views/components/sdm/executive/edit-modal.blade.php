{{-- ============================================================
     Modal Edit Petinggi
     Form untuk memperbarui data petinggi yang sudah ada.
     Menggunakan method PUT untuk update.
     Fields:
       - name (required)    : Nama petinggi, maks 150 karakter
       - position (opsional): Jabatan, maks 150 karakter
       - signature_image (opsional): Gambar tanda tangan baru
     ============================================================ --}}
<x-modal id="editModal-{{ $executive->id }}" title="Edit Petinggi" action="{{ route('executive.update', $executive->id) }}"
    method="PUT" buttonText="Update" enctype="multipart/form-data">

    {{-- Nama Petinggi (wajib diisi) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Petinggi <span class="text-error">*</span></label>
        <input type="text" name="name" class="w-full border rounded p-2" value="{{ $executive->name }}" required
            maxlength="150">
    </div>

    {{-- Jabatan (opsional) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jabatan</label>
        <input type="text" name="position" class="w-full border rounded p-2" value="{{ $executive->position }}"
            maxlength="150">
    </div>

    {{-- Tanda Tangan (opsional) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanda Tangan</label>
        <input type="file" name="signature_image" id="signature-image-input-{{ $executive->id }}"
            accept="image/jpeg,image/png,image/gif,image/webp"
            class="w-full border rounded p-2"
            onchange="previewSignature(this, 'signature-image-preview-{{ $executive->id }}')">
        <p class="text-xs text-text-secondary mt-1">Kosongkan jika ingin mempertahankan tanda tangan yang
            ada. PNG transparan didukung.</p>
        <img id="signature-image-preview-{{ $executive->id }}" alt="Pratinjau tanda tangan"
            class="hidden mt-2 h-16 w-auto max-w-full border border-border rounded bg-white object-contain">
        @if ($executive->signature_image)
            <p class="text-xs text-text-label mt-2">Tanda tangan saat ini:</p>
            <img id="current-signature-{{ $executive->id }}"
                src="{{ asset('storage/' . $executive->signature_image) }}"
                alt="Tanda tangan {{ $executive->name }}"
                class="mt-1 h-16 w-auto max-w-full border border-border rounded bg-white object-contain">
            <div class="mt-2 flex items-center gap-2">
                <input type="checkbox" name="remove_signature" value="1"
                    id="remove-signature-{{ $executive->id }}"
                    class="w-4 h-4 accent-error cursor-pointer"
                    onchange="toggleRemoveSignature(this, '{{ $executive->id }}')">
                <label for="remove-signature-{{ $executive->id }}"
                    class="text-sm text-error cursor-pointer">Hapus tanda tangan yang ada</label>
            </div>
        @endif
    </div>
</x-modal>
