{{-- ============================================================
     Modal Edit Penandatangan (gaya Filament)
     Form untuk memperbarui data penandatangan yang sudah ada (PUT).
     Fields:
       - name (required)    : Nama penandatangan, maks 150 karakter
       - position (opsional): Jabatan, maks 150 karakter
       - signature_image (opsional): Gambar tanda tangan baru
       - remove_signature (opsional): hapus tanda tangan yang ada
     JS: previewSignature(), toggleRemoveSignature()
         (resources/js/pages/sdm/executive/index.js)
     ============================================================ --}}
@php($id = $executive->id)
<x-fi.modal id="editModal-{{ $id }}" heading="Edit Penandatangan" :description="$executive->name" icon="pencil-square"
    :action="route('executive.update', $id)" method="PUT" enctype="multipart/form-data" submitLabel="Update">

    {{-- Nama Penandatangan (wajib diisi) --}}
    <x-fi.field label="Nama Penandatangan" for="edit-executive-name-{{ $id }}" required>
        <x-fi.input id="edit-executive-name-{{ $id }}" name="name" :value="$executive->name" required maxlength="150" />
    </x-fi.field>

    {{-- Jabatan (opsional) --}}
    <x-fi.field label="Jabatan" for="edit-executive-position-{{ $id }}">
        <x-fi.input id="edit-executive-position-{{ $id }}" name="position" :value="$executive->position" maxlength="150" />
    </x-fi.field>

    {{-- Tanda Tangan (opsional) --}}
    <x-fi.field label="Tanda Tangan" for="signature-image-input-{{ $id }}"
        hint="Kosongkan jika ingin mempertahankan tanda tangan yang ada. PNG transparan didukung.">
        <input type="file" name="signature_image" id="signature-image-input-{{ $id }}"
            accept="image/jpeg,image/png,image/gif,image/webp"
            onchange="previewSignature(this, 'signature-image-preview-{{ $id }}')"
            class="block w-full rounded-lg bg-white text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/10 file:me-3 file:cursor-pointer file:rounded-s-lg file:border-0 file:border-e file:border-gray-200 file:bg-gray-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-50">
        <img id="signature-image-preview-{{ $id }}" alt="Pratinjau tanda tangan"
            class="hidden h-16 w-auto max-w-full rounded-lg bg-white object-contain p-1 ring-1 ring-gray-950/10">
    </x-fi.field>

    {{-- Tanda tangan saat ini + opsi hapus --}}
    @if ($executive->signature_image)
        <div class="grid gap-y-2 rounded-lg bg-gray-50 p-4 ring-1 ring-inset ring-gray-950/5">
            <p class="text-sm font-medium text-gray-950">Tanda tangan saat ini</p>
            <img id="current-signature-{{ $id }}" src="{{ asset('storage/' . $executive->signature_image) }}"
                alt="Tanda tangan {{ $executive->name }}"
                class="h-16 w-auto max-w-full justify-self-start rounded-lg bg-white object-contain p-1 ring-1 ring-gray-950/10">
            <label for="remove-signature-{{ $id }}" class="flex cursor-pointer items-center gap-2 text-sm text-red-600">
                <input type="checkbox" name="remove_signature" value="1" id="remove-signature-{{ $id }}"
                    class="fi-checkbox" onchange="toggleRemoveSignature(this, '{{ $id }}')">
                Hapus tanda tangan yang ada
            </label>
        </div>
    @endif
</x-fi.modal>
