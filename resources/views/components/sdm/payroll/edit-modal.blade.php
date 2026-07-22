{{--
    Edit Payroll Draft Modal

    Modal form for editing draft payroll records.
    Only available for payroll with status 'draft'.

    Editable fields:
    - project_name (required)
    - additional_expenses (required, must be >= 0)
    - additional_expenses_notes (required if additional_expenses > 0)
    - notes (optional)

    Read-only fields:
    - Employee name
    - Period
    - Daily wage
    - Net salary

    Validation:
    - Uses UpdatePayrollRequest for server-side validation
    - Client-side validation via validatePayrollEditNotes() in payroll-scripts.blade.php

    Included from: pages/sdm/payroll.blade.php (inside @foreach loop)
--}}

{{-- Modal Edit Payroll --}}
<x-modal id="editModal-{{ $payroll->id }}" title="Edit Payroll Draft" action="{{ route('payroll.update', $payroll->id) }}"
    method="PUT" buttonText="Update" size="xl">

    <div class="mb-4 p-4 bg-warning-light border border-warning rounded-lg">
        <div class="flex gap-2">
            <i class="fa-solid fa-triangle-exclamation text-warning mt-1"></i>
            <div class="text-sm text-warning">
                <p class="font-semibold mb-1">Payroll draft hanya bisa diubah sebelum dibayar.</p>
                <p>Data absensi, upah pokok, dan perhitungan inti tetap terkunci. Yang dapat diubah hanya data
                    administratif dan pengeluaran tambahan.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Nama Karyawan</label>
            <input type="text"
                class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-secondary"
                value="{{ $payroll->employee->name }}" disabled>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Periode</label>
            <input type="text"
                class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-secondary"
                value="{{ $payroll->formatted_period }}" disabled>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Upah Per Hari</label>
            <input type="text"
                class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-secondary"
                value="Rp {{ number_format($payroll->base_salary, 0, ',', '.') }}" disabled>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Upah Bersih</label>
            <input type="text"
                class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-secondary"
                value="Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}" disabled>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Proyek <span class="text-error">*</span></label>
        <input type="text" name="project_name"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Masukkan nama proyek" maxlength="255" required
            value="{{ old('project_name', $payroll->project_name) }}"
            oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Pengeluaran Tambahan (Rp) <span class="text-error">*</span></label>
        <input type="number" name="additional_expenses" id="additional_expenses_{{ $payroll->id }}"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" placeholder="0"
            min="0" required value="{{ old('additional_expenses', $payroll->additional_expenses ?? 0) }}"
            oninvalid="this.setCustomValidity('Pengeluaran tambahan tidak boleh kosong')"
            oninput="this.setCustomValidity(''); validatePayrollEditNotes({{ $payroll->id }})">
        <p class="text-xs text-text-secondary mt-1">Jika nominal lebih dari 0, keterangan wajib diisi.</p>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan Pengeluaran Tambahan</label>
        <textarea name="additional_expenses_notes" id="additional_expenses_notes_{{ $payroll->id }}"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" rows="3"
            placeholder="Contoh: Token listrik, air, ATK" oninput="validatePayrollEditNotes({{ $payroll->id }})">@php
                $notesValue = old('additional_expenses_notes', $payroll->additional_expenses_notes);
                if (is_string($notesValue) && trim($notesValue) !== '' && trim($notesValue) !== '[]') {
                    $decoded = json_decode($notesValue, true);
                    if (is_array($decoded) && count($decoded) === 0) {
                        echo '';
                    } else {
                        echo e($notesValue);
                    }
                }
            @endphp</textarea>
        <p class="text-xs text-text-secondary mt-1">Contoh: token listrik, air, ATK, dll.</p>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan</label>
        <textarea name="notes" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            rows="2" placeholder="Catatan tambahan (opsional)">{{ old('notes', $payroll->notes) }}</textarea>
    </div>
</x-modal>
