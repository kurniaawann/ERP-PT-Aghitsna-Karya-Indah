{{-- Modal Edit Absensi --}}
<x-modal id="editModal-{{ $attendance->id }}" title="Edit Absensi"
    action="{{ route('attendance.update', $attendance->id) }}" method="POST" buttonText="Update">
    @method('PUT')

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Karyawan</label>
        <input type="text" class="w-full border rounded p-2 bg-gray-100" value="{{ $attendance->employee->name }}"
            disabled>
        <input type="hidden" name="employee_id" value="{{ $attendance->employee_id }}">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="attendance_date" class="w-full border rounded p-2"
            value="{{ $attendance->attendance_date }}" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Status <span class="text-error">*</span></label>
        <select name="status" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Status tidak boleh kosong')" oninput="this.setCustomValidity('')">
            <option value="">Pilih Status</option>
            <option value="hadir" {{ $attendance->status === 'hadir' ? 'selected' : '' }}>Hadir</option>
            <option value="izin" {{ $attendance->status === 'izin' ? 'selected' : '' }}>Izin</option>
            <option value="sakit" {{ $attendance->status === 'sakit' ? 'selected' : '' }}>Sakit</option>
            <option value="cuti" {{ $attendance->status === 'cuti' ? 'selected' : '' }}>Cuti</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Keterangan</label>
        <textarea name="notes" class="w-full border rounded p-2" placeholder="Masukkan keterangan (opsional)" rows="3">{{ $attendance->notes }}</textarea>
    </div>
</x-modal>
