{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN MODAL EDIT REIMBURSE
     Formulir untuk memperbarui data reimbursement (Admin only, draft only).
     Modal ini dirender per baris untuk setiap reimburse dengan status draft.
     Pengajuan otomatis dari Nota menampilkan info nota sumber (badge
     "Dari Nota" + link pratinjau PDF nota).
     ═══════════════════════════════════════════════════════════════════════════ --}}
<x-modal id="editModal-{{ $reimburse->reimburse_code }}" title="Edit Reimburse"
    action="{{ route('reimburse.update', $reimburse->reimburse_code) }}" method="PUT" buttonText="Update"
    enctype="multipart/form-data">

    {{-- Info Nota Sumber (pengajuan otomatis dari Nota Super Admin) --}}
    @if ($reimburse->is_from_nota)
        <div class="mb-4 p-3 rounded-lg border border-blue-200 bg-blue-50 text-sm text-text-primary">
            <div class="flex flex-wrap items-center gap-2 mb-1">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-800">Dari Nota</span>
                @include('components.finance.reimburse.nota-link', ['reimburse' => $reimburse])
            </div>
            <p class="text-xs text-text-secondary">
                Pengajuan ini dibuat otomatis dari nota. Selama masih Draft, perubahan nota akan
                memperbarui tanggal, nama proyek, keterangan belanja, dan total di sini.
            </p>
        </div>
    @endif

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
        <input type="text" inputmode="numeric" name="total_amount"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input reimburse-amount-input"
            placeholder="Masukkan total amount" required min="0"
            oninvalid="this.setCustomValidity('Total amount tidak boleh kosong')" oninput="this.setCustomValidity('')"
            value="{{ number_format($reimburse->total_amount, 0, ',', '.') }}">
        <p class="text-xs text-text-secondary mt-1">Total keseluruhan biaya yang akan direimbursement</p>
    </div>

    {{-- Field: Catatan (Opsional) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan</label>
        <textarea name="notes" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Catatan tambahan (opsional)" rows="2">{{ $reimburse->notes }}</textarea>
    </div>

    {{-- Field: Bukti (Opsional) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Lampiran Bukti (Foto/File)</label>
        @if ($reimburse->proof_file)
            <div class="mb-2 flex items-center gap-2 text-sm">
                <i class="fa-solid fa-paperclip text-text-secondary"></i>
                {{-- Pratinjau lampiran di dalam halaman (tanpa tab baru) --}}
                <a href="{{ $reimburse->proof_url }}"
                    onclick="event.preventDefault(); window.openFilePreview(this.href, { title: @js('Bukti Reimburse ' . $reimburse->reimburse_code), downloadName: @js($reimburse->proof_file_name ?: basename($reimburse->proof_file)) })"
                    class="text-primary underline hover:text-primary-hover">
                    {{ $reimburse->proof_file_name ?: 'Lihat Lampiran' }}
                </a>
            </div>
        @endif
        <input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.webp,.gif,.bmp,.pdf,image/*"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input">
        <p class="text-xs text-text-secondary mt-1">Biarkan kosong jika tidak ingin mengubah lampiran</p>
    </div>
</x-modal>
