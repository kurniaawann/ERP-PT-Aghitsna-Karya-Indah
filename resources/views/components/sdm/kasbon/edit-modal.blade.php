{{-- ═══════════════════════════════════════════════════════════════════════
     Komponen Modal Edit Kasbon
     Modal per-baris untuk mengedit kasbon yang tertunda.
     Mengisi formulir dengan data kasbon saat ini dan menginisialisasi
     visibilitas bidang karyawan/divisi berdasarkan jenis kasbon.
     ═══════════════════════════════════════════════════════════════════════ --}}
<x-modal id="editModal{{ $kasbon->kasbon_code }}" title="Edit Kasbon"
    action="{{ route('kasbon.update', $kasbon->kasbon_code) }}" method="PUT" buttonText="Update">

    {{-- Pilihan Jenis Kasbon --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jenis Kasbon <span class="text-error">*</span></label>
        <select name="kasbon_type" id="edit_{{ $kasbon->kasbon_code }}_kasbon_type" class="w-full border rounded p-2"
            onchange="toggleEmployeeSelect('edit_{{ $kasbon->kasbon_code }}')">
            <option value="">Pilih Jenis</option>
            <option value="personal" {{ $kasbon->kasbon_type === 'personal' ? 'selected' : '' }}>Per Orang</option>
            <option value="team" {{ $kasbon->kasbon_type === 'team' ? 'selected' : '' }}>Per Tim/Divisi</option>
        </select>
    </div>

    {{-- Pilihan Karyawan (Personal) --}}
    <div class="mb-3" id="edit_{{ $kasbon->kasbon_code }}_employee_field">
        <label class="flex items-center gap-2 text-text-primary mb-1">
            <span>Karyawan <span class="text-error">*</span></span>
            <span id="edit_{{ $kasbon->kasbon_code }}_employee_type_label"
                class="hidden px-2 py-0.5 bg-primary-light text-primary text-xs rounded-full"></span>
        </label>
        <x-forms.searchable-select name="employee_id" id="edit_{{ $kasbon->kasbon_code }}_employee_id" :label="''"
            invalidMessage="Karyawan" placeholder="Cari karyawan..."
            :options="$employees->map(fn($e) => ['value' => $e->employee_code, 'label' => $e->name . ' (' . $e->employee_code . ') - ' . ($e->employment_type === 'bulanan' ? 'Gaji Bulanan' : 'Gaji Harian'), 'type' => $e->employment_type])->values()"
            :extraData="['type']"
            :selected="$kasbon->employee_id" />
    </div>

    {{-- Pilihan Divisi (Tim) --}}
    <div class="mb-3" id="edit_{{ $kasbon->kasbon_code }}_division_field" style="display: none;">
        <label class="block text-text-primary mb-1">Divisi <span class="text-error">*</span></label>
        <x-forms.searchable-select name="division" id="edit_{{ $kasbon->kasbon_code }}_division" :label="''"
            invalidMessage="Divisi" placeholder="Cari divisi..."
            :options="$divisions->map(fn($d) => ['value' => $d->name, 'label' => $d->name])->values()"
            :selected="$kasbon->division" />
    </div>

    {{-- Pilihan Proyek (Tim, satu proyek sesuai data yang sudah ada) --}}
    <div class="mb-3" id="edit_{{ $kasbon->kasbon_code }}_project_field" style="display: none;">
        <div class="kasbon-project-section">
            <div class="flex items-center justify-between cursor-pointer select-none kasbon-project-toggle mb-1 mr-3"
                onclick="toggleKasbonProjectSection(this)">
                <span class="text-text-primary text-sm font-medium">Proyek <span class="text-error">*</span></span>
                <i class="fa-solid fa-chevron-down kasbon-project-chevron text-xs text-text-secondary"></i>
            </div>
            <div class="kasbon-project-body hidden">
                <x-forms.searchable-select
                    name="project_names[]"
                    id="edit_{{ $kasbon->kasbon_code }}_project"
                    label=""
                    invalidMessage="Proyek"
                    :required="true"
                    placeholder="Cari proyek..."
                    :options="$projects->map(fn($p) => ['value' => $p, 'label' => $p])->values()"
                    :selected="$kasbon->project_names[0] ?? ''" />
            </div>
            <p class="text-xs text-text-secondary mt-1">Kasbon divisi otomatis lunas saat payroll proyek tersebut dibayar (tidak bisa dicicil).</p>
        </div>
    </div>

    {{-- Tanggal Kasbon (untuk personal & tim) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Kasbon <span class="text-error">*</span></label>
        <input type="date" name="kasbon_date" id="edit_{{ $kasbon->kasbon_code }}_kasbon_date"
            class="w-full border rounded p-2"
            value="{{ $kasbon->kasbon_date?->format('Y-m-d') }}"
            onchange="checkMaxKasbon('edit_{{ $kasbon->kasbon_code }}')">
    </div>

    {{-- Input Jumlah (untuk kasbon personal & tim) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah Kasbon <span class="text-error">*</span></label>
        <input type="text" inputmode="numeric" name="amount" id="edit_{{ $kasbon->kasbon_code }}_amount"
            class="w-full border rounded p-2 kasbon-amount-input"
            value="{{ $kasbon->amount }}" min="1000" step="1000">
    </div>

    {{-- Detail Kasbon (Personal) --}}
    <div id="edit_{{ $kasbon->kasbon_code }}_kasbon_detail_field" style="display: none;">
        {{-- Bidang Tersembunyi untuk Periode (diisi ulang dari kasbon_date) --}}
        <input type="hidden" name="week_number" id="edit_{{ $kasbon->kasbon_code }}_week_number"
            value="{{ $kasbon->week_number }}">
        <input type="hidden" name="period_start_date" id="edit_{{ $kasbon->kasbon_code }}_period_start_date"
            value="{{ $kasbon->period_start_date ? $kasbon->period_start_date->format('Y-m-d') : '' }}">
        <input type="hidden" name="period_end_date" id="edit_{{ $kasbon->kasbon_code }}_period_end_date"
            value="{{ $kasbon->period_end_date ? $kasbon->period_end_date->format('Y-m-d') : '' }}">
    </div>

    {{-- Catatan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan</label>
        <textarea name="notes" class="w-full border rounded p-2" rows="3" maxlength="500">{{ $kasbon->notes }}</textarea>
    </div>
</x-modal>

{{-- Inisialisasi visibilitas bidang karyawan saat halaman dimuat --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        toggleEmployeeSelect('edit_{{ $kasbon->kasbon_code }}');
    });
</script>
