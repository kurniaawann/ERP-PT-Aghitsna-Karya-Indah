{{--
    Edit Overtime Modal Component

    Displays a modal form for editing an existing overtime record.
    One modal is rendered per overtime record (triggered from the table's Edit button).
    Uses <x-forms.searchable-select> for employee selection with pre-selected value.

    Sections:
    - Searchable Select: Employee picker (pre-selected with current employee)
    - Attendance Date: Date picker (pre-populated)
    - Duplicate Warning: Client-side validation for existing attendance
    - Overtime Hours: Numeric input (pre-populated)
    - Overtime Rate: Rate per hour (pre-populated)
    - Total Lembur: Auto-calculated (hours × rate), readonly
    - Notes: Optional notes textarea (pre-populated)

    Variables:
    - $overtime: Attendance model instance with status 'lembur'
    - $employees: Collection of Employee models with employee_code and name

    Data Attributes (used by JavaScript for duplicate validation):
    - data-original-date: Original attendance date (to skip validation if unchanged)
    - data-overtime-id: Overtime record ID (to exclude from duplicate check)
--}}
<x-modal id="editModal-{{ $overtime->id }}" title="Edit Lembur" action="{{ route('overtime.update', $overtime->id) }}"
    method="POST" buttonText="Update">
    @method('PUT')

    {{-- Searchable Select: Karyawan (pre-selected) --}}
    <x-forms.searchable-select name="employee_id"
        id="edit-employee_id-{{ $overtime->id }}" label="Karyawan" :required="true"
        placeholder="Cari karyawan..."
        :options="$employees->map(fn($e) => ['value' => $e->employee_code, 'label' => $e->name . ' - ' . $e->employee_code])->values()"
        selected="{{ $overtime->employee_id }}" />

    {{-- Tanggal Lembur --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="attendance_date" id="edit-attendance-date-{{ $overtime->id }}"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            value="{{ $overtime->attendance_date ? \Carbon\Carbon::parse($overtime->attendance_date)->format('Y-m-d') : '' }}"
            required data-original-date="{{ $overtime->attendance_date }}" data-overtime-id="{{ $overtime->id }}"
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Duplicate Warning: client-side validation --}}
    <div id="edit-duplicate-warning-{{ $overtime->id }}"
        class="hidden mb-3 p-3 bg-error-light border-l-4 border-error rounded">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-error mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd" />
            </svg>
            <div>
                <p class="font-semibold text-error text-sm mb-1">Data Sudah Ada!</p>
                <p id="edit-duplicate-warning-text-{{ $overtime->id }}" class="text-sm text-error"></p>
            </div>
        </div>
    </div>

    {{-- Jam Lembur --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jam Lembur <span class="text-error">*</span></label>
        <input type="number" name="overtime_hours"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Contoh: 2.5" value="{{ $overtime->overtime_hours }}" required min="0.01" max="24"
            step="0.01" id="edit-overtime-hours-{{ $overtime->id }}"
            oninvalid="this.setCustomValidity('Jam lembur tidak boleh kosong')"
            oninput="calculateEditOvertimeTotal('{{ $overtime->id }}'); this.setCustomValidity('')">
        <p class="text-xs text-text-secondary mt-1">Maksimal 24 jam</p>
    </div>

    {{-- Tarif per Jam (Rupiah currency format) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tarif per Jam <span class="text-error">*</span></label>
        <input type="text" inputmode="numeric" name="overtime_rate"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Masukkan tarif per jam" value="{{ number_format($overtime->overtime_rate, 0, ',', '.') }}"
            required min="0" id="edit-overtime-rate-{{ $overtime->id }}"
            oninvalid="this.setCustomValidity('Tarif tidak boleh kosong')"
            oninput="formatCurrencyInput(this); calculateEditOvertimeTotal('{{ $overtime->id }}'); this.setCustomValidity('')">
    </div>

    {{-- Total Lembur (Auto-calculated) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Total Lembur</label>
        <input type="text" id="edit-overtime-total-{{ $overtime->id }}"
            class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-input" readonly
            value="Rp {{ number_format($overtime->overtime_total, 0, ',', '.') }}">
        <p class="text-xs text-text-secondary mt-1">Otomatis dihitung: Jam Lembur × Tarif</p>
    </div>

    {{-- Keterangan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan</label>
        <textarea name="notes" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Masukkan keterangan (opsional)" rows="3">{{ $overtime->notes }}</textarea>
    </div>
</x-modal>
