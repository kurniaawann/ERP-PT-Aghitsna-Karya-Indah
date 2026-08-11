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
        <label class="block text-text-primary mb-1">Karyawan <span class="text-error">*</span></label>
        <select name="employee_id" id="edit_{{ $kasbon->kasbon_code }}_employee_id" class="w-full border rounded p-2">
            <option value="">Pilih Karyawan</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->employee_code }}"
                    {{ $kasbon->employee_id === $employee->employee_code ? 'selected' : '' }}>
                    {{ $employee->name }} ({{ $employee->employee_code }})
                </option>
            @endforeach
        </select>
    </div>

    {{-- Pilihan Divisi (Tim) --}}
    <div class="mb-3" id="edit_{{ $kasbon->kasbon_code }}_division_field" style="display: none;">
        <label class="block text-text-primary mb-1">Divisi <span class="text-error">*</span></label>
        <select name="division" id="edit_{{ $kasbon->kasbon_code }}_division" class="w-full border rounded p-2">
            <option value="">Pilih Divisi</option>
            @foreach ($divisions as $division)
                <option value="{{ $division->name }}" {{ $kasbon->division === $division->name ? 'selected' : '' }}>
                    {{ $division->name }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Pilihan Proyek (Tim, satu proyek sesuai data yang sudah ada) --}}
    <div class="mb-3" id="edit_{{ $kasbon->kasbon_code }}_project_field" style="display: none;">
        <x-forms.searchable-select
            name="project_names[]"
            id="edit_{{ $kasbon->kasbon_code }}_project"
            label="Proyek"
            :required="true"
            placeholder="Cari proyek..."
            :options="$projects->map(fn($p) => ['value' => $p, 'label' => $p])->values()"
            :selected="$kasbon->project_names[0] ?? ''" />
        <p class="text-xs text-text-secondary mt-1">Kasbon divisi otomatis lunas saat payroll proyek tersebut dibayar (tidak bisa dicicil).</p>
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
        {{-- Periode Kasbon --}}
        <div class="mb-3">
            <label class="block text-text-primary mb-1">Periode Kasbon <span class="text-error">*</span></label>
            <input type="date" name="kasbon_date" id="edit_{{ $kasbon->kasbon_code }}_kasbon_date"
                class="w-full border rounded p-2"
                value="{{ $kasbon->kasbon_date->format('Y-m-d') }}"
                onchange="checkMaxKasbon('edit_{{ $kasbon->kasbon_code }}')">
        </div>

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
