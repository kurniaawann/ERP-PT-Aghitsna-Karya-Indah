{{-- Modal Edit Expense Report --}}
<x-modal id="editModal-{{ $expense->id }}" title="Edit Laporan Pengeluaran"
    action="{{ route('expense-report.update', $expense->id) }}" method="PUT" buttonText="Update">

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Kategori Pengeluaran <span class="text-error">*</span></label>
        <select name="transaction_category_id" class="w-full border rounded p-2" required>
            <option value="">-- Pilih Kategori --</option>
            @foreach ($categories->where('type', 'EXPENSE') as $cat)
                <option value="{{ $cat->id }}"
                    {{ $expense->transaction_category_id == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="transaction_date" class="w-full border rounded p-2"
            value="{{ $expense->transaction_date ? \Carbon\Carbon::parse($expense->transaction_date)->format('Y-m-d') : '' }}"
            required>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Keterangan <span class="text-error">*</span></label>
        <textarea name="description" class="w-full border rounded p-2" rows="3" required>{{ $expense->description }}</textarea>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Jumlah Pengeluaran <span class="text-error">*</span></label>
        <input type="number" name="expense_amount" class="w-full border rounded p-2"
            value="{{ $expense->expense_amount }}" required min="0">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">No. Faktur (Opsional)</label>
        <input type="text" name="invoice_number" class="w-full border rounded p-2"
            value="{{ $expense->invoice_number }}">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Sumber Uang (Opsional)</label>
        <input type="text" name="money_source" class="w-full border rounded p-2"
            value="{{ $expense->money_source }}">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Catatan (Opsional)</label>
        <textarea name="notes" class="w-full border rounded p-2" rows="2">{{ $expense->notes }}</textarea>
    </div>
</x-modal>
