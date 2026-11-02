{{--
    Add Overtime Modal Component (gaya Filament)

    Form tambah lembur:
    - Karyawan (x-fi.searchable-select → hidden employee_id, .searchable-select-*)
    - Tanggal (default kosong, maks hari ini)
    - Peringatan duplikat/absensi (validasi sisi klien, #add-duplicate-warning)
    - Jam Lembur (0.01 - 24), Tarif per Jam (Rupiah)
    - Total Lembur otomatis (jam × tarif), readonly
    - Keterangan (opsional)

    Variables: $employees (employee_code, name)
    JS: resources/js/pages/sdm/overtime/index.js (validateAddOvertime,
    calculateAddOvertimeTotal, formatCurrencyInput).
--}}
<x-fi.modal id="addModal" heading="Tambah Lembur" icon="clock" :action="route('overtime.store')">

    {{-- Karyawan (pencarian) --}}
    <x-fi.searchable-select name="employee_id" label="Pilih Karyawan" :required="true" placeholder="Cari karyawan..."
        :options="$employees->map(fn ($e) => ['value' => $e->employee_code, 'label' => $e->name . ' - ' . $e->employee_code])->values()" />

    {{-- Tanggal Lembur --}}
    <x-fi.field label="Tanggal" for="add-attendance-date" required
        hint="Lembur hanya dapat diinput jika karyawan sudah absen dengan status Hadir">
        <x-fi.input type="date" name="attendance_date" id="add-attendance-date" required :max="date('Y-m-d')"
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Peringatan duplikat / belum absen hadir (validasi sisi klien) --}}
    <div id="add-duplicate-warning" class="hidden rounded-lg bg-red-50 p-4 ring-1 ring-inset ring-red-600/10">
        <div class="flex gap-3">
            <x-fi.icon name="exclamation-circle" class="h-5 w-5 shrink-0 text-red-600" />
            <div>
                <p class="text-sm font-semibold text-red-800">Data Sudah Ada!</p>
                <p id="add-duplicate-warning-text" class="mt-1 text-sm text-red-700"></p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
        {{-- Jam Lembur --}}
        <x-fi.field label="Jam Lembur" for="add-overtime-hours" required hint="Maksimal 24 jam">
            <x-fi.input type="number" name="overtime_hours" id="add-overtime-hours" placeholder="Contoh: 2.5" suffix="jam"
                required min="0.01" max="24" step="0.01"
                oninvalid="this.setCustomValidity('Jam lembur tidak boleh kosong')"
                oninput="calculateAddOvertimeTotal(); this.setCustomValidity('')" />
        </x-fi.field>

        {{-- Tarif per Jam --}}
        <x-fi.field label="Tarif per Jam" for="add-overtime-rate" required>
            <x-fi.input inputmode="numeric" name="overtime_rate" id="add-overtime-rate" value="0" prefix="Rp"
                placeholder="Masukkan tarif per jam" required min="0"
                oninvalid="this.setCustomValidity('Tarif tidak boleh kosong')"
                oninput="formatCurrencyInput(this); calculateAddOvertimeTotal(); this.setCustomValidity('')" />
        </x-fi.field>
    </div>

    {{-- Total Lembur (otomatis) --}}
    <x-fi.field label="Total Lembur" for="add-overtime-total" hint="Otomatis dihitung: Jam Lembur × Tarif">
        <x-fi.input id="add-overtime-total" value="Rp 0" readonly class="font-semibold" />
    </x-fi.field>

    {{-- Keterangan --}}
    <x-fi.field label="Keterangan" for="add-overtime-notes">
        <x-fi.textarea id="add-overtime-notes" name="notes" rows="3" placeholder="Masukkan keterangan (opsional)"></x-fi.textarea>
    </x-fi.field>
</x-fi.modal>
