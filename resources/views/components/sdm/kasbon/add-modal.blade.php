{{-- ═══════════════════════════════════════════════════════════════════════
     Komponen Modal Tambah Kasbon
     Formulir untuk membuat kasbon personal atau tim baru.
     Termasuk toggle jenis, pemilihan karyawan/divisi,
     validasi jumlah, dan resolusi tanggal periode.
     ═══════════════════════════════════════════════════════════════════════ --}}
<x-modal id="addModal" title="Tambah Kasbon" action="{{ route('kasbon.store') }}" method="POST" buttonText="Simpan">

    {{-- Kotak Informasi --}}
    <div class="mb-4 p-4 bg-primary-light border border-primary rounded-lg">
        <div class="flex gap-2">
            <i class="fa-solid fa-info-circle text-primary mt-1"></i>
            <div class="text-sm text-primary">
                <p class="font-semibold mb-1">Informasi Kasbon:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li><strong>Personal:</strong> Kasbon untuk 1 orang karyawan (otomatis dipotong saat generate payroll)</li>
                    <li><strong>Tim:</strong> Kasbon untuk divisi tertentu, diisi per proyek. Setiap proyek disimpan sebagai kasbon terpisah (otomatis lunas saat payroll proyek tersebut dibayar)</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Pilihan Jenis Kasbon --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jenis Kasbon <span class="text-error">*</span></label>
        <select name="kasbon_type" id="add_kasbon_type"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required
            onchange="toggleEmployeeSelect('add')" oninvalid="this.setCustomValidity('Jenis kasbon tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
            <option value="">Pilih Jenis</option>
            <option value="personal">Per Orang</option>
            <option value="team">Per Tim/Divisi</option>
        </select>
    </div>

    {{-- Pilihan Karyawan (Personal) --}}
    <div class="mb-3" id="add_employee_field">
        <label class="block text-text-primary mb-1">Karyawan <span class="text-error">*</span></label>
        <select name="employee_id" id="add_employee_id"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            onchange="checkMaxKasbon('add')">
            <option value="">Pilih Karyawan</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->employee_code }}">{{ $employee->name }} ({{ $employee->employee_code }})</option>
            @endforeach
        </select>
    </div>

    {{-- Detail Kasbon (Personal) --}}
    <div id="add_kasbon_detail_field" style="display: none;">
        <div class="mb-3">
            <label class="block text-text-primary mb-1">Jumlah Kasbon <span class="text-error">*</span></label>
            <input type="text" inputmode="numeric" name="amount" id="add_amount"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input kasbon-amount-input"
                placeholder="Masukkan jumlah kasbon" min="1000" step="1000">
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Periode Kasbon <span class="text-error">*</span></label>
            <input type="date" name="kasbon_date" id="add_kasbon_date"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                value="{{ date('Y-m-d') }}" onchange="checkMaxKasbon('add')">
        </div>

        {{-- Bidang Tersembunyi (dihapus otomatis dari tanggal periode) --}}
        <input type="hidden" name="week_number" id="add_week_number" value="">
        <input type="hidden" name="period_start_date" id="add_period_start_date" value="">
        <input type="hidden" name="period_end_date" id="add_period_end_date" value="">

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Catatan</label>
            <textarea name="notes" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                placeholder="Catatan tambahan" rows="3" maxlength="500"></textarea>
        </div>
    </div>

    {{-- Pilihan Divisi (Tim) --}}
    <div class="mb-3" id="add_division_field" style="display: none;">
        <label class="block text-text-primary mb-1">Divisi <span class="text-error">*</span></label>
        <select name="division" id="add_division"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input">
            <option value="">Pilih Divisi</option>
            @foreach ($divisions as $division)
                <option value="{{ $division->name }}">{{ $division->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Proyek & Detail per Proyek (Tim, dinamis) --}}
    <div class="mb-3" id="add_project_field" style="display: none;">
        <div id="add_project_rows"></div>
        <button type="button" id="add_project_row_btn"
            class="w-full py-2 border border-dashed border-border-strong rounded-lg text-sm text-primary hover:bg-primary-light transition">
            <i class="fa-solid fa-plus mr-1"></i> Tambah Proyek
        </button>
        <p class="text-xs text-text-secondary mt-2">Setiap proyek disimpan sebagai kasbon terpisah dan otomatis lunas saat payroll proyek tersebut dibayar.</p>
    </div>

    {{-- Template Baris Proyek (Tim) --}}
    <template id="add_project_row_template">
        <div class="kasbon-project-row mb-4 p-3 border border-border-light rounded-lg bg-surface-base relative">
            <button type="button" class="remove-project-row absolute top-2 right-2 text-error hover:opacity-70 text-sm"
                title="Hapus proyek">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <x-forms.searchable-select
                name="projects[0][project]"
                id="add_project_select_0"
                label="Proyek"
                :required="true"
                placeholder="Cari proyek..."
                :options="$projects->map(fn($p) => ['value' => $p, 'label' => $p])->values()" />

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-text-primary mb-1">Jumlah Kasbon <span class="text-error">*</span></label>
                    <input type="text" inputmode="numeric" name="projects[0][amount]"
                        class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input kasbon-amount-input"
                        placeholder="Masukkan jumlah" min="1000" step="1000" oninput="formatCurrencyInput(this)">
                </div>
                <div>
                    <label class="block text-text-primary mb-1">Periode Kasbon <span class="text-error">*</span></label>
                    <input type="date" name="projects[0][kasbon_date]"
                        class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input kasbon-project-date"
                        value="{{ date('Y-m-d') }}" onchange="resolveProjectPeriod(this)">
                </div>
            </div>

            <input type="hidden" name="projects[0][period_start_date]" class="kasbon-project-period-start" value="">
            <input type="hidden" name="projects[0][period_end_date]" class="kasbon-project-period-end" value="">

            <div class="mt-3">
                <label class="block text-text-primary mb-1">Catatan</label>
                <textarea name="projects[0][notes]" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                    rows="2" maxlength="500" placeholder="Catatan tambahan"></textarea>
            </div>
        </div>
    </template>
</x-modal>
