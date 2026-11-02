{{--
    Edit Overtime Modal Component (gaya Filament) — satu modal per data lembur.

    - Karyawan (x-fi.searchable-select, terpilih sesuai data). Input teks
      pencarian ini harus menjadi input[type=text] PERTAMA di form karena JS
      membaca nama karyawan dari sana untuk pesan validasi.
    - Tanggal (data-original-date, data-overtime-id untuk validasi duplikat)
    - Peringatan duplikat (#edit-duplicate-warning-{id})
    - Jam Lembur, Tarif per Jam, Total Lembur otomatis, Keterangan

    Variables: $overtime (Attendance status 'lembur'), $employees
    JS: resources/js/pages/sdm/overtime/index.js (initEditModalValidation,
    calculateEditOvertimeTotal).
--}}
@php($oid = $overtime->id)
<x-fi.modal id="editModal-{{ $oid }}" heading="Edit Lembur" :description="$overtime->employee->name ?? null" icon="pencil-square"
    :action="route('overtime.update', $oid)" method="PUT" submitLabel="Update">

    {{-- Karyawan (pencarian, terpilih) --}}
    <x-fi.searchable-select name="employee_id" id="edit-employee_id-{{ $oid }}" label="Karyawan" :required="true"
        placeholder="Cari karyawan..." :selected="$overtime->employee_id"
        :options="$employees->map(fn ($e) => ['value' => $e->employee_code, 'label' => $e->name . ' - ' . $e->employee_code])->values()" />

    {{-- Tanggal Lembur --}}
    <x-fi.field label="Tanggal" for="edit-attendance-date-{{ $oid }}" required
        hint="Lembur hanya dapat diinput jika karyawan sudah absen dengan status Hadir">
        <x-fi.input type="date" name="attendance_date" id="edit-attendance-date-{{ $oid }}" required :max="date('Y-m-d')"
            :value="$overtime->attendance_date ? \Carbon\Carbon::parse($overtime->attendance_date)->format('Y-m-d') : ''"
            data-original-date="{{ $overtime->attendance_date }}" data-overtime-id="{{ $oid }}"
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Peringatan duplikat (validasi sisi klien) --}}
    <div id="edit-duplicate-warning-{{ $oid }}" class="hidden rounded-lg bg-red-50 p-4 ring-1 ring-inset ring-red-600/10">
        <div class="flex gap-3">
            <x-fi.icon name="exclamation-circle" class="h-5 w-5 shrink-0 text-red-600" />
            <div>
                <p class="text-sm font-semibold text-red-800">Data Sudah Ada!</p>
                <p id="edit-duplicate-warning-text-{{ $oid }}" class="mt-1 text-sm text-red-700"></p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
        {{-- Jam Lembur --}}
        <x-fi.field label="Jam Lembur" for="edit-overtime-hours-{{ $oid }}" required hint="Maksimal 24 jam">
            <x-fi.input type="number" name="overtime_hours" id="edit-overtime-hours-{{ $oid }}" placeholder="Contoh: 2.5"
                suffix="jam" :value="$overtime->overtime_hours" required min="0.01" max="24" step="0.01"
                oninvalid="this.setCustomValidity('Jam lembur tidak boleh kosong')"
                oninput="calculateEditOvertimeTotal('{{ $oid }}'); this.setCustomValidity('')" />
        </x-fi.field>

        {{-- Tarif per Jam --}}
        <x-fi.field label="Tarif per Jam" for="edit-overtime-rate-{{ $oid }}" required>
            <x-fi.input inputmode="numeric" name="overtime_rate" id="edit-overtime-rate-{{ $oid }}" prefix="Rp"
                placeholder="Masukkan tarif per jam" :value="number_format($overtime->overtime_rate, 0, ',', '.')" required min="0"
                oninvalid="this.setCustomValidity('Tarif tidak boleh kosong')"
                oninput="formatCurrencyInput(this); calculateEditOvertimeTotal('{{ $oid }}'); this.setCustomValidity('')" />
        </x-fi.field>
    </div>

    {{-- Total Lembur (otomatis) --}}
    <x-fi.field label="Total Lembur" for="edit-overtime-total-{{ $oid }}" hint="Otomatis dihitung: Jam Lembur × Tarif">
        <x-fi.input id="edit-overtime-total-{{ $oid }}" readonly class="font-semibold"
            :value="'Rp ' . number_format($overtime->overtime_total, 0, ',', '.')" />
    </x-fi.field>

    {{-- Keterangan --}}
    <x-fi.field label="Keterangan" for="edit-overtime-notes-{{ $oid }}">
        <x-fi.textarea id="edit-overtime-notes-{{ $oid }}" name="notes" rows="3"
            placeholder="Masukkan keterangan (opsional)">{{ $overtime->notes }}</x-fi.textarea>
    </x-fi.field>
</x-fi.modal>
