{{--
    Edit Slip Gaji Draft Modal (gaya Filament)

    Mengisi/mengubah rekap absensi satu bulan slip gaji draft (hanya status
    'draft'). Tiap hari = tombol yang berputar saat diklik: H → I → S → C → A → L.
    - Nilai disimpan pada hidden input attendance[hari].
    - Ringkasan perhitungan live oleh JS (.slip-calc-data, .recap-*):
        Penerimaan = gaji pokok + (transport × hadir) + (makan × hadir) + lembur
        Potongan   = BPJS 1% gaji pokok + JHT 2% UMP + JPN 1% UMP + PPh 21 + cicilan kasbon
        THP        = Penerimaan − Potongan
    - Cicilan kasbon (kasbon_installment): default = seluruh sisa, maksimal = sisa.

    Frontend JS: resources/js/pages/sdm/salary-slip/index.js (initAttendanceGrid:
    .day-btn, .pph21-input, .kasbon-installment-input, .recap-kasbon-remaining-input).
--}}
@php
    $matrix = $slip->attendance_matrix;
    $rupiah = fn ($value) => 'Rp ' . number_format($value ?? 0, 0, ',', '.');
    $sid = $slip->id;
@endphp

<x-fi.modal id="editModal-{{ $sid }}" heading="Edit Rekap Absensi Slip Gaji"
    :description="($slip->employee->name ?? $slip->employee_code) . ' · ' . $slip->formatted_period" icon="pencil-square"
    width="6xl" :action="route('salary-slips.update', $sid)" method="PUT">

    <input type="hidden" class="slip-calc-data"
        data-base-salary="{{ $slip->base_salary }}"
        data-transport-rate="{{ $slip->transport_rate }}"
        data-meal-rate="{{ $slip->meal_rate }}"
        data-ump="{{ $slip->ump }}"
        data-pph21="{{ $slip->pph21 }}"
        data-overtime="{{ $slip->overtime_total }}"
        data-kasbon-total="{{ $slip->kasbon_total }}"
        data-kasbon="{{ $slip->kasbon_deduction }}">

    <div class="flex gap-3 rounded-lg bg-amber-50 p-4 text-sm ring-1 ring-inset ring-amber-600/20">
        <x-fi.icon name="exclamation-triangle" class="h-5 w-5 shrink-0 text-amber-600" />
        <div class="text-amber-800">
            <p class="font-semibold">Slip draft hanya bisa diubah sebelum dibayar.</p>
            <p class="mt-0.5">Klik tiap hari untuk mengganti status: <strong>H</strong>=Hadir, <strong>I</strong>=Izin,
                <strong>S</strong>=Sakit, <strong>C</strong>=Cuti, <strong>A</strong>=Alpha, <strong>L</strong>=Libur
                (Minggu &amp; hari libur terpilih otomatis L).</p>
        </div>
    </div>

    {{-- Data karyawan & tarif (read-only) --}}
    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-fi.field label="Nama Karyawan"><x-fi.input :value="$slip->employee->name ?? '-'" disabled /></x-fi.field>
        <x-fi.field label="Jabatan / Status">
            <x-fi.input :value="($slip->employee->position ?? '-') . ' — ' . ($slip->employee->status ?? '-')" disabled />
        </x-fi.field>
        <x-fi.field label="Periode"><x-fi.input :value="$slip->formatted_period" disabled /></x-fi.field>
        <x-fi.field label="Gaji Pokok"><x-fi.input prefix="Rp" :value="number_format($slip->base_salary, 0, ',', '.')" disabled /></x-fi.field>
        <x-fi.field label="Uang Transport / Hari"><x-fi.input prefix="Rp" :value="number_format($slip->transport_rate, 0, ',', '.')" disabled /></x-fi.field>
        <x-fi.field label="Uang Makan / Hari"><x-fi.input prefix="Rp" :value="number_format($slip->meal_rate, 0, ',', '.')" disabled /></x-fi.field>
        <x-fi.field label="UMP"><x-fi.input prefix="Rp" :value="number_format($slip->ump, 0, ',', '.')" disabled /></x-fi.field>
        <x-fi.field label="Lembur Bulan Ini" hint="Otomatis dari modul Lembur.">
            <x-fi.input prefix="Rp" :value="number_format($slip->overtime_total, 0, ',', '.')" disabled />
        </x-fi.field>
    </div>

    {{-- Kasbon (bisa dicicil): total sisa → cicilan bulan ini → sisa --}}
    <section class="rounded-lg ring-1 ring-gray-950/5">
        <header class="flex items-center gap-3 border-b border-gray-200 px-4 py-3">
            <x-fi.icon name="banknotes" class="h-5 w-5 text-gray-400" />
            <h3 class="text-sm font-semibold text-gray-950">Kasbon</h3>
        </header>
        <div class="grid grid-cols-1 gap-x-4 gap-y-5 p-4 md:grid-cols-3">
            <x-fi.field label="Total Sisa Kasbon">
                <x-fi.input prefix="Rp" :value="number_format($slip->kasbon_total, 0, ',', '.')" disabled />
            </x-fi.field>
            <x-fi.field label="Cicilan Bulan Ini" for="kasbon-installment-{{ $sid }}"
                :hint="'Default seluruh sisa kasbon, maksimal Rp ' . number_format($slip->kasbon_total, 0, ',', '.') . '.'">
                <x-fi.input type="number" id="kasbon-installment-{{ $sid }}" name="kasbon_installment" prefix="Rp"
                    class="kasbon-installment-input" min="0" :max="$slip->kasbon_total" step="1" placeholder="0"
                    :value="$slip->kasbon_deduction" :disabled="$slip->kasbon_total <= 0" />
            </x-fi.field>
            <x-fi.field label="Sisa Setelah Cicilan">
                <x-fi.input class="recap-kasbon-remaining-input" :value="$rupiah($slip->kasbon_remaining)" disabled />
            </x-fi.field>
        </div>
    </section>

    {{-- Grid absensi satu bulan --}}
    <div class="grid gap-y-2">
        <p class="text-sm font-medium text-gray-950">Rekap Absensi ({{ $slip->days_in_month }} Hari)<sup class="ms-0.5 text-red-600">*</sup></p>
        <div class="grid grid-cols-5 gap-1.5 sm:grid-cols-6 md:grid-cols-10">
            @foreach ($matrix as $day => $status)
                @php $current = $status ?? 'H'; @endphp
                <button type="button"
                    class="day-btn flex flex-col items-center justify-center rounded-lg border py-1.5 transition-colors duration-150 status-{{ $current }}"
                    data-day="{{ $day }}" data-status="{{ $current }}" title="Hari {{ $day }} — {{ $current }}">
                    <span class="text-[10px] leading-none opacity-70">{{ $day }}</span>
                    <span class="day-letter text-sm font-semibold leading-none">{{ $current }}</span>
                </button>
                <input type="hidden" name="attendance[{{ $day }}]" value="{{ $current }}">
            @endforeach
        </div>
        @include('components.sdm.salary-slip.status-legend')
    </div>

    {{-- PPh 21 manual --}}
    <x-fi.field label="PPh 21 (Potongan)" for="pph21-{{ $sid }}" hint="Diisi manual, dibayar karyawan.">
        <x-fi.input type="number" id="pph21-{{ $sid }}" name="pph21" prefix="Rp" class="pph21-input" min="0" step="1"
            :value="$slip->pph21" placeholder="0" />
    </x-fi.field>

    {{-- Ringkasan live (.recap-* diperbarui JS) --}}
    <div class="grid gap-y-2 rounded-lg bg-gray-50 p-4 text-sm ring-1 ring-inset ring-gray-950/5">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
            <span>Hadir: <strong class="text-success recap-present">{{ $slip->present_days }}</strong></span>
            <span>Izin: <strong class="text-warning recap-permission">{{ $slip->permission_days }}</strong></span>
            <span>Sakit: <strong class="text-error recap-sick">{{ $slip->sick_days }}</strong></span>
            <span>Cuti: <strong class="text-purple-700 recap-leave">{{ $slip->leave_days }}</strong></span>
            <span>Alpha: <strong class="text-gray-500 recap-absent">{{ $slip->absent_days }}</strong></span>
            <span>Libur: <strong class="text-primary recap-libur">{{ $slip->libur_days }}</strong></span>
        </div>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-gray-200 pt-2">
            <span>Transport: <strong class="recap-transport">{{ $rupiah($slip->transport_total) }}</strong></span>
            <span>Makan: <strong class="recap-meal">{{ $rupiah($slip->meal_total) }}</strong></span>
            <span>Lembur: <strong class="recap-overtime">{{ $rupiah($slip->overtime_total) }}</strong></span>
            <span class="ms-auto">Penerimaan: <strong class="recap-income">{{ $rupiah($slip->total_income) }}</strong></span>
        </div>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-gray-200 pt-2">
            <span>BPJS Kes 1%: <strong class="text-red-600 recap-bpjs">{{ $rupiah($slip->bpjs_kesehatan_employee) }}</strong></span>
            <span>JHT 2%: <strong class="text-red-600 recap-jht">{{ $rupiah($slip->jht_employee) }}</strong></span>
            <span>JPN 1%: <strong class="text-red-600 recap-jpn">{{ $rupiah($slip->jpn_employee) }}</strong></span>
            <span>PPh 21: <strong class="text-red-600 recap-pph21">{{ $rupiah($slip->pph21) }}</strong></span>
            <span>Cicilan Kasbon: <strong class="text-red-600 recap-kasbon">{{ $rupiah($slip->kasbon_deduction) }}</strong></span>
            <span class="ms-auto">Total Potongan: <strong class="text-red-600 recap-total-deduction">{{ $rupiah($slip->total_deduction) }}</strong></span>
            <span>THP: <strong class="text-primary recap-net">{{ $rupiah($slip->net_salary) }}</strong></span>
        </div>
    </div>

    <x-fi.field label="Catatan" for="slip-notes-{{ $sid }}">
        <x-fi.textarea id="slip-notes-{{ $sid }}" name="notes" rows="2"
            placeholder="Catatan tambahan (opsional)">{{ old('notes', $slip->notes) }}</x-fi.textarea>
    </x-fi.field>
</x-fi.modal>
