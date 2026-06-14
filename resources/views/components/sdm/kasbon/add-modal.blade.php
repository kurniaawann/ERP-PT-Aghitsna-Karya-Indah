{{-- Modal Tambah Kasbon --}}
<x-modal id="addModal" title="Tambah Kasbon" action="{{ route('kasbon.store') }}" method="POST" buttonText="Simpan">

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

    {{-- Alert batas maksimal kasbon --}}
    <div id="add_kasbon_limit_alert" class="mb-4 p-4 bg-warning-light border border-warning rounded-lg hidden">
        <div class="flex gap-2">
            <i class="fa-solid fa-exclamation-triangle text-warning mt-1"></i>
            <div class="text-sm text-warning">
                <p class="font-semibold mb-1">Batas Maksimal Kasbon:</p>
                <p id="add_kasbon_limit_message"></p>
            </div>
        </div>
    </div>

    {{-- Alert per-employee amount untuk team kasbon --}}
    <div id="add_kasbon_per_employee_alert" class="mb-4 p-4 bg-info-light border border-info rounded-lg hidden">
        <div class="flex gap-2">
            <i class="fa-solid fa-calculator text-info mt-1"></i>
            <div class="text-sm text-info">
                <p class="font-semibold mb-1">Perhitungan Kasbon Tim:</p>
                <p id="add_kasbon_per_employee_message"></p>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jenis Kasbon <span class="text-error">*</span></label>
        <select name="kasbon_type" id="add_kasbon_type"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required
            onchange="toggleEmployeeSelect('add')" oninvalid="this.setCustomValidity('Jenis kasbon tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
            <option value="">Pilih Jenis</option>
            <option value="personal">Per Orang</option>
            <option value="team">Per Tim/Divisi</option>
        </select>
    </div>

    <div class="mb-3" id="add_employee_field">
        <label class="block text-text-primary mb-1">Karyawan <span class="text-error">*</span></label>
        <select name="employee_id" id="add_employee_id"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            onchange="checkMaxKasbon('add')">
            <option value="">Pilih Karyawan</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->employee_code }}">{{ $employee->name }} ({{ $employee->employee_code }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3" id="add_division_field" style="display: none;">
        <label class="block text-text-primary mb-1">Divisi <span class="text-error">*</span></label>
        <select name="division" id="add_division"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            onchange="onDivisionChange('add')">
            <option value="">Pilih Divisi</option>
            @foreach ($divisions as $division)
                <option value="{{ $division->name }}">{{ $division->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3" id="add_team_employees_field" style="display: none;">
        <label class="block text-text-primary mb-1">Pilih Karyawan (Tim) <span class="text-error">*</span></label>
        <div class="mb-2 flex items-center gap-2">
            <input type="text" id="add_team_employee_search" placeholder="Cari karyawan..."
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input text-sm"
                oninput="filterTeamEmployees('add')">
            <label class="flex items-center gap-1 text-sm text-text-primary whitespace-nowrap">
                <input type="checkbox" id="add_select_all_team" onchange="toggleSelectAllTeam('add')">
                Pilih Semua
            </label>
        </div>
        <div id="add_team_employee_list" class="max-h-48 overflow-y-auto border border-border-strong rounded p-2 bg-surface-base space-y-1">
            @foreach ($employees as $employee)
                <label class="flex items-center gap-2 p-1 hover:bg-surface-hover rounded employee-checkbox-item text-sm"
                    data-division="{{ $employee->division ?? '' }}"
                    data-daily-wage="{{ $employee->daily_wage ?? $employee->base_salary ?? 0 }}">
                    <input type="checkbox" name="employee_details[]" value="{{ $employee->employee_code }}"
                        class="team-employee-checkbox" onchange="onTeamEmployeeChange('add')">
                    <span>{{ $employee->name }} ({{ $employee->employee_code }})</span>
                    <span class="text-text-label text-xs ml-auto">{{ $employee->division ?? '-' }}</span>
                </label>
            @endforeach
            <div id="add_team_employee_empty" class="text-center py-6 text-text-label text-sm {{ $employees->count() > 0 ? 'hidden' : '' }}">
                <i class="fa-solid fa-users-slash text-2xl mb-2 text-border"></i>
                <p>Tidak ada karyawan yang tersedia untuk dipilih.</p>
            </div>
        </div>
        <div id="add_selected_employee_count" class="text-xs text-text-label mt-1">Dipilih: 0 karyawan</div>
    </div>

    <div class="mb-3" id="add_per_employee_amount_field" style="display: none;">
        <label class="block text-text-primary mb-1">Perhitungan Otomatis</label>
        <div class="grid grid-cols-2 gap-3 p-3 bg-surface-secondary border border-border-strong rounded text-sm">
            <div>
                <span class="text-text-label">Total Kasbon:</span>
                <span id="add_display_total_amount" class="font-semibold text-text-primary">Rp 0</span>
            </div>
            <div>
                <span class="text-text-label">Per Karyawan:</span>
                <span id="add_display_per_employee_amount" class="font-semibold text-text-primary">Rp 0</span>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah Kasbon <span class="text-error">*</span></label>
        <input type="text" inputmode="numeric" name="amount" id="add_amount"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input kasbon-amount-input"
            placeholder="Masukkan jumlah kasbon" required min="1000" step="1000"
            oninput="validateKasbonAmount('add'); updatePerEmployeeAmount('add');">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Kasbon <span class="text-error">*</span></label>
        <input type="date" name="kasbon_date" id="add_kasbon_date"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            value="{{ date('Y-m-d') }}" required oninvalid="this.setCustomValidity('Tanggal kasbon tidak boleh kosong')"
            oninput="this.setCustomValidity('')" onchange="checkMaxKasbon('add')">
    </div>

    <div class="grid grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Bulan <span class="text-error">*</span></label>
            <select name="period_month" id="add_period_month"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required
                onchange="checkMaxKasbon('add')">
                <option value="">Pilih</option>
                @for ($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ date('n') == $i ? 'selected' : '' }}>
                        {{ DateTime::createFromFormat('!m', $i)->format('M') }}
                    </option>
                @endfor
            </select>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Tahun <span class="text-error">*</span></label>
            <input type="number" name="period_year" id="add_period_year"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                value="{{ date('Y') }}" required min="2020" max="2100" onchange="checkMaxKasbon('add')">
        </div>
    </div>

    {{-- Hidden field untuk week_number (auto-detected dari tanggal) --}}
    <input type="hidden" name="week_number" id="add_week_number" value="">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan</label>
        <textarea name="notes" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Catatan tambahan" rows="3" maxlength="500"></textarea>
    </div>
</x-modal>
