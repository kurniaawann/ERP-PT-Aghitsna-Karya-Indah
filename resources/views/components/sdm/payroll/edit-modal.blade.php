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
    - Client-side validation via required expense item fields in payroll-scripts.blade.php

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
        <div class="flex justify-between items-center mb-2">
            <label class="block text-text-primary font-semibold">Pengeluaran Tambahan (Opsional)</label>
            <button type="button" onclick="addExpenseItem('{{ $payroll->id }}')"
                class="text-sm bg-success hover:bg-success-hover text-white px-3 py-1 rounded-lg flex items-center gap-1">
                <i class="fa-solid fa-plus"></i> Tambah Item
            </button>
        </div>

        @php
            $editExpenseItems = [];
            $editNotesValue = old('additional_expenses_notes', $payroll->additional_expenses_notes);
            if (is_string($editNotesValue) && trim($editNotesValue) !== '' && trim($editNotesValue) !== '[]') {
                $decoded = json_decode($editNotesValue, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $editExpenseItems = $decoded;
                }
            }
        @endphp

        <div id="expense-items-container-{{ $payroll->id }}" class="space-y-2"
            data-expense-context="{{ $payroll->id }}">
            <p class="text-sm text-text-secondary text-center py-4" id="no-expense-text-{{ $payroll->id }}">
                Belum ada pengeluaran tambahan. Klik "Tambah Item" untuk menambahkan.
            </p>
            @foreach ($editExpenseItems as $expenseIndex => $expenseItem)
                <div class="expense-item border border-border-strong rounded-lg p-3 bg-surface-base"
                    data-item-id="edit-{{ $expenseIndex }}">
                    <div class="flex gap-2 items-start">
                        <div class="flex-1 grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs text-text-secondary mb-1">Nama Pengeluaran</label>
                                <input type="text"
                                    class="expense-name w-full border border-border-strong rounded-lg px-2 py-1.5 text-sm bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
                                    placeholder="Contoh: Token Listrik"
                                    value="{{ trim((string) ($expenseItem['name'] ?? '')) }}"
                                    oninput="updateExpenseData('{{ $payroll->id }}')" required>
                            </div>
                            <div>
                                <label class="block text-xs text-text-secondary mb-1">Jumlah (Rp)</label>
                                <input type="number"
                                    class="expense-amount w-full border border-border-strong rounded-lg px-2 py-1.5 text-sm bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
                                    placeholder="0" min="0" value="{{ (int) ($expenseItem['amount'] ?? 0) }}"
                                    oninput="updateExpenseData('{{ $payroll->id }}')" required>
                            </div>
                        </div>
                        <button type="button"
                            onclick="removeExpenseItem('edit-{{ $expenseIndex }}', '{{ $payroll->id }}')"
                            class="mt-6 text-error hover:text-error hover:bg-error-light px-2 py-1.5 rounded transition-colors"
                            title="Hapus item">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Hidden inputs for form submission -->
        <input type="hidden" name="additional_expenses" id="total_additional_expenses_{{ $payroll->id }}"
            value="{{ array_sum(array_column($editExpenseItems, 'amount')) }}">
        <input type="hidden" name="additional_expenses_notes" id="additional_expenses_notes_{{ $payroll->id }}"
            value="{{ $editExpenseItems ? json_encode($editExpenseItems) : '' }}">

        <!-- Total Display -->
        <div class="mt-3 p-3 bg-surface-secondary rounded-lg border border-border-strong">
            <div class="flex justify-between items-center">
                <span class="font-semibold text-text-primary">Total Pengeluaran Tambahan:</span>
                <span class="text-lg font-bold text-primary" id="total-expense-display-{{ $payroll->id }}">Rp 0</span>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan</label>
        <textarea name="notes" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            rows="2" placeholder="Catatan tambahan (opsional)">{{ old('notes', $payroll->notes) }}</textarea>
    </div>
</x-modal>
