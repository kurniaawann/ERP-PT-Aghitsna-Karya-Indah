{{-- Modal Tambah Rekap Pengeluaran (admin: "Kas Kantor") --}}
@php
    $recapLabel = $recapLabel ?? 'Rekap Pengeluaran';
    // Nomor faktur hanya dipakai super admin (admin/Kas Kantor tanpa kolom faktur)
    $usesInvoiceNumber = !auth()->user()->isAdmin();
@endphp
<x-modal id="addModal" title="Tambah {{ $recapLabel }}" action="{{ route('recap-expense.store') }}" method="POST"
    buttonText="Simpan">

    {{-- Kategori Pengeluaran --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kategori <span class="text-error">*</span></label>
        <select name="transaction_category_id" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Kategori tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
            <option value="">-- Pilih Kategori --</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" data-type="{{ $cat->type }}">{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="transaction_date" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Keterangan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan <span class="text-error">*</span></label>
        <textarea name="description" class="w-full border rounded p-2" rows="3"
            placeholder="Contoh: Belanja ATK, Sampah Cemara, dll" required maxlength="1000"
            oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')" oninput="this.setCustomValidity('')"></textarea>
    </div>

    {{-- Jumlah --}}
    <div class="mb-3">
        <label class="amount-label block text-text-primary mb-1">Jumlah Pengeluaran <span
                class="text-error">*</span></label>
        <input type="text" inputmode="numeric" name="expense_amount"
            class="w-full border rounded p-2 expense-amount-input" placeholder="Contoh: 50000" required min="0"
            oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Nomor Faktur: hanya untuk kategori uang masuk (di-generate otomatis, read-only).
         Uang keluar tidak memiliki nomor faktur — bagian ini disembunyikan oleh JS. --}}
    @if ($usesInvoiceNumber)
        <div class="invoice-section hidden mb-3">
            <label class="block text-text-primary mb-1">No. Faktur</label>
            <input type="text" class="w-full border rounded p-2 bg-surface-secondary text-text-secondary"
                value="{{ $nextIncomeInvoiceNumber ?? '' }}" placeholder="Di-generate otomatis saat disimpan"
                readonly tabindex="-1">
            <p class="invoice-note text-xs text-text-secondary mt-1">Khusus uang masuk — nomor faktur di-generate
                otomatis saat disimpan.</p>
        </div>
    @endif

    {{-- Sumber Uang --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Sumber Uang (Opsional)</label>
        <input type="text" name="money_source" class="w-full border rounded p-2" placeholder="Contoh: Kas Perusahaan"
            maxlength="255">
    </div>

    {{-- Catatan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan (Opsional)</label>
        <textarea name="notes" class="w-full border rounded p-2" rows="2" placeholder="Catatan tambahan..."></textarea>
    </div>
</x-modal>
