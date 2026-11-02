{{-- ============================================================
     Modal Edit Divisi (gaya Filament)
     Form untuk memperbarui data divisi yang sudah ada (PUT).
     Fields:
       - name (required): Nama divisi, maks 100 karakter
       - description (optional): Deskripsi divisi, maks 500 karakter
     ============================================================ --}}
<x-fi.modal id="editModal-{{ $division->id }}" heading="Edit Divisi" :description="$division->name" icon="pencil-square"
    :action="route('division.update', $division->id)" method="PUT" submitLabel="Update">

    {{-- Nama Divisi (wajib diisi) --}}
    <x-fi.field label="Nama Divisi" for="edit-division-name-{{ $division->id }}" required>
        <x-fi.input id="edit-division-name-{{ $division->id }}" name="name" :value="$division->name" required maxlength="100" />
    </x-fi.field>

    {{-- Deskripsi Divisi (opsional) --}}
    <x-fi.field label="Deskripsi" for="edit-division-description-{{ $division->id }}">
        <x-fi.textarea id="edit-division-description-{{ $division->id }}" name="description" rows="3"
            maxlength="500">{{ $division->description }}</x-fi.textarea>
    </x-fi.field>
</x-fi.modal>
