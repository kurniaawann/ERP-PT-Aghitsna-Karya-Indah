{{-- Modal Edit Kasbon --}}
<x-modal id="editModal{{ $kasbon->kasbon_code }}" title="Edit Kasbon"
    action="{{ route('kasbon.update', $kasbon->kasbon_code) }}" method="PUT" buttonText="Update">

    <div class="mb-4 p-4 bg-primary-light border border-primary rounded-lg">
        <div class="flex gap-2">
            <i class="fa-solid fa-info-circle text-primary mt-1"></i>
            <div class="text-sm text-primary">
                <p class="font-semibold mb-1">Informasi Kasbon:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li><strong>Personal:</strong> Kasbon untuk 1 orang karyawan</li>
                    <li><strong>Tim:</strong> Kasbon untuk divisi tertentu (bisa pilih beberapa karyawan)</li>
                    <li>Kasbon akan otomatis dipotong saat generate payroll</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Alert per-employee amount untuk team kasbon --}}
    <div id="edit_{{ $kasbon->kasbon_code }}_kasbon_per_employee_alert" class="mb-4 p-4 bg-info-light border border-info rounded-lg hidden">
        <div class="flex gap-2">
            <i class="fa-solid fa-calculator text-info mt-1"></i>
            <div class="text-sm text-info">
                <p class="font-semibold mb-1">Perhitungan Kasbon Tim:</p>
                <p id="edit_{{ $kasbon->kasbon_code }}_kasbon_per_employee_message"></p>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jenis Kasbon <span class="text-error">*</span></label>
        <select name="kasbon_type" id="edit_kasbon_type_{{ $kasbon->kasbon_code }}" class="w-full border rounded p-2"
            required onchange="toggleEmployeeSelect('edit_{{ $kasbon->kasbon_code }}')">
            <option value="">Pilih Jenis</option>
            <option value="personal" {{ $kasbon->kasbon_type === 'personal' ? 'selected' : '' }}>Per Orang</option>
            <option value="team" {{ $kasbon->kasbon_type === 'team' ? 'selected' : '' }}>Per Tim/Divisi</option>
        </select>
    </div>

    <div class="mb-3" id="edit_{{ $kasbon->kasbon_code }}_employee_field">
        <label class="block text-text-primary mb-1">Karyawan <span class="text-error">*</span></label>
        <select name="employee_id" id="edit_{{ $kasbon->kasbon_code }}_employee_id" class="w-full border rounded p-2">
            <option value="">Pilih Karyawan</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->employee_code }}"
                    {{ $kasbon->employee_id === $employee->employee_code ? 'selected' : '' }}>
                    {{ $employee->name }} ({{ $employee->employee_code }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3" id="edit_{{ $kasbon->kasbon_code }}_division_field" style="display: none;">
        <label class="block text-text-primary mb-1">Divisi <span class="text-error">*</span></label>
        <select name="division" id="edit_{{ $kasbon->kasbon_code }}_division" class="w-full border rounded p-2"
            onchange="onDivisionChange('edit_{{ $kasbon->kasbon_code }}')">
            <option value="">Pilih Divisi</option>
            @foreach ($divisions as $division)
                <option value="{{ $division->name }}" {{ $kasbon->division === $division->name ? 'selected' : '' }}>
                    {{ $division->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3" id="edit_{{ $kasbon->kasbon_code }}_team_employees_field" style="display: none;">
        <label class="block text-text-primary mb-1">Pilih Karyawan (Tim) <span class="text-error">*</span></label>
        <div class="mb-2 flex items-center gap-2">
            <input type="text" id="edit_{{ $kasbon->kasbon_code }}_team_employee_search" placeholder="Cari karyawan..."
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input text-sm"
                oninput="filterTeamEmployees('edit_{{ $kasbon->kasbon_code }}')">
            <label class="flex items-center gap-1 text-sm text-text-primary whitespace-nowrap">
                <input type="checkbox" id="edit_{{ $kasbon->kasbon_code }}_select_all_team" onchange="toggleSelectAllTeam('edit_{{ $kasbon->kasbon_code }}')">
                Pilih Semua
            </label>
        </div>
        <div id="edit_{{ $kasbon->kasbon_code }}_team_employee_list" class="max-h-48 overflow-y-auto border border-border-strong rounded p-2 bg-surface-base space-y-1">
            @foreach ($employees as $employee)
                <label class="flex items-center gap-2 p-1 hover:bg-surface-hover rounded employee-checkbox-item text-sm"
                    data-division="{{ $employee->division ?? '' }}"
                    data-daily-wage="{{ $employee->daily_wage ?? $employee->base_salary ?? 0 }}">
                    <input type="checkbox" name="employee_details[]" value="{{ $employee->employee_code }}"
                        class="team-employee-checkbox"
                        {{ in_array($employee->employee_code, $kasbon->employee_details ?? []) ? 'checked' : '' }}
                        onchange="onTeamEmployeeChange('edit_{{ $kasbon->kasbon_code }}')">
                    <span>{{ $employee->name }} ({{ $employee->employee_code }})</span>
                    <span class="text-text-label text-xs ml-auto">{{ $employee->division ?? '-' }}</span>
                </label>
            @endforeach
            <div id="edit_{{ $kasbon->kasbon_code }}_team_employee_empty" class="text-center py-6 text-text-label text-sm {{ $employees->count() > 0 ? 'hidden' : '' }}">
                <i class="fa-solid fa-users-slash text-2xl mb-2 text-border"></i>
                <p>Tidak ada karyawan yang tersedia untuk dipilih.</p>
            </div>
        </div>
        <div id="edit_{{ $kasbon->kasbon_code }}_selected_employee_count" class="text-xs text-text-label mt-1">Dipilih: 0 karyawan</div>
    </div>

    <div class="mb-3" id="edit_{{ $kasbon->kasbon_code }}_per_employee_amount_field" style="display: none;">
        <label class="block text-text-primary mb-1">Perhitungan Otomatis</label>
        <div class="grid grid-cols-2 gap-3 p-3 bg-surface-secondary border border-border-strong rounded text-sm">
            <div>
                <span class="text-text-label">Total Kasbon:</span>
                <span id="edit_{{ $kasbon->kasbon_code }}_display_total_amount" class="font-semibold text-text-primary">Rp 0</span>
            </div>
            <div>
                <span class="text-text-label">Per Karyawan:</span>
                <span id="edit_{{ $kasbon->kasbon_code }}_display_per_employee_amount" class="font-semibold text-text-primary">Rp 0</span>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah Kasbon <span class="text-error">*</span></label>
        <input type="text" inputmode="numeric" name="amount" id="edit_amount_{{ $kasbon->kasbon_code }}" class="w-full border rounded p-2 kasbon-amount-input"
            value="{{ $kasbon->amount }}" required min="1000" step="1000"
            oninput="validateKasbonAmount('edit_{{ $kasbon->kasbon_code }}'); updatePerEmployeeAmount('edit_{{ $kasbon->kasbon_code }}');">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Kasbon <span class="text-error">*</span></label>
        <input type="date" name="kasbon_date" id="edit_kasbon_date_{{ $kasbon->kasbon_code }}" class="w-full border rounded p-2"
            value="{{ $kasbon->kasbon_date->format('Y-m-d') }}" required>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Bulan <span class="text-error">*</span></label>
            <select name="period_month" id="edit_period_month_{{ $kasbon->kasbon_code }}" class="w-full border rounded p-2" required>
                @for ($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ $kasbon->period_month == $i ? 'selected' : '' }}>
                        {{ DateTime::createFromFormat('!m', $i)->format('M') }}
                    </option>
                @endfor
            </select>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Tahun <span class="text-error">*</span></label>
            <input type="number" name="period_year" id="edit_period_year_{{ $kasbon->kasbon_code }}" class="w-full border rounded p-2"
                value="{{ $kasbon->period_year }}" required min="2020" max="2100">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Minggu</label>
            <select name="week_number" class="w-full border rounded p-2">
                <option value="">-</option>
                <option value="1" {{ $kasbon->week_number == 1 ? 'selected' : '' }}>1</option>
                <option value="2" {{ $kasbon->week_number == 2 ? 'selected' : '' }}>2</option>
                <option value="3" {{ $kasbon->week_number == 3 ? 'selected' : '' }}>3</option>
                <option value="4" {{ $kasbon->week_number == 4 ? 'selected' : '' }}>4</option>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan</label>
        <textarea name="notes" class="w-full border rounded p-2" rows="3" maxlength="500">{{ $kasbon->notes }}</textarea>
    </div>
</x-modal>

<script>
    // Initialize employee field visibility on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleEmployeeSelect('edit_{{ $kasbon->kasbon_code }}');
    });
</script>
