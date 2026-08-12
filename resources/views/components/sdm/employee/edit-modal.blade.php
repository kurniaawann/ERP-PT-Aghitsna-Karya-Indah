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
            value="{{ $employee->name }}" required maxlength="255">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jabatan <span class="text-text-tertiary text-sm">(Opsional)</span></label>
        <input type="text" name="position" class="w-full border rounded p-2" placeholder="Masukkan jabatan (opsional)"
            value="{{ $employee->position }}" maxlength="100">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jenis Karyawan <span class="text-error">*</span></label>
        <select name="employment_type" id="edit-employment-type-{{ $employee->employee_code }}"
            class="w-full border rounded p-2 edit-employment-type-select"
            data-employee-code="{{ $employee->employee_code }}">
            <option value="harian" @selected($employee->employment_type === 'harian')>Harian (Tukang)</option>
            <option value="bulanan" @selected($employee->employment_type === 'bulanan')>Bulanan (Slip Gaji)</option>
        </select>
    </div>

    <div class="mb-3 edit-wage-field-harian-{{ $employee->employee_code }}"
        @if ($employee->employment_type === 'bulanan') style="display: none;" @endif>
        <label class="block text-text-primary mb-1">Upah Per Hari <span class="text-error">*</span></label>
        <input type="text" inputmode="numeric" name="daily_wage" class="w-full border rounded p-2 daily-wage-input"
            placeholder="Masukkan upah per hari" value="{{ $employee->daily_wage }}" required min="0">
    </div>

    <div class="mb-3 edit-wage-field-bulanan-{{ $employee->employee_code }} hidden"
        @if ($employee->employment_type === 'bulanan') style="display: block;" @endif>
        <div class="p-3 border border-border-light rounded-lg bg-surface-secondary space-y-3">
            <div>
                <label class="block text-text-primary mb-1">Gaji Pokok / Bulan <span class="text-error">*</span></label>
                <input type="text" inputmode="numeric" name="base_salary" class="w-full border rounded p-2 base-salary-input"
                    placeholder="Masukkan gaji pokok per bulan" value="{{ $employee->base_salary }}" min="0">
            </div>

            <div>
                <label class="block text-text-primary mb-1">Status Karyawan <span class="text-text-tertiary text-sm">(Opsional)</span></label>
                <input type="text" name="status" class="w-full border rounded p-2"
                    placeholder="cth: Karyawan Tetap / Kontrak" value="{{ $employee->status }}" maxlength="100">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-text-primary mb-1">Transport / Hari <span class="text-text-tertiary text-sm">(Opsional)</span></label>
                    <input type="text" inputmode="numeric" name="transport_rate" class="w-full border rounded p-2 monthly-currency-input"
                        placeholder="cth: 5.000" value="{{ $employee->transport_rate }}" min="0">
                </div>

                <div>
                    <label class="block text-text-primary mb-1">Makan / Hari <span class="text-text-tertiary text-sm">(Opsional)</span></label>
                    <input type="text" inputmode="numeric" name="meal_rate" class="w-full border rounded p-2 monthly-currency-input"
                        placeholder="cth: 10.000" value="{{ $employee->meal_rate }}" min="0">
                </div>

                <div>
                    <label class="block text-text-primary mb-1">UMP <span class="text-text-tertiary text-sm">(Opsional)</span></label>
                    <input type="text" inputmode="numeric" name="ump" class="w-full border rounded p-2 monthly-currency-input"
                        placeholder="cth: 4.200.000" value="{{ $employee->ump }}" min="0">
                </div>
            </div>
        </div>
    </div>

    <div class="edit-harian-extra-{{ $employee->employee_code }}"
        @if ($employee->employment_type === 'bulanan') style="display: none;" @endif>
        <x-forms.searchable-select name="division" label="Divisi"
            placeholder="Cari divisi (opsional)..."
            :options="$divisions->map(fn($d) => ['value' => $d->name, 'label' => $d->name])->values()"
            selected="{{ $employee->division ?? '' }}" />
    </div>

    <div class="mb-3 edit-harian-extra-{{ $employee->employee_code }}"
        @if ($employee->employment_type === 'bulanan') style="display: none;" @endif>
        <label class="block text-text-primary mb-1">Proyek <span class="text-text-tertiary text-sm">(Opsional)</span></label>
        <div class="project-dropdown relative" data-route="{{ route('employee.projects-dropdown') }}">
            <input type="hidden" name="project_name" class="project-dropdown-hidden" value="{{ $employee->project_name ?? '' }}">
            <button type="button"
                class="project-dropdown-toggle w-full border border-border-strong rounded p-2 bg-surface-base text-text-input flex items-center justify-between">
                <span class="project-dropdown-label text-text-input">{{ $employee->project_name ?? '-- Pilih Proyek --' }}</span>
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

    <div class="mb-3 edit-harian-extra-{{ $employee->employee_code }}"
        @if ($employee->employment_type === 'bulanan') style="display: none;" @endif>
        <label class="block text-text-primary mb-1">No. Telepon</label>
        <input type="text" name="phone" class="w-full border rounded p-2" placeholder="Masukkan no. telepon (opsional)"
            value="{{ $employee->phone }}" maxlength="20">
    </div>

    <div class="mb-3 edit-harian-extra-{{ $employee->employee_code }}"
        @if ($employee->employment_type === 'bulanan') style="display: none;" @endif>
        <label class="block text-text-primary mb-1">Alamat <span class="text-text-tertiary text-sm">(Opsional)</span></label>
        <textarea name="address" class="w-full border rounded p-2" placeholder="Masukkan alamat" rows="3">{{ $employee->address }}</textarea>
    </div>
</x-modal>
