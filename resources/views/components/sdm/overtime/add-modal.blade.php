{{-- Modal Tambah Lembur (Single Select) --}}
<x-modal id="addModal" title="Tambah Lembur" action="{{ route('overtime.store') }}" method="POST" buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Pilih Karyawan <span class="text-error">*</span></label>
        <input type="hidden" name="employee_id" id="add-selected-employee-id" value="">
        <div class="relative">
            <input type="text" id="add-employee-search" placeholder="Cari nama atau divisi..."
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                oninput="debounceFetchEmployees(this.value)" onfocus="openEmployeeDropdown()">
            <div id="add-employee-dropdown"
                class="hidden absolute z-10 w-full mt-1 bg-surface-base border border-border-strong rounded-lg shadow-lg max-h-60 overflow-y-auto">
                <div id="add-employee-list"></div>
                <div id="add-employee-pagination" class="flex justify-between items-center p-2 border-t border-border-strong bg-surface-secondary">
                    <button type="button" id="add-employee-prev" onclick="changeEmployeePage(-1)"
                        class="text-xs px-2 py-1 rounded bg-surface-base hover:bg-surface-hover disabled:opacity-30" disabled>
                        <i class="fa-solid fa-chevron-left"></i> Prev
                    </button>
                    <span id="add-employee-page-info" class="text-xs text-text-secondary">Halaman 1</span>
                    <button type="button" id="add-employee-next" onclick="changeEmployeePage(1)"
                        class="text-xs px-2 py-1 rounded bg-surface-base hover:bg-surface-hover disabled:opacity-30" disabled>
                        Next <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
        <p id="add-employee-selected-name" class="text-xs text-text-secondary mt-1"></p>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="attendance_date" id="add-attendance-date"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Error Message untuk Duplicate Overtime --}}
    <div id="add-duplicate-warning" class="hidden mb-3 p-3 bg-error-light border-l-4 border-error rounded">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-error mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd" />
            </svg>
            <div>
                <p class="font-semibold text-error text-sm mb-1">Data Sudah Ada!</p>
                <p id="add-duplicate-warning-text" class="text-sm text-error"></p>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jam Lembur <span class="text-error">*</span></label>
        <input type="number" name="overtime_hours"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Contoh: 2.5" required min="0.01" max="24" step="0.01" id="add-overtime-hours"
            oninvalid="this.setCustomValidity('Jam lembur tidak boleh kosong')" oninput="this.setCustomValidity('')">
        <p class="text-xs text-text-secondary mt-1">Maksimal 24 jam</p>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tarif per Jam <span class="text-error">*</span></label>
        <input type="text" name="overtime_rate" value="Rp 0"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Masukkan tarif per jam" required inputmode="numeric" id="add-overtime-rate"
            oninvalid="this.setCustomValidity('Tarif tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Total Lembur</label>
        <input type="text" id="add-overtime-total"
            class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-primary" readonly
            value="Rp 0">
        <p class="text-xs text-text-secondary mt-1">Otomatis dihitung: Jam Lembur × Tarif</p>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan</label>
        <textarea name="notes" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Masukkan keterangan (opsional)" rows="3"></textarea>
    </div>
</x-modal>
