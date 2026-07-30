{{-- ============================================================
     Modal Tambah Divisi
     Form untuk menambah data divisi baru ke dalam sistem.
     Fields:
       - name (required): Nama divisi, maks 100 karakter
       - description (optional): Deskripsi divisi, maks 500 karakter
     ============================================================ --}}
<x-modal id="addModal" title="Tambah Divisi" action="{{ route('division.store') }}" method="POST" buttonText="Simpan">

    {{-- Nama Divisi (wajib diisi) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Divisi <span class="text-error">*</span></label>
        <input type="text" name="name" class="w-full border rounded p-2" placeholder="Masukkan nama divisi" required
            maxlength="100" oninvalid="this.setCustomValidity('Nama divisi tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Deskripsi Divisi (opsional) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Deskripsi</label>
        <textarea name="description" class="w-full border rounded p-2" placeholder="Deskripsi divisi" rows="3"
            maxlength="500"></textarea>
    </div>
</x-modal>
