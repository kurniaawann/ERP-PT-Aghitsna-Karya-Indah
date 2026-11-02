{{-- ============================================================
     Modal Tambah Divisi (gaya Filament)
     Form untuk menambah data divisi baru ke dalam sistem.
     Fields:
       - name (required): Nama divisi, maks 100 karakter
       - description (optional): Deskripsi divisi, maks 500 karakter
     ============================================================ --}}
<x-fi.modal id="addModal" heading="Tambah Divisi" icon="rectangle-group" :action="route('division.store')">

    {{-- Nama Divisi (wajib diisi) --}}
    <x-fi.field label="Nama Divisi" for="add-division-name" required>
        <x-fi.input id="add-division-name" name="name" placeholder="Masukkan nama divisi" required maxlength="100"
            oninvalid="this.setCustomValidity('Nama divisi tidak boleh kosong')" oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Deskripsi Divisi (opsional) --}}
    <x-fi.field label="Deskripsi" for="add-division-description">
        <x-fi.textarea id="add-division-description" name="description" placeholder="Deskripsi divisi" rows="3"
            maxlength="500"></x-fi.textarea>
    </x-fi.field>
</x-fi.modal>
