{{-- Modal Tambah Expense Report --}}
<x-modal id="addModal" title="Tambah Rekap Pengeluaran" action="{{ route('recap-expense.store') }}" method="POST"
    buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kategori Pengeluaran <span class="text-error">*</span></label>
        <select name="transaction_category_id" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Kategori pengeluaran tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
            <option value="">-- Pilih Kategori --</option>
            @foreach ($categories->where('type', 'EXPENSE') as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="transaction_date" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan <span class="text-error">*</span></label>
        <textarea name="description" class="w-full border rounded p-2" rows="3"
            placeholder="Contoh: Belanja ATK, Sampah Cemara, dll" required maxlength="1000"
            oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')" oninput="this.setCustomValidity('')"></textarea>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah Pengeluaran <span class="text-error">*</span></label>
        <input type="number" name="expense_amount" class="w-full border rounded p-2" placeholder="Contoh: 50000"
            required min="0" oninvalid="this.setCustomValidity('Jumlah pengeluaran tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">No. Faktur (Opsional)</label>
        <input type="text" name="invoice_number" class="w-full border rounded p-2" placeholder="Contoh: INV-001"
            maxlength="100">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Sumber Uang (Opsional)</label>
        <input type="text" name="money_source" class="w-full border rounded p-2" placeholder="Contoh: Kas Perusahaan"
            maxlength="255">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan (Opsional)</label>
        <textarea name="notes" class="w-full border rounded p-2" rows="2" placeholder="Catatan tambahan..."></textarea>
    </div>
</x-modal>
