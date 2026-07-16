{{-- ═══════════════════════════════════════════════════════════════════════
     Add Kasbon Modal Component
     Form for creating a new personal or team cash advance.
     Includes type toggle, employee/division selection,
     amount validation, and period date resolution.
     ═══════════════════════════════════════════════════════════════════════ --}}
<x-modal id="addModal" title="Tambah Kasbon" action="{{ route('kasbon.store') }}" method="POST" buttonText="Simpan">

    {{-- Information Box --}}
    <div class="mb-4 p-4 bg-primary-light border border-primary rounded-lg">
        <div class="flex gap-2">
            <i class="fa-solid fa-info-circle text-primary mt-1"></i>
            <div class="text-sm text-primary">
                <p class="font-semibold mb-1">Informasi Kasbon:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li><strong>Personal:</strong> Kasbon untuk 1 orang karyawan</li>
                    <li><strong>Tim:</strong> Kasbon untuk divisi tertentu (dibagi rata saat payroll)</li>
                    <li>Kasbon akan otomatis dipotong saat generate payroll</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Max Kasbon Limit Alert --}}
    <div id="add_kasbon_limit_alert" class="mb-4 p-4 bg-warning-light border border-warning rounded-lg hidden">
        <div class="flex gap-2">
            <i class="fa-solid fa-exclamation-triangle text-warning mt-1"></i>
            <div class="text-sm text-warning">
                <p class="font-semibold mb-1">Batas Maksimal Kasbon:</p>
                <p id="add_kasbon_limit_message"></p>
            </div>
        </div>
    </div>

    {{-- Kasbon Type Selection --}}
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

    {{-- Employee Selection (Personal) --}}
    <div class="mb-3" id="add_employee_field">
        <label class="block text-text-primary mb-1">Karyawan <span class="text-error">*</span></label>
        <select name="employee_id" id="add_employee_id"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            onchange="checkMaxKasbon('add')">
            <option value="">Pilih Karyawan</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->employee_code }}">{{ $employee->name }} ({{ $employee->employee_code }})</option>
            @endforeach
        </select>
    </div>

    {{-- Division Selection (Team) --}}
    <div class="mb-3" id="add_division_field" style="display: none;">
        <label class="block text-text-primary mb-1">Divisi <span class="text-error">*</span></label>
        <select name="division" id="add_division"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input">
            <option value="">Pilih Divisi</option>
            @foreach ($divisions as $division)
                <option value="{{ $division->name }}">{{ $division->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Amount Input --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah Kasbon <span class="text-error">*</span></label>
        <input type="text" inputmode="numeric" name="amount" id="add_amount"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input kasbon-amount-input"
            placeholder="Masukkan jumlah kasbon" required min="1000" step="1000"
            oninput="validateKasbonAmount('add')">
    </div>

    {{-- Kasbon Date --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Kasbon <span class="text-error">*</span></label>
        <input type="date" name="kasbon_date" id="add_kasbon_date"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            value="{{ date('Y-m-d') }}" required oninvalid="this.setCustomValidity('Tanggal kasbon tidak boleh kosong')"
            oninput="this.setCustomValidity('')" onchange="checkMaxKasbon('add')">
    </div>

    {{-- Period Month & Year --}}
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

    {{-- Hidden Fields (auto-resolved from period dates) --}}
    <input type="hidden" name="week_number" id="add_week_number" value="">
    <input type="hidden" name="period_start_date" id="add_period_start_date" value="">
    <input type="hidden" name="period_end_date" id="add_period_end_date" value="">

    {{-- Notes --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan</label>
        <textarea name="notes" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Catatan tambahan" rows="3" maxlength="500"></textarea>
    </div>
</x-modal>
