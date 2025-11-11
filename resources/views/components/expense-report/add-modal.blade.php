{{-- Modal Tambah Expense Report --}}
<x-modal id="addModal" title="Tambah Laporan Pengeluaran" action="{{ route('expense-report.store') }}" method="POST"
    buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Kategori Pengeluaran <span class="text-error">*</span></label>
        <select name="transaction_category_id" class="w-full border rounded p-2" required>
            <option value="">-- Pilih Kategori --</option>
            @foreach ($categories->where('type', 'EXPENSE') as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="transaction_date" class="w-full border rounded p-2" required>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Keterangan <span class="text-error">*</span></label>
        <textarea name="description" class="w-full border rounded p-2" rows="3"
            placeholder="Contoh: Belanja ATK, Sampah Cemara, dll" required></textarea>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Jumlah Pengeluaran <span class="text-error">*</span></label>
        <input type="number" name="expense_amount" class="w-full border rounded p-2" placeholder="Contoh: 50000"
            required min="0">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">No. Faktur (Opsional)</label>
        <input type="text" name="invoice_number" class="w-full border rounded p-2" placeholder="Contoh: INV-001">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Sumber Uang (Opsional)</label>
        <input type="text" name="money_source" class="w-full border rounded p-2"
            placeholder="Contoh: Kas Perusahaan">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Catatan (Opsional)</label>
        <textarea name="notes" class="w-full border rounded p-2" rows="2" placeholder="Catatan tambahan..."></textarea>
    </div>
</x-modal>
