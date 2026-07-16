{{-- Modal Edit Kasbon --}}
<x-modal id="editModal{{ $kasbon->kasbon_code }}" title="Edit Kasbon"
    action="{{ route('kasbon.update', $kasbon->kasbon_code) }}" method="PUT" buttonText="Update">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jenis Kasbon <span class="text-error">*</span></label>
        <select name="kasbon_type" id="edit_kasbon_type_{{ $kasbon->kasbon_code }}" class="w-full border rounded p-2"
            required onchange="toggleEmployeeSelect('edit_{{ $kasbon->kasbon_code }}')">
            <option value="">Pilih Jenis</option>
            <option value="personal" {{ $kasbon->kasbon_type === 'personal' ? 'selected' : '' }}>Per Orang</option>
            <option value="team" {{ $kasbon->kasbon_type === 'team' ? 'selected' : '' }}>Per Tim/Divisi</option>
        </select>
    </div>

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

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah Kasbon <span class="text-error">*</span></label>
        <input type="text" inputmode="numeric" name="amount" class="w-full border rounded p-2 kasbon-amount-input"
            value="{{ $kasbon->amount }}" required min="1000" step="1000">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Kasbon <span class="text-error">*</span></label>
        <input type="date" name="kasbon_date" class="w-full border rounded p-2"
            value="{{ $kasbon->kasbon_date->format('Y-m-d') }}" required>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Bulan <span class="text-error">*</span></label>
            <select name="period_month" class="w-full border rounded p-2" required>
                @for ($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ $kasbon->period_month == $i ? 'selected' : '' }}>
                        {{ DateTime::createFromFormat('!m', $i)->format('M') }}
                    </option>
                @endfor
            </select>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Tahun <span class="text-error">*</span></label>
            <input type="number" name="period_year" class="w-full border rounded p-2"
                value="{{ $kasbon->period_year }}" required min="2020" max="2100">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Minggu</label>
            <select name="week_number" class="w-full border rounded p-2">
                <option value="">-</option>
                <option value="1" {{ $kasbon->week_number == 1 ? 'selected' : '' }}>1</option>
                <option value="2" {{ $kasbon->week_number == 2 ? 'selected' : '' }}>2</option>
                <option value="3" {{ $kasbon->week_number == 3 ? 'selected' : '' }}>3</option>
                <option value="4" {{ $kasbon->week_number == 4 ? 'selected' : '' }}>4</option>
            </select>
        </div>
    </div>

    {{-- Hidden inputs for period date range --}}
    <input type="hidden" name="period_start_date"
        value="{{ $kasbon->period_start_date ? $kasbon->period_start_date->format('Y-m-d') : '' }}">
    <input type="hidden" name="period_end_date"
        value="{{ $kasbon->period_end_date ? $kasbon->period_end_date->format('Y-m-d') : '' }}">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan</label>
        <textarea name="notes" class="w-full border rounded p-2" rows="3" maxlength="500">{{ $kasbon->notes }}</textarea>
    </div>
</x-modal>

<script>
    // Initialize employee field visibility on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleEmployeeSelect('edit_{{ $kasbon->kasbon_code }}');
    });
</script>
