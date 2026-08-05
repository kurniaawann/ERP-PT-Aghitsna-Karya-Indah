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
                    <li><strong>Tim:</strong> Kasbon untuk divisi tertentu (direkap di cetakan payroll, tidak dipotong per karyawan)</li>
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

    {{-- Input Jumlah --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah Kasbon <span class="text-error">*</span></label>
        <input type="text" inputmode="numeric" name="amount" id="add_amount"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input kasbon-amount-input"
            placeholder="Masukkan jumlah kasbon" required min="1000" step="1000">
    </div>

    {{-- Periode Kasbon --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Periode Kasbon <span class="text-error">*</span></label>
        <input type="date" name="kasbon_date" id="add_kasbon_date"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            value="{{ date('Y-m-d') }}" required oninvalid="this.setCustomValidity('Periode kasbon tidak boleh kosong')"
            oninput="this.setCustomValidity('')" onchange="checkMaxKasbon('add')">
    </div>

    {{-- Bidang Tersembunyi (dihapus otomatis dari tanggal periode) --}}
    <input type="hidden" name="week_number" id="add_week_number" value="">
    <input type="hidden" name="period_start_date" id="add_period_start_date" value="">
    <input type="hidden" name="period_end_date" id="add_period_end_date" value="">

    {{-- Catatan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan</label>
        <textarea name="notes" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Catatan tambahan" rows="3" maxlength="500"></textarea>
    </div>
</x-modal>
