{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN MODAL TAMBAH REIMBURSE
     Formulir untuk membuat pengajuan reimbursement baru (Super Admin only).

     "Ambil dari Nota" (opsional): pilih nota yang belum punya pengajuan →
     tanggal, nama proyek, keterangan belanja, total, dan catatan terisi
     otomatis (masih bisa diubah) dan pengajuan tertaut ke nota (id_nota).
     Data opsi: $notaOptions (ReimburseService::getNotaOptionsForReimburse),
     JS: initNotaPicker() di resources/js/pages/finance/reimburse/index.js.
     ═══════════════════════════════════════════════════════════════════════════ --}}
<x-modal id="addModal" title="Tambah Reimburse" action="{{ route('reimburse.store') }}" method="POST" buttonText="Simpan"
    enctype="multipart/form-data">

    {{-- Field: Ambil dari Nota (opsional) --}}
    <div class="mb-3 p-3 bg-surface-secondary border border-border rounded-lg">
        @if (!empty($notaOptions))
            <x-forms.searchable-select name="id_nota" id="addModal-id_nota" label="Ambil dari Nota (Opsional)"
                placeholder="Cari nomor nota / proyek..."
                :options="collect($notaOptions)->map(fn($o) => ['value' => $o['value'], 'label' => $o['label']])->values()" />
            <script type="application/json" id="reimburse-nota-options">@json($notaOptions)</script>
            <div id="reimburse-nota-picked" class="hidden -mt-1 flex items-center justify-between gap-2 text-xs text-success">
                <span><i class="fa-solid fa-circle-check mr-1"></i><span class="reimburse-nota-picked-text"></span></span>
                <button type="button" id="reimburse-nota-clear" class="text-error hover:underline">Batal ambil dari nota</button>
            </div>
            <p class="text-xs text-text-secondary mt-1">Pilih nota → tanggal, nama proyek, keterangan belanja, total, dan catatan
                terisi otomatis (masih bisa diubah). Nota dari "Tambah Nota" tidak otomatis masuk reimbursement — pilih di
                sini. Hanya nota yang belum punya pengajuan reimbursement.</p>
        @else
            <p class="text-sm text-text-primary font-medium mb-1"><i class="fa-solid fa-file-invoice mr-1 text-primary"></i> Ambil dari Nota</p>
            <p class="text-xs text-text-secondary">Belum ada nota yang bisa diambil — semua nota sudah memiliki pengajuan
                reimbursement. Isi form di bawah secara manual.</p>
        @endif
    </div>

    {{-- Field: Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-red-600">*</span></label>
        <input type="date" name="date" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')"
            value="{{ date('Y-m-d') }}">
    </div>

    {{-- Field: Nama Proyek --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Proyek <span class="text-red-600">*</span></label>
        <input type="text" name="project_name" class="w-full border rounded p-2" placeholder="Masukkan nama proyek"
            required maxlength="255" oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Keterangan Belanja --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan Belanja <span class="text-red-600">*</span></label>
        <textarea name="expense_description" class="w-full border rounded p-2"
            placeholder="Contoh: Belanja Alumunium untuk Proyek A, Belanja Handle Paket Solid untuk Proyek B" rows="3"
            required oninvalid="this.setCustomValidity('Keterangan belanja tidak boleh kosong')"
            oninput="this.setCustomValidity('')"></textarea>
        <p class="text-xs text-text-secondary mt-1">Jelaskan detail pengeluaran yang akan direimbursement</p>
    </div>

    {{-- Field: Total Amount --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Total Amount <span class="text-red-600">*</span></label>
        <input type="text" inputmode="numeric" name="total_amount" value="0"
            class="w-full border rounded p-2 reimburse-amount-input"
            placeholder="Masukkan total amount" required min="0"
            oninvalid="this.setCustomValidity('Total amount tidak boleh kosong')" oninput="this.setCustomValidity('')">
        <p class="text-xs text-text-secondary mt-1">Total keseluruhan biaya yang akan direimbursement</p>
    </div>

    {{-- Field: Catatan (Opsional) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan</label>
        <textarea name="notes" class="w-full border rounded p-2" placeholder="Catatan tambahan (opsional)" rows="2"></textarea>
    </div>

    {{-- Field: Bukti (Opsional) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Lampiran Bukti (Foto/File)</label>
        <input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.webp,.gif,.bmp,.pdf,image/*"
            class="w-full border rounded p-2 bg-surface-base text-text-input">
        <p class="text-xs text-text-secondary mt-1">Lampiran bukti (struk, kwitansi, foto, dsb.) — opsional</p>
    </div>
</x-modal>
