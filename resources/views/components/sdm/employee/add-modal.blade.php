{{-- Modal Tambah Karyawan --}}
<x-modal id="addModal" title="Tambah Karyawan" action="{{ route('employee.store') }}" method="POST" buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Lengkap <span class="text-error">*</span></label>
        <input type="text" name="name" class="w-full border rounded p-2" placeholder="Masukkan nama lengkap"
            required maxlength="255" oninvalid="this.setCustomValidity('Nama tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Upah Per Hari <span class="text-error">*</span></label>
        <input type="number" name="daily_wage" class="w-full border rounded p-2" placeholder="Masukkan upah per hari"
            required min="0" oninvalid="this.setCustomValidity('Upah per hari tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Divisi <span class="text-error">*</span></label>
        <select name="division" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Divisi tidak boleh kosong')" oninput="this.setCustomValidity('')">
            <option value="">Pilih Divisi</option>
            @foreach ($divisions as $division)
                <option value="{{ $division->name }}">{{ $division->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">No. Telepon <span class="text-error">*</span></label>
        <input type="text" name="phone" class="w-full border rounded p-2" placeholder="Masukkan no. telepon"
            required maxlength="20" oninvalid="this.setCustomValidity('No. telepon tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Alamat <span class="text-error">*</span></label>
        <textarea name="address" class="w-full border rounded p-2" placeholder="Masukkan alamat" rows="3" required
            oninvalid="this.setCustomValidity('Alamat tidak boleh kosong')" oninput="this.setCustomValidity('')"></textarea>
    </div>
</x-modal>
