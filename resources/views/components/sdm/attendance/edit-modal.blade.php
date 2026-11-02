{{--
    Modal Edit Absensi (gaya Filament)
    Mengubah satu record absensi: karyawan (pilihan dengan pencarian),
    tanggal, status, dan keterangan.
    Memakai $employees dari halaman absensi; JS: form di [id^="editModal-"]
    ditangani initEditFormHandlers() (resources/js/pages/sdm/attendance/index.js).
--}}
@php
    $employeesForEdit = $employees ?? collect();

    // $employees hanya karyawan harian; absensi lama milik karyawan kantor
    // (gaji bulanan) tetap menampilkan karyawannya agar pilihan tidak kosong.
    if ($attendance->employee && ! $employeesForEdit->contains('employee_code', $attendance->employee_id)) {
        $employeesForEdit = $employeesForEdit->concat([$attendance->employee]);
    }

    $aid = $attendance->id;
@endphp

<x-fi.modal id="editModal-{{ $aid }}" heading="Edit Absensi" :description="$attendance->employee->name ?? null"
    icon="pencil-square" :action="route('attendance.update', $aid)" method="PUT" submitLabel="Update">

    {{-- Karyawan (pilihan dengan pencarian) --}}
    <x-fi.field label="Karyawan" for="edit-employee_id-{{ $aid }}" required>
        <x-fi.select name="employee_id" id="edit-employee_id-{{ $aid }}" data-fi-select="search" required
            placeholder="-- Pilih Karyawan --" :value="$attendance->employee_id"
            :options="$employeesForEdit->mapWithKeys(fn ($e) => [$e->employee_code => $e->name . ' - ' . $e->employee_code])"
            oninvalid="this.setCustomValidity('Karyawan tidak boleh kosong')" onchange="this.setCustomValidity('')" />
    </x-fi.field>

    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
        {{-- Tanggal Absensi --}}
        <x-fi.field label="Tanggal" for="edit-attendance-date-{{ $aid }}" required>
            <x-fi.input type="date" id="edit-attendance-date-{{ $aid }}" name="attendance_date" required
                :value="$attendance->attendance_date ? \Carbon\Carbon::parse($attendance->attendance_date)->format('Y-m-d') : ''"
                oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>

        {{-- Status Absensi --}}
        <x-fi.field label="Status" for="edit-status-{{ $aid }}" required>
            <x-fi.select name="status" id="edit-status-{{ $aid }}" required data-fi-select placeholder="Pilih Status"
                :value="$attendance->status"
                :options="['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'cuti' => 'Cuti']"
                oninvalid="this.setCustomValidity('Status tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>
    </div>

    {{-- Keterangan --}}
    <x-fi.field label="Keterangan" for="edit-notes-{{ $aid }}">
        <x-fi.textarea id="edit-notes-{{ $aid }}" name="notes" rows="3"
            placeholder="Masukkan keterangan (opsional)">{{ $attendance->notes }}</x-fi.textarea>
    </x-fi.field>
</x-fi.modal>
