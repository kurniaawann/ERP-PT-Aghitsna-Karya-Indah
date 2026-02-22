{{-- Modal Edit Karyawan --}}
<x-modal id="editModal-{{ $employee->employee_code }}" title="Edit Karyawan"
    action="{{ route('employee.update', $employee->employee_code) }}" method="POST" buttonText="Update">
    @method('PUT')

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kode Karyawan</label>
        <input type="text" name="employee_code" class="w-full border rounded p-2 bg-surface-hover"
            value="{{ $employee->employee_code }}" readonly>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Lengkap <span class="text-error">*</span></label>
        <input type="text" name="name" class="w-full border rounded p-2" placeholder="Masukkan nama lengkap"
            value="{{ $employee->name }}" required maxlength="255"
            oninvalid="this.setCustomValidity('Nama tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Upah Per Hari <span class="text-error">*</span></label>
        <input type="number" name="daily_wage" class="w-full border rounded p-2" placeholder="Masukkan upah per hari"
            value="{{ $employee->daily_wage }}" required min="0"
            oninvalid="this.setCustomValidity('Upah per hari tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">No. Telepon <span class="text-error">*</span></label>
        <input type="text" name="phone" class="w-full border rounded p-2" placeholder="Masukkan no. telepon"
            value="{{ $employee->phone }}" required maxlength="20"
            oninvalid="this.setCustomValidity('No. telepon tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Alamat <span class="text-error">*</span></label>
        <textarea name="address" class="w-full border rounded p-2" placeholder="Masukkan alamat" rows="3" required
            oninvalid="this.setCustomValidity('Alamat tidak boleh kosong')" oninput="this.setCustomValidity('')">{{ $employee->address }}</textarea>
    </div>
</x-modal>
