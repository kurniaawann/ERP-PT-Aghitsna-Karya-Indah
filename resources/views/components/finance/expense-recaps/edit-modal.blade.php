{{-- Modal Edit Rekap Pengeluaran (admin: "Kas Kantor") --}}
@php
    $safeId = str_replace('/', '-', $expense->id);
    $recapLabel = $recapLabel ?? 'Rekap Pengeluaran';
    // Nomor faktur hanya dipakai super admin (admin/Kas Kantor tanpa kolom faktur)
    $usesInvoiceNumber = !auth()->user()->isAdmin();
    // Faktur yang ada hanya relevan untuk record uang masuk
    $currentInvoiceNumber = (int) $expense->income_amount > 0 ? $expense->invoice_number : null;
@endphp
<x-modal id="editModal-{{ $safeId }}" title="Edit {{ $recapLabel }}"
    action="{{ route('recap-expense.update', $expense->id) }}" method="PUT" buttonText="Update">

    {{-- Kategori --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kategori <span class="text-error">*</span></label>
        <select name="transaction_category_id" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Kategori tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
            <option value="">-- Pilih Kategori --</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" data-type="{{ $cat->type }}"
                    {{ $expense->transaction_category_id == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="transaction_date" class="w-full border rounded p-2"
            value="{{ $expense->transaction_date ? \Carbon\Carbon::parse($expense->transaction_date)->format('Y-m-d') : '' }}"
            required oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Keterangan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan <span class="text-error">*</span></label>
        <textarea name="description" class="w-full border rounded p-2" rows="3" required maxlength="1000"
            oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')" oninput="this.setCustomValidity('')">{{ $expense->description }}</textarea>
    </div>

    {{-- Jumlah --}}
    <div class="mb-3">
        <label class="amount-label block text-text-primary mb-1">Jumlah Pengeluaran <span
                class="text-error">*</span></label>
        <input type="text" inputmode="numeric" name="expense_amount"
            class="w-full border rounded p-2 expense-amount-input"
            value="{{ $expense->income_amount ?: $expense->expense_amount }}" required min="0"
            oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Nomor Faktur: hanya untuk kategori uang masuk (read-only). Uang keluar tidak
         memiliki nomor faktur — bagian ini disembunyikan oleh JS. --}}
    @if ($usesInvoiceNumber)
        <div class="invoice-section hidden mb-3">
            <label class="block text-text-primary mb-1">No. Faktur</label>
            <input type="text" class="w-full border rounded p-2 bg-surface-secondary text-text-secondary"
                value="{{ $currentInvoiceNumber }}" placeholder="Di-generate otomatis saat disimpan"
                readonly tabindex="-1">
            <p class="invoice-note text-xs text-text-secondary mt-1">Khusus uang masuk — nomor faktur di-generate
                otomatis dan tidak dapat diubah.</p>
        </div>
    @endif

    {{-- Sumber Uang --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Sumber Uang (Opsional)</label>
        <input type="text" name="money_source" class="w-full border rounded p-2" value="{{ $expense->money_source }}"
            maxlength="255">
    </div>

    {{-- Catatan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan (Opsional)</label>
        <textarea name="notes" class="w-full border rounded p-2" rows="2">{{ $expense->notes }}</textarea>
    </div>
</x-modal>
