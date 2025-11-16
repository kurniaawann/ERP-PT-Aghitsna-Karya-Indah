{{-- Modal Tambah Lembur (Single Select) --}}
<x-modal id="addModal" title="Tambah Lembur" action="{{ route('overtime.store') }}" method="POST" buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Pilih Karyawan <span class="text-error">*</span></label>
        <select name="employee_id" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Karyawan tidak boleh kosong')" oninput="this.setCustomValidity('')">
            <option value="">Pilih Karyawan</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}">{{ $employee->name }} - {{ $employee->employee_code }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="attendance_date" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Jam Lembur <span class="text-error">*</span></label>
        <input type="number" name="overtime_hours" class="w-full border rounded p-2" placeholder="Contoh: 2.5" required
            min="0.01" max="24" step="0.01" id="add-overtime-hours"
            oninvalid="this.setCustomValidity('Jam lembur tidak boleh kosong')" oninput="this.setCustomValidity('')">
        <p class="text-xs text-gray-500 mt-1">Maksimal 24 jam</p>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Tarif per Jam <span class="text-error">*</span></label>
        <input type="number" name="overtime_rate" value="0" class="w-full border rounded p-2"
            placeholder="Masukkan tarif per jam" required min="0" id="add-overtime-rate"
            oninvalid="this.setCustomValidity('Tarif tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Total Lembur</label>
        <input type="text" id="add-overtime-total" class="w-full border rounded p-2 bg-gray-100" readonly
            value="Rp 0">
        <p class="text-xs text-gray-500 mt-1">Otomatis dihitung: Jam Lembur × Tarif</p>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Keterangan</label>
        <textarea name="notes" class="w-full border rounded p-2" placeholder="Masukkan keterangan (opsional)" rows="3"></textarea>
    </div>
</x-modal>
