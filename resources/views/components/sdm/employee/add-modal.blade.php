{{-- Modal Tambah Karyawan --}}
<x-modal id="addModal" title="Tambah Karyawan" action="{{ route('employee.store') }}" method="POST" buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Lengkap <span class="text-error">*</span></label>
        <input type="text" name="name" class="w-full border rounded p-2" placeholder="Masukkan nama lengkap"
            required maxlength="255" oninvalid="this.setCustomValidity('Nama tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jabatan <span class="text-error">*</span></label>
        <input type="text" name="position" class="w-full border rounded p-2" placeholder="Masukkan jabatan" required
            maxlength="100" oninvalid="this.setCustomValidity('Jabatan tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">No. Telepon</label>
        <input type="text" name="phone" class="w-full border rounded p-2" placeholder="Masukkan no. telepon"
            maxlength="20">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Email</label>
        <input type="email" name="email" class="w-full border rounded p-2" placeholder="Masukkan email"
            maxlength="255">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Alamat</label>
        <textarea name="address" class="w-full border rounded p-2" placeholder="Masukkan alamat" rows="3"></textarea>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Gaji Pokok <span class="text-error">*</span></label>
        <input type="number" name="base_salary" value="0" class="w-full border rounded p-2"
            placeholder="Masukkan gaji pokok" required min="0"
            oninvalid="this.setCustomValidity('Gaji pokok tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Masuk <span class="text-error">*</span></label>
        <input type="date" name="join_date" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal masuk tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>
</x-modal>
