{{-- Modal Tambah Transaksi Laporan Keuangan Proyek (global, dari halaman daftar) --}}
<x-modal id="addModal" title="Tambah Transaksi" action="{{ route('project-financial-report.store') }}"
    method="POST" buttonText="Simpan" enctype="multipart/form-data">

    {{-- Rekap Proyek --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Rekap Proyek <span class="text-error">*</span></label>
        <select name="project_recap_id" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Rekap Proyek tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
            <option value="">-- Pilih Rekap Proyek --</option>
            @foreach ($rekapOptions as $rekap)
                <option value="{{ $rekap->id }}">
                    {{ $rekap->id }} — {{ $rekap->project_name }}{{ $rekap->location ? ' (' . $rekap->location . ')' : '' }}
                </option>
            @endforeach
        </select>
        @if ($rekapOptions->isEmpty())
            <p class="text-xs text-error mt-1">
                Belum ada Rekap Proyek. Buat rekap terlebih dahulu melalui menu
                <a href="{{ route('recap-proyek.index') }}" target="_blank" class="underline">Rekap Proyek</a>.
            </p>
        @endif
    </div>

    {{-- Kategori --}}
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
        @if ($categories->isEmpty())
            <p class="text-xs text-error mt-1">
                Belum ada kategori untuk modul Keuangan Proyek. Buat kategori melalui menu
                <a href="{{ route('transaction-category.index') }}" target="_blank" class="underline">Kategori Transaksi</a>
                (Modul: Keuangan Proyek).
            </p>
        @endif
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
            placeholder="Contoh: Pembayaran material, Termin 1, dll" required maxlength="1000"
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

    {{-- Keterangan Bon --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan Bon <span class="text-error">*</span></label>
        <input type="text" name="keterangan_bon" class="w-full border rounded p-2"
            placeholder="Contoh: Bon Pembelian Material" required maxlength="255"
            oninvalid="this.setCustomValidity('Keterangan Bon tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Bukti Pembayaran --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Bukti Pembayaran</label>
        <input type="file" name="proof_file"
            accept="image/jpeg,image/png,image/gif,image/webp,image/bmp,application/pdf"
            class="w-full border rounded p-2">
        <p class="text-xs text-text-secondary mt-1">Opsional. Format: JPG, PNG, GIF, WEBP, BMP, PDF. Maksimal 5 MB.</p>
    </div>
</x-modal>
