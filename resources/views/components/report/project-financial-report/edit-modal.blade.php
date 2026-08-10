{{-- Modal Edit Transaksi Laporan Keuangan Proyek --}}
<x-modal id="editModal-{{ $item->id }}" title="Edit Transaksi"
    action="{{ route('project-financial-report.update', [$recap, $item]) }}" method="PUT" buttonText="Update"
    enctype="multipart/form-data">

    {{-- Kategori --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kategori <span class="text-error">*</span></label>
        <select name="transaction_category_id" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Kategori tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
            <option value="">-- Pilih Kategori --</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" data-type="{{ $cat->type }}"
                    {{ $item->transaction_category_id == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="transaction_date" class="w-full border rounded p-2"
            value="{{ $item->transaction_date ? \Carbon\Carbon::parse($item->transaction_date)->format('Y-m-d') : '' }}"
            required oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Keterangan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan <span class="text-error">*</span></label>
        <textarea name="description" class="w-full border rounded p-2" rows="3" required maxlength="1000"
            oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')" oninput="this.setCustomValidity('')">{{ $item->description }}</textarea>
    </div>

    {{-- Jumlah --}}
    <div class="mb-3">
        <label class="amount-label block text-text-primary mb-1">Jumlah Pengeluaran <span
                class="text-error">*</span></label>
        <input type="text" inputmode="numeric" name="expense_amount"
            class="w-full border rounded p-2 expense-amount-input"
            value="{{ $item->income_amount ?: $item->expense_amount }}" required min="0"
            oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Keterangan Bon --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan Bon <span class="text-error">*</span></label>
        <input type="text" name="keterangan_bon" class="w-full border rounded p-2"
            value="{{ $item->keterangan_bon }}" required maxlength="255"
            oninvalid="this.setCustomValidity('Keterangan Bon tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Bukti Pembayaran Saat Ini --}}
    @if ($item->hasProof())
        <div class="mb-3 p-3 border rounded bg-blue-50">
            <label class="block text-text-primary mb-1">Bukti Saat Ini</label>
            <a href="{{ asset('storage/' . $item->proof_file) }}" target="_blank" rel="noopener noreferrer"
                class="text-blue-600 hover:underline text-sm">{{ $item->proof_file_name }}</a>
        </div>
    @endif

    {{-- Ganti Bukti Pembayaran (opsional) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Ganti Bukti Pembayaran</label>
        <input type="file" name="proof_file"
            accept="image/jpeg,image/png,image/gif,image/webp,image/bmp,application/pdf"
            class="w-full border rounded p-2">
        <p class="text-xs text-text-secondary mt-1">Format: JPG, PNG, GIF, WEBP, BMP, PDF. Maksimal 5 MB.
            Kosongkan jika tidak ingin mengganti file.</p>
    </div>
</x-modal>
