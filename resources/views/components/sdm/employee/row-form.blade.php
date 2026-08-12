{{-- Form row untuk satu karyawan (dipakai pada modal tambah massal).
     $index : indeks baris. Pada template dinamis diisi placeholder '__INDEX__'
              yang diganti oleh JavaScript saat baris ditambahkan. --}}
<div class="employee-row border border-border-strong rounded p-4 bg-surface-base space-y-3"
    data-row-index="{{ $index }}">
    <div class="flex items-center justify-between">
        <h3 class="font-semibold text-text-primary text-sm">
            Karyawan <span class="employee-row-number"></span>
        </h3>
        <button type="button"
            class="employee-remove-row text-xs text-error hover:text-error flex items-center gap-1"
            title="Hapus baris ini">
            <i class="fa-solid fa-trash"></i> Hapus
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-text-primary mb-1 text-sm">Nama Lengkap <span class="text-error">*</span></label>
            <input type="text" name="employees[{{ $index }}][name]" class="w-full border rounded p-2"
                placeholder="Masukkan nama lengkap" required maxlength="255">
        </div>

        <div>
            <label class="block text-text-primary mb-1 text-sm">Jabatan <span class="text-text-tertiary text-sm">(Opsional)</span></label>
            <input type="text" name="employees[{{ $index }}][position]" class="w-full border rounded p-2"
                placeholder="Masukkan jabatan (opsional)" maxlength="100">
        </div>

        <div>
            <label class="block text-text-primary mb-1 text-sm">Jenis Karyawan <span class="text-error">*</span></label>
            <select name="employees[{{ $index }}][employment_type]"
                class="w-full border rounded p-2 employment-type-select"
                data-row-index="{{ $index }}">
                <option value="harian">Harian (Tukang)</option>
                <option value="bulanan">Bulanan (Slip Gaji)</option>
            </select>
        </div>

        <div class="wage-field-harian">
            <label class="block text-text-primary mb-1 text-sm">Upah Per Hari <span class="text-error">*</span></label>
            <input type="text" inputmode="numeric" name="employees[{{ $index }}][daily_wage]"
                class="w-full border rounded p-2 daily-wage-input" placeholder="Masukkan upah per hari"
                required min="0">
        </div>

        <div class="wage-field-bulanan hidden sm:col-span-2">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 border border-border-light rounded-lg bg-surface-secondary">
                <div>
                    <label class="block text-text-primary mb-1 text-sm">Gaji Pokok / Bulan <span class="text-error">*</span></label>
                    <input type="text" inputmode="numeric" name="employees[{{ $index }}][base_salary]"
                        class="w-full border rounded p-2 base-salary-input" placeholder="Masukkan gaji pokok per bulan"
                        min="0">
                </div>

                <div>
                    <label class="block text-text-primary mb-1 text-sm">Status Karyawan <span class="text-text-tertiary text-sm">(Opsional)</span></label>
                    <input type="text" name="employees[{{ $index }}][status]"
                        class="w-full border rounded p-2" placeholder="cth: Karyawan Tetap / Kontrak" maxlength="100">
                </div>

                <div>
                    <label class="block text-text-primary mb-1 text-sm">Uang Transport / Hari <span class="text-text-tertiary text-sm">(Opsional)</span></label>
                    <input type="text" inputmode="numeric" name="employees[{{ $index }}][transport_rate]"
                        class="w-full border rounded p-2 monthly-currency-input" placeholder="cth: 5.000" min="0">
                </div>

                <div>
                    <label class="block text-text-primary mb-1 text-sm">Uang Makan / Hari <span class="text-text-tertiary text-sm">(Opsional)</span></label>
                    <input type="text" inputmode="numeric" name="employees[{{ $index }}][meal_rate]"
                        class="w-full border rounded p-2 monthly-currency-input" placeholder="cth: 10.000" min="0">
                </div>

                <div>
                    <label class="block text-text-primary mb-1 text-sm">UMP <span class="text-text-tertiary text-sm">(Opsional, dasar potongan BPJS/JHT/JPN)</span></label>
                    <input type="text" inputmode="numeric" name="employees[{{ $index }}][ump]"
                        class="w-full border rounded p-2 monthly-currency-input" placeholder="cth: 4.200.000" min="0">
                </div>
            </div>
        </div>

        <div class="harian-extra-field">
            <label class="block text-text-primary mb-1 text-sm">No. Telepon</label>
            <input type="text" name="employees[{{ $index }}][phone]" class="w-full border rounded p-2"
                placeholder="Masukkan no. telepon (opsional)" maxlength="20">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="harian-extra-field">
            <x-forms.searchable-select name="employees[{{ $index }}][division]" label="Divisi"
                placeholder="Cari divisi (opsional)..."
                :options="$divisions->map(fn($d) => ['value' => $d->name, 'label' => $d->name])->values()" />
        </div>

        <div class="harian-extra-field">
            <label class="block text-text-primary mb-1 text-sm">Proyek <span class="text-text-tertiary text-sm">(Opsional)</span></label>
            <div class="project-dropdown relative" data-route="{{ route('employee.projects-dropdown') }}">
                <input type="hidden" name="employees[{{ $index }}][project_name]" class="project-dropdown-hidden">
                <button type="button"
                    class="project-dropdown-toggle w-full border border-border-strong rounded p-2 bg-surface-base text-text-input flex items-center justify-between">
                    <span class="project-dropdown-label text-text-input">-- Pilih Proyek --</span>
                    <span class="text-text-tertiary text-xs">▼</span>
                </button>
                <div class="project-dropdown-menu absolute z-50 w-full bg-surface-base border border-border-strong rounded-lg shadow-lg mt-1 hidden">
                    <div class="p-2 border-b border-border-light">
                        <input type="text" class="project-dropdown-search w-full border border-border-light rounded px-2 py-1.5 text-sm bg-surface-base text-text-input"
                            placeholder="Cari nama proyek...">
                    </div>
                    <div class="project-dropdown-list max-h-60 overflow-y-auto">
                        <div class="p-2 text-sm text-text-secondary">Silakan klik untuk memuat data...</div>
                    </div>
                    <div class="p-2 border-t border-border-light">
                        <button type="button" class="project-dropdown-clear text-sm text-error hover:text-error">
                            Reset (- Tidak Ada Proyek -)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="harian-extra-field">
        <label class="block text-text-primary mb-1 text-sm">Alamat <span class="text-text-tertiary text-sm">(Opsional)</span></label>
        <textarea name="employees[{{ $index }}][address]" class="w-full border rounded p-2"
            placeholder="Masukkan alamat" rows="2"></textarea>
    </div>
</div>
