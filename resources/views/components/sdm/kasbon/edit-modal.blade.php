{{-- ═══════════════════════════════════════════════════════════════════════
     Komponen Modal Edit Kasbon (gaya Filament)
     Modal per-baris (id editModal{kode}) untuk mengedit kasbon yang belum
     dibayar. Visibilitas field karyawan/divisi/proyek mengikuti jenis kasbon
     lewat toggleEmployeeSelect('edit_{kode}') (style.display pada wrapper).
     ═══════════════════════════════════════════════════════════════════════ --}}
@php
    $p = 'edit_' . $kasbon->kasbon_code;
    $employeeOptions = $employees->map(fn ($e) => [
        'value' => $e->employee_code,
        'label' => $e->name . ' (' . $e->employee_code . ') - ' . ($e->employment_type === 'bulanan' ? 'KARYAWAN KANTOR' : 'TUKANG PROYEK'),
        'type' => $e->employment_type,
    ])->values();
@endphp

<x-fi.modal id="editModal{{ $kasbon->kasbon_code }}" heading="Edit Kasbon" :description="$kasbon->kasbon_code" icon="pencil-square"
    width="xl" :action="route('kasbon.update', $kasbon->kasbon_code)" method="PUT" submitLabel="Update">

    {{-- Jenis Kasbon --}}
    <x-fi.field label="Jenis Kasbon" for="{{ $p }}_kasbon_type" required>
        <x-fi.select name="kasbon_type" id="{{ $p }}_kasbon_type" data-fi-select placeholder="Pilih Jenis"
            :value="$kasbon->kasbon_type" :options="['personal' => 'Per Orang', 'team' => 'Per Tim/Divisi']"
            onchange="toggleEmployeeSelect('{{ $p }}')" />
    </x-fi.field>

    {{-- Karyawan (Personal) --}}
    <div id="{{ $p }}_employee_field" class="grid gap-y-2">
        <p class="flex items-center gap-2 text-sm font-medium leading-6 text-gray-950">
            <span>Karyawan<sup class="ms-0.5 text-red-600">*</sup></span>
            <span id="{{ $p }}_employee_type_label" class="hidden rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10"></span>
        </p>
        <x-fi.searchable-select name="employee_id" id="{{ $p }}_employee_id" invalidMessage="Karyawan" placeholder="Cari karyawan..."
            :options="$employeeOptions" :extraData="['type']" :selected="$kasbon->employee_id" />
    </div>

    {{-- Divisi (Tim) --}}
    <div id="{{ $p }}_division_field" style="display: none;">
        <p class="mb-2 text-sm font-medium leading-6 text-gray-950">Divisi<sup class="ms-0.5 text-red-600">*</sup></p>
        <x-fi.searchable-select name="division" id="{{ $p }}_division" invalidMessage="Divisi" placeholder="Cari divisi..."
            :options="$divisions->map(fn ($d) => ['value' => $d->name, 'label' => $d->name])->values()" :selected="$kasbon->division" />
    </div>

    {{-- Proyek (Tim, satu proyek sesuai data yang sudah ada) --}}
    <div id="{{ $p }}_project_field" style="display: none;">
        <div class="kasbon-project-section grid gap-y-2 rounded-lg bg-gray-50 p-4 ring-1 ring-inset ring-gray-950/5">
            <div class="kasbon-project-toggle flex cursor-pointer select-none items-center justify-between"
                onclick="toggleKasbonProjectSection(this)">
                <span class="text-sm font-medium text-gray-950">Proyek<sup class="ms-0.5 text-red-600">*</sup></span>
                <x-fi.icon name="chevron-down" class="kasbon-project-chevron fa-chevron-down h-5 w-5 text-gray-400 transition-transform" />
            </div>
            <div class="kasbon-project-body hidden">
                <x-fi.searchable-select name="project_names[]" id="{{ $p }}_project" invalidMessage="Proyek" :required="true"
                    placeholder="Cari proyek..." :options="$projects->map(fn ($pr) => ['value' => $pr, 'label' => $pr])->values()"
                    :selected="$kasbon->project_names[0] ?? ''" />
            </div>
            <p class="text-sm text-gray-500">Kasbon divisi otomatis lunas saat payroll proyek tersebut dibayar (tidak bisa dicicil).</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
        {{-- Tanggal Kasbon (personal & tim) --}}
        <x-fi.field label="Tanggal Kasbon" for="{{ $p }}_kasbon_date" required>
            <x-fi.input type="date" name="kasbon_date" id="{{ $p }}_kasbon_date" :value="$kasbon->kasbon_date?->format('Y-m-d')"
                onchange="checkMaxKasbon('{{ $p }}')" />
        </x-fi.field>

        {{-- Jumlah (personal & tim) --}}
        <x-fi.field label="Jumlah Kasbon" for="{{ $p }}_amount" required>
            <x-fi.input inputmode="numeric" name="amount" id="{{ $p }}_amount" prefix="Rp" class="kasbon-amount-input"
                :value="$kasbon->amount" min="1000" step="1000" />
        </x-fi.field>
    </div>

    {{-- Periode payroll (Personal; diisi ulang dari kasbon_date) --}}
    <div id="{{ $p }}_kasbon_detail_field" style="display: none;">
        <input type="hidden" name="week_number" id="{{ $p }}_week_number" value="{{ $kasbon->week_number }}">
        <input type="hidden" name="period_start_date" id="{{ $p }}_period_start_date"
            value="{{ $kasbon->period_start_date ? $kasbon->period_start_date->format('Y-m-d') : '' }}">
        <input type="hidden" name="period_end_date" id="{{ $p }}_period_end_date"
            value="{{ $kasbon->period_end_date ? $kasbon->period_end_date->format('Y-m-d') : '' }}">
    </div>

    {{-- Catatan --}}
    <x-fi.field label="Catatan" for="{{ $p }}_notes">
        <x-fi.textarea id="{{ $p }}_notes" name="notes" rows="3" maxlength="500">{{ $kasbon->notes }}</x-fi.textarea>
    </x-fi.field>
</x-fi.modal>

{{-- Inisialisasi visibilitas field sesuai jenis kasbon saat halaman dimuat --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            toggleEmployeeSelect('{{ $p }}');
        });
    </script>
@endpush
