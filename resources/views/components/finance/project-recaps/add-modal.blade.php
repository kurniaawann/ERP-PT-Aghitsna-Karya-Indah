{{-- Modal Tambah Rekap Proyek (gaya Filament).
     Hook JS (project-recaps/index.js): #addModal form, .total-rab-input
     (format ribuan saat diketik, dibersihkan sebelum submit). --}}
<x-fi.modal id="addModal" heading="Tambah Rekap Proyek" icon="building-office-2" width="xl"
    :action="route('recap-proyek.store')" method="POST" enctype="multipart/form-data" submitLabel="Simpan">

    {{-- Field: Nama Proyek --}}
    <x-fi.field label="Nama Proyek" for="add-project-name" required>
        <x-fi.input id="add-project-name" name="project_name" placeholder="Contoh: Proyek Ruko Jl. Cemara"
            required maxlength="255" oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')"
            oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Lokasi --}}
    <x-fi.field label="Lokasi" for="add-location">
        <x-fi.input id="add-location" name="location" placeholder="Contoh: Depok, Jawa Barat" maxlength="255" />
    </x-fi.field>

    {{-- Field: Total RAB --}}
    <x-fi.field label="Total RAB" for="add-total-rab" required>
        <x-fi.input id="add-total-rab" inputmode="numeric" name="total_rab" class="total-rab-input" prefix="Rp"
            placeholder="Contoh: 100000000" required min="0"
            oninvalid="this.setCustomValidity('Total RAB tidak boleh kosong')" oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Upload File Design --}}
    <x-fi.field label="File Design" for="add-design-file" hint="Opsional. Format: JPG, PNG, GIF, WEBP, BMP. Maksimal 5 MB.">
        <input type="file" id="add-design-file" name="design_file" accept="image/jpeg,image/png,image/gif,image/webp,image/bmp"
            class="block w-full rounded-lg bg-white text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/10 file:me-3 file:cursor-pointer file:rounded-s-lg file:border-0 file:border-e file:border-gray-200 file:bg-gray-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary">
    </x-fi.field>
</x-fi.modal>
