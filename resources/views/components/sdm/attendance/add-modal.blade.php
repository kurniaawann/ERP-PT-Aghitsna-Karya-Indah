{{-- Modal Tambah Absensi (Multi Select) --}}
<x-modal id="addModal" title="Tambah Absensi" action="{{ route('attendance.store') }}" method="POST" buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Pilih Karyawan <span class="text-error">*</span></label>
        <div class="border rounded p-3 max-h-48 overflow-y-auto bg-gray-50">
            <div class="mb-2">
                <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-100 p-2 rounded">
                    <input type="checkbox" id="selectAllEmployees" class="w-4 h-4 accent-primary">
                    <span class="font-semibold">Pilih Semua</span>
                </label>
            </div>
            <hr class="my-2">
            @foreach ($employees as $employee)
                <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-100 p-2 rounded">
                    <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}"
                        class="w-4 h-4 accent-primary employee-checkbox" required>
                    <span>{{ $employee->name }} - {{ $employee->employee_code }}</span>
                </label>
            @endforeach
        </div>
        <p class="text-xs text-gray-500 mt-1">Pilih satu atau lebih karyawan</p>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="attendance_date" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Status <span class="text-error">*</span></label>
        <select name="status" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Status tidak boleh kosong')" oninput="this.setCustomValidity('')">
            <option value="">Pilih Status</option>
            <option value="hadir">Hadir</option>
            <option value="izin">Izin</option>
            <option value="sakit">Sakit</option>
            <option value="cuti">Cuti</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Keterangan</label>
        <textarea name="notes" class="w-full border rounded p-2" placeholder="Masukkan keterangan (opsional)" rows="3"></textarea>
    </div>
</x-modal>
