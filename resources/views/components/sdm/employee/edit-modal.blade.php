{{-- Modal Edit Karyawan --}}
<x-modal id="editModal-{{ $employee->id }}" title="Edit Karyawan" action="{{ route('employee.update', $employee->id) }}"
    method="POST" buttonText="Update">
    @method('PUT')

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Kode Karyawan <span class="text-error">*</span></label>
        <input type="text" name="employee_code" class="w-full border rounded p-2" placeholder="Masukkan kode karyawan"
            value="{{ $employee->employee_code }}" required maxlength="50"
            oninvalid="this.setCustomValidity('Kode karyawan tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Nama Lengkap <span class="text-error">*</span></label>
        <input type="text" name="name" class="w-full border rounded p-2" placeholder="Masukkan nama lengkap"
            value="{{ $employee->name }}" required maxlength="255"
            oninvalid="this.setCustomValidity('Nama tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Jabatan <span class="text-error">*</span></label>
        <input type="text" name="position" class="w-full border rounded p-2" placeholder="Masukkan jabatan"
            value="{{ $employee->position }}" required maxlength="100"
            oninvalid="this.setCustomValidity('Jabatan tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">No. Telepon</label>
        <input type="text" name="phone" class="w-full border rounded p-2" placeholder="Masukkan no. telepon"
            value="{{ $employee->phone }}" maxlength="20">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Email</label>
        <input type="email" name="email" class="w-full border rounded p-2" placeholder="Masukkan email"
            value="{{ $employee->email }}" maxlength="255">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Alamat</label>
        <textarea name="address" class="w-full border rounded p-2" placeholder="Masukkan alamat" rows="3">{{ $employee->address }}</textarea>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Gaji Pokok <span class="text-error">*</span></label>
        <input type="number" name="base_salary" class="w-full border rounded p-2" placeholder="Masukkan gaji pokok"
            value="{{ $employee->base_salary }}" required min="0"
            oninvalid="this.setCustomValidity('Gaji pokok tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Tanggal Masuk <span class="text-error">*</span></label>
        <input type="date" name="join_date" class="w-full border rounded p-2" value="{{ $employee->join_date }}"
            required oninvalid="this.setCustomValidity('Tanggal masuk tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>
</x-modal>
