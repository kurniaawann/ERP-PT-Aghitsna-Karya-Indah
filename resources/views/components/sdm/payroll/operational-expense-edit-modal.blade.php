{{--
    Edit Pengeluaran Operasional Proyek Modal

    Modal untuk mengubah pengeluaran tambahan / operasional proyek
    yang tercatat sekali per periode payroll.

    Fields:
    - project_name (optional)
    - additional_expenses (hidden, total)
    - additional_expenses_notes (hidden, JSON items)
    - notes (optional)

    JS expense item handling: resources/js/pages/sdm/payroll/index.js
    (context = expense id)
--}}

<x-modal id="expenseModal-{{ $expense->id }}" title="Edit Pengeluaran Operasional Proyek"
    action="{{ route('payroll.operational-expense.update', $expense->id) }}" method="PUT" buttonText="Update">

    <div class="mb-4 p-4 bg-warning-light border border-warning rounded-lg">
        <div class="flex gap-2">
            <i class="fa-solid fa-triangle-exclamation text-warning mt-1"></i>
            <div class="text-sm text-warning">
                <p class="font-semibold mb-1">Pengeluaran ini berlaku untuk seluruh periode, bukan per karyawan.</p>
                <p>Periode: <strong>{{ $expense->formatted_period }}</strong></p>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Proyek <span
                class="text-text-tertiary text-sm">(Opsional)</span></label>
        <input type="text" name="project_name"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Contoh: Lampsr Tanap 3, Proyek ABC, dll" maxlength="255"
            value="{{ old('project_name', $expense->project_name) }}">
    </div>

    <div class="mb-3">
        <div class="flex justify-between items-center mb-2">
            <label class="block text-text-primary font-semibold">Pengeluaran Tambahan / Operasional</label>
            <button type="button" onclick="addExpenseItem('{{ $expense->id }}')"
                class="text-sm bg-success hover:bg-success-hover text-white px-3 py-1 rounded-lg flex items-center gap-1">
                <i class="fa-solid fa-plus"></i> Tambah Item
            </button>
        </div>

        @php
            $expenseItems = is_array($expense->expense_items) ? $expense->expense_items : [];
        @endphp

        <div id="expense-items-container-{{ $expense->id }}" class="space-y-2"
            data-expense-context="{{ $expense->id }}">
            <p class="text-sm text-text-secondary text-center py-4" id="no-expense-text-{{ $expense->id }}"
                @if (count($expenseItems) > 0) style="display: none;" @endif>
                Belum ada pengeluaran tambahan. Klik "Tambah Item" untuk menambahkan.
            </p>

            @foreach ($expenseItems as $expenseIndex => $expenseItem)
                <div class="expense-item border border-border-strong rounded-lg p-3 bg-surface-base"
                    data-item-id="exp-{{ $expenseIndex }}">
                    <div class="flex gap-2 items-start">
                        <div class="flex-1 grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs text-text-secondary mb-1">Nama Pengeluaran</label>
                                <input type="text"
                                    class="expense-name w-full border border-border-strong rounded-lg px-2 py-1.5 text-sm bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
                                    placeholder="Contoh: Air Minum Proyek"
                                    value="{{ trim((string) ($expenseItem['name'] ?? '')) }}"
                                    oninput="updateExpenseData('{{ $expense->id }}')" required>
                            </div>
                            <div>
                                <label class="block text-xs text-text-secondary mb-1">Jumlah (Rp)</label>
                                <input type="number"
                                    class="expense-amount w-full border border-border-strong rounded-lg px-2 py-1.5 text-sm bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
                                    placeholder="0" min="0" value="{{ (int) ($expenseItem['amount'] ?? 0) }}"
                                    oninput="updateExpenseData('{{ $expense->id }}')" required>
                            </div>
                        </div>
                        <button type="button"
                            onclick="removeExpenseItem('exp-{{ $expenseIndex }}', '{{ $expense->id }}')"
                            class="mt-6 text-error hover:text-error hover:bg-error-light px-2 py-1.5 rounded transition-colors"
                            title="Hapus item">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Hidden inputs for form submission -->
        <input type="hidden" name="additional_expenses" id="total_additional_expenses_{{ $expense->id }}"
            value="{{ $expense->total_amount }}">
        <input type="hidden" name="additional_expenses_notes" id="additional_expenses_notes_{{ $expense->id }}"
            value="{{ $expenseItems ? json_encode($expenseItems) : '' }}">

        <!-- Total Display -->
        <div class="mt-3 p-3 bg-surface-secondary rounded-lg border border-border-strong">
            <div class="flex justify-between items-center">
                <span class="font-semibold text-text-primary">Total Pengeluaran Tambahan:</span>
                <span class="text-lg font-bold text-primary"
                    id="total-expense-display-{{ $expense->id }}">Rp
                    {{ number_format($expense->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan</label>
        <textarea name="notes" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            rows="2" placeholder="Catatan tambahan (opsional)">{{ old('notes', $expense->notes) }}</textarea>
    </div>
</x-modal>
