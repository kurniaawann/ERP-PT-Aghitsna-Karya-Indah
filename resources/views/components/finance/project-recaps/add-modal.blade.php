{{-- Modal Tambah Rekap Proyek --}}
<x-modal id="addModal" title="Tambah Rekap Proyek" action="{{ route('recap-proyek.store') }}" method="POST"
    buttonText="Simpan" enctype="multipart/form-data">

    {{-- Nama Proyek --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Proyek <span class="text-error">*</span></label>
        <input type="text" name="project_name" class="w-full border rounded p-2" placeholder="Contoh: Proyek Ruko Jl. Cemara"
            required maxlength="255" oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Total RAB --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Total RAB <span class="text-error">*</span></label>
        <input type="text" inputmode="numeric" name="total_rab" class="w-full border rounded p-2 total-rab-input"
            placeholder="Contoh: 100000000" required min="0"
            oninvalid="this.setCustomValidity('Total RAB tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Upload File Design --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">File Design</label>
        <input type="file" name="design_file" accept="image/jpeg,image/png,image/gif,image/webp,image/bmp"
            class="w-full border rounded p-2">
        <p class="text-xs text-text-secondary mt-1">Opsional. Format: JPG, PNG, GIF, WEBP, BMP. Maksimal 5 MB.</p>
    </div>
</x-modal>
