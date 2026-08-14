{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN MODAL TAMBAH REIMBURSE
     Formulir untuk membuat pengajuan reimbursement baru (Admin only).
     ═══════════════════════════════════════════════════════════════════════════ --}}
<x-modal id="addModal" title="Tambah Reimburse" action="{{ route('reimburse.store') }}" method="POST" buttonText="Simpan">

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

    {{-- Field: Tanggal Jatuh Tempo --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Jatuh Tempo <span class="text-red-600">*</span></label>
        <input type="date" name="due_date" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal jatuh tempo tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
        <p class="text-xs text-text-secondary mt-1">Tanggal target pencairan/pembayaran reimburse</p>
    </div>

    {{-- Field: Catatan (Opsional) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan</label>
        <textarea name="notes" class="w-full border rounded p-2" placeholder="Catatan tambahan (opsional)" rows="2"></textarea>
    </div>
</x-modal>
