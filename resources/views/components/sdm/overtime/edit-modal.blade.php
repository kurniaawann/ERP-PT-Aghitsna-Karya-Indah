{{-- Modal Edit Lembur --}}
<x-modal id="editModal-{{ $overtime->id }}" title="Edit Lembur" action="{{ route('overtime.update', $overtime->id) }}"
    method="POST" buttonText="Update">
    @method('PUT')

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Karyawan</label>
        <input type="text" class="w-full border rounded p-2 bg-gray-100" value="{{ $overtime->employee->name }}"
            disabled>
        <input type="hidden" name="employee_id" value="{{ $overtime->employee_id }}">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="attendance_date" class="w-full border rounded p-2"
            value="{{ $overtime->attendance_date }}" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Jam Lembur <span class="text-error">*</span></label>
        <input type="number" name="overtime_hours" class="w-full border rounded p-2" placeholder="Contoh: 2.5"
            value="{{ $overtime->overtime_hours }}" required min="0.01" max="24" step="0.01"
            id="edit-overtime-hours-{{ $overtime->id }}"
            oninvalid="this.setCustomValidity('Jam lembur tidak boleh kosong')" oninput="this.setCustomValidity('')">
        <p class="text-xs text-gray-500 mt-1">Maksimal 24 jam</p>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Tarif per Jam <span class="text-error">*</span></label>
        <input type="number" name="overtime_rate" class="w-full border rounded p-2"
            placeholder="Masukkan tarif per jam" value="{{ $overtime->overtime_rate }}" required min="0"
            id="edit-overtime-rate-{{ $overtime->id }}" oninvalid="this.setCustomValidity('Tarif tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Total Lembur</label>
        <input type="text" id="edit-overtime-total-{{ $overtime->id }}" class="w-full border rounded p-2 bg-gray-100"
            readonly value="Rp {{ number_format($overtime->overtime_total, 0, ',', '.') }}">
        <p class="text-xs text-gray-500 mt-1">Otomatis dihitung: Jam Lembur × Tarif</p>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Keterangan</label>
        <textarea name="notes" class="w-full border rounded p-2" placeholder="Masukkan keterangan (opsional)" rows="3">{{ $overtime->notes }}</textarea>
    </div>
</x-modal>
