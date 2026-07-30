{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN MODAL EDIT REIMBURSE
     Formulir untuk memperbarui data reimbursement (Admin only, draft only).
     Modal ini dirender per baris untuk setiap reimburse dengan status draft.
     ═══════════════════════════════════════════════════════════════════════════ --}}
<x-modal id="editModal-{{ $reimburse->reimburse_code }}" title="Edit Reimburse"
    action="{{ route('reimburse.update', $reimburse->reimburse_code) }}" method="PUT" buttonText="Update">

    {{-- Field: Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="date"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')"
            value="{{ $reimburse->date->format('Y-m-d') }}">
    </div>

    {{-- Field: Nama Proyek --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Proyek <span class="text-error">*</span></label>
        <input type="text" name="project_name"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Masukkan nama proyek" required maxlength="255"
            oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')" oninput="this.setCustomValidity('')"
            value="{{ $reimburse->project_name }}">
    </div>

    {{-- Field: Keterangan Belanja --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan Belanja <span class="text-error">*</span></label>
        <textarea name="expense_description"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Contoh: Belanja Alumunium untuk Proyek A, Belanja Handle Paket Solid untuk Proyek B" rows="3"
            required oninvalid="this.setCustomValidity('Keterangan belanja tidak boleh kosong')"
            oninput="this.setCustomValidity('')">{{ $reimburse->expense_description }}</textarea>
        <p class="text-xs text-text-secondary mt-1">Jelaskan detail pengeluaran yang akan direimbursement</p>
    </div>

    {{-- Field: Total Amount --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Total Amount <span class="text-error">*</span></label>
        <input type="number" name="total_amount"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Masukkan total amount" required min="0"
            oninvalid="this.setCustomValidity('Total amount tidak boleh kosong')" oninput="this.setCustomValidity('')"
            value="{{ $reimburse->total_amount }}">
        <p class="text-xs text-text-secondary mt-1">Total keseluruhan biaya yang akan direimbursement</p>
    </div>

    {{-- Field: Tanggal Jatuh Tempo --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Jatuh Tempo <span class="text-error">*</span></label>
        <input type="date" name="due_date"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required
            oninvalid="this.setCustomValidity('Tanggal jatuh tempo tidak boleh kosong')"
            oninput="this.setCustomValidity('')" value="{{ $reimburse->due_date->format('Y-m-d') }}">
        <p class="text-xs text-text-secondary mt-1">Tanggal target pencairan/pembayaran reimburse</p>
    </div>

    {{-- Field: Catatan (Opsional) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan</label>
        <textarea name="notes" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Catatan tambahan (opsional)" rows="2">{{ $reimburse->notes }}</textarea>
    </div>
</x-modal>
