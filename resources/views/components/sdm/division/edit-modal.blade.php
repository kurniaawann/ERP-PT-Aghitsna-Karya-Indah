{{-- ============================================================
     Modal Edit Divisi
     Form untuk memperbarui data divisi yang sudah ada.
     Menggunakan method PUT untuk update.
     Fields:
       - name (required): Nama divisi, maks 100 karakter
       - description (optional): Deskripsi divisi, maks 500 karakter
     ============================================================ --}}
<x-modal id="editModal-{{ $division->id }}" title="Edit Divisi" action="{{ route('division.update', $division->id) }}"
    method="PUT" buttonText="Update">

    {{-- Nama Divisi (wajib diisi) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Divisi <span class="text-error">*</span></label>
        <input type="text" name="name" class="w-full border rounded p-2" value="{{ $division->name }}" required
            maxlength="100">
    </div>

    {{-- Deskripsi Divisi (opsional) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Deskripsi</label>
        <textarea name="description" class="w-full border rounded p-2" rows="3" maxlength="500">{{ $division->description }}</textarea>
    </div>
</x-modal>
