{{-- Modal Tambah DO Semen --}}
<x-modal id="addModal" title="Tambah DO Semen" action="{{ route('cement-do.store') }}" method="POST" buttonText="Simpan">

    {{-- Field: Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="tanggal" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Proyek --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Proyek <span class="text-error">*</span></label>
        <input type="text" name="proyek" class="w-full border rounded p-2" placeholder="Masukkan nama proyek"
            required maxlength="255" oninvalid="this.setCustomValidity('Proyek tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Volume --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Volume <span class="text-error">*</span></label>
        <input type="number" name="volume" value="0" class="w-full border rounded p-2"
            placeholder="Masukkan volume" required min="0"
            oninvalid="this.setCustomValidity('Volume tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Satuan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Satuan</label>
        <input type="text" name="satuan" class="w-full border rounded p-2" placeholder="cth: sak / zak"
            maxlength="50">
    </div>

    {{-- Field: Harga --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Harga <span class="text-error">*</span></label>
        <input type="text" name="harga" value="Rp 0" class="w-full border rounded p-2" placeholder="Rp 0"
            required inputmode="numeric" id="add-harga"
            oninvalid="this.setCustomValidity('Harga tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Tanggal Lunas --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Lunas</label>
        <input type="date" name="tanggal_lunas" class="w-full border rounded p-2">
    </div>

    {{-- Field: Harga Modal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Harga Modal <span class="text-error">*</span></label>
        <input type="text" name="harga_modal" value="Rp 0" class="w-full border rounded p-2" placeholder="Rp 0"
            required inputmode="numeric" id="add-harga-modal"
            oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>
</x-modal>
