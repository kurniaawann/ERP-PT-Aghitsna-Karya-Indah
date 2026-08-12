{{--
    Edit Slip Gaji Draft Modal

    Modal untuk mengisi/mengubah rekap absensi satu bulan slip gaji draft.
    Tersedia hanya untuk status 'draft'. Setiap hari direpresentasikan tombol
    yang berubah saat diklik dengan urutan: H → I → S → C → A → L → H.

    - H = Hadir (default), I = Izin, S = Sakit, C = Cuti, A = Alpha, L = Libur
      (Minggu & hari libur terpilih saat generate otomatis L, tapi bisa diubah).
    - Nilai disimpan pada hidden input attendance[hari].
    - Ringkasan perhitungan live oleh JS:
        Penerimaan = gaji pokok + (transport × hadir) + (makan × hadir)
        Potongan   = BPJS 1% gaji pokok + JHT 2% UMP + JPN 1% UMP
                     + PPh 21 (input manual) + kasbon pending
        THP        = Penerimaan − Potongan

    Frontend JS: resources/js/pages/sdm/salary-slip/index.js
    (grid day toggle + ringkasan live).
--}}

@php
    $matrix = $slip->attendance_matrix;
@endphp

<x-modal id="editModal-{{ $slip->id }}" title="Edit Rekap Absensi Slip Gaji"
    action="{{ route('salary-slips.update', $slip->id) }}" method="PUT" buttonText="Simpan" size="6xl">

    <input type="hidden" class="slip-calc-data"
        data-base-salary="{{ $slip->base_salary }}"
        data-transport-rate="{{ $slip->transport_rate }}"
        data-meal-rate="{{ $slip->meal_rate }}"
        data-ump="{{ $slip->ump }}"
        data-pph21="{{ $slip->pph21 }}"
        data-kasbon="{{ $slip->kasbon_deduction }}">
    <div class="mb-4 p-4 bg-warning-light border border-warning rounded-lg">
        <div class="flex gap-2">
            <i class="fa-solid fa-triangle-exclamation text-warning mt-1"></i>
            <div class="text-sm text-warning">
                <p class="font-semibold mb-1">Slip draft hanya bisa diubah sebelum dibayar.</p>
                    <p>Klik tiap hari untuk mengganti status: <strong>H</strong>=Hadir, <strong>I</strong>=Izin,
                        <strong>S</strong>=Sakit, <strong>C</strong>=Cuti, <strong>A</strong>=Alpha, <strong>L</strong>=Libur
                        (Minggu &amp; hari libur terpilih otomatis L).</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
        <div>
            <label class="block text-text-primary mb-1">Nama Karyawan</label>
            <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-secondary"
                value="{{ $slip->employee->name ?? '-' }}" disabled>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Jabatan / Status</label>
            <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-secondary"
                value="{{ ($slip->employee->position ?? '-') . ' — ' . ($slip->employee->status ?? '-') }}" disabled>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Periode</label>
            <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-secondary"
                value="{{ $slip->formatted_period }}" disabled>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Gaji Pokok</label>
            <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-secondary"
                value="Rp {{ number_format($slip->base_salary, 0, ',', '.') }}" disabled>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
        <div>
            <label class="block text-text-primary mb-1">Uang Transport / Hari</label>
            <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-secondary"
                value="Rp {{ number_format($slip->transport_rate, 0, ',', '.') }}" disabled>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Uang Makan / Hari</label>
            <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-secondary"
                value="Rp {{ number_format($slip->meal_rate, 0, ',', '.') }}" disabled>
        </div>

        <div>
            <label class="block text-text-primary mb-1">UMP</label>
            <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-secondary"
                value="Rp {{ number_format($slip->ump, 0, ',', '.') }}" disabled>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Kasbon Pending</label>
            <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-error"
                value="Rp {{ number_format($slip->kasbon_deduction, 0, ',', '.') }}" disabled>
        </div>
    </div>

    {{-- Grid Absensi satu bulan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-2">
            Rekap Absensi ({{ $slip->days_in_month }} Hari) <span class="text-error">*</span>
        </label>
        <div class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-10 gap-1.5">
            @foreach ($matrix as $day => $status)
                @php
                    $current = $status ?? 'H';
                @endphp
                <button type="button"
                    class="day-btn flex flex-col items-center justify-center py-1.5 rounded-lg border transition-colors duration-150
                        status-{{ $current }}"
                    data-day="{{ $day }}"
                    data-status="{{ $current }}"
                    title="Hari {{ $day }} — {{ $current }}">
                    <span class="text-[10px] leading-none opacity-70">{{ $day }}</span>
                    <span class="day-letter font-semibold text-sm leading-none">{{ $current }}</span>
                </button>
                <input type="hidden" name="attendance[{{ $day }}]" value="{{ $current }}">
            @endforeach
        </div>

        {{-- Legend --}}
        <div class="mt-2 flex items-center gap-3 text-xs text-text-label flex-wrap">
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-success-light text-success font-semibold">H</span> Hadir</span>
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-warning-light text-warning font-semibold">I</span> Izin</span>
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-error-light text-error font-semibold">S</span> Sakit</span>
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-purple-100 text-purple-700 font-semibold">C</span> Cuti</span>
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-surface-hover text-text-label font-semibold">A</span> Alpha</span>
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-primary-light text-primary font-semibold">L</span> Libur</span>
        </div>
    </div>

    {{-- PPh 21 Manual --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">PPh 21 (Potongan) <span class="text-text-label text-xs">— diisi manual, dibayar karyawan</span></label>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-label text-sm">Rp</span>
            <input type="number" name="pph21" min="0" step="1"
                class="pph21-input w-full border border-border-strong rounded p-2 pl-9 bg-surface-base text-text-input"
                value="{{ $slip->pph21 }}" placeholder="0">
        </div>
    </div>

    {{-- Ringkasan live --}}
    <div class="mb-3 p-3 bg-surface-secondary border border-border rounded-lg">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
            <span>Hadir: <strong class="text-success recap-present">{{ $slip->present_days }}</strong></span>
            <span>Izin: <strong class="text-warning recap-permission">{{ $slip->permission_days }}</strong></span>
            <span>Sakit: <strong class="text-error recap-sick">{{ $slip->sick_days }}</strong></span>
            <span>Cuti: <strong class="text-purple-700 recap-leave">{{ $slip->leave_days }}</strong></span>
            <span>Alpha: <strong class="text-text-label recap-absent">{{ $slip->absent_days }}</strong></span>
            <span>Libur: <strong class="text-primary recap-libur">{{ $slip->libur_days }}</strong></span>
        </div>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mt-2 text-sm border-t border-border pt-2">
            <span>Transport: <strong class="text-text-primary recap-transport">Rp {{ number_format($slip->transport_total, 0, ',', '.') }}</strong></span>
            <span>Makan: <strong class="text-text-primary recap-meal">Rp {{ number_format($slip->meal_total, 0, ',', '.') }}</strong></span>
            <span class="ml-auto">Penerimaan: <strong class="text-text-primary recap-income">Rp {{ number_format($slip->total_income, 0, ',', '.') }}</strong></span>
        </div>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mt-2 text-sm border-t border-border pt-2">
            <span>BPJS Kes 1%: <strong class="text-error recap-bpjs">Rp {{ number_format($slip->bpjs_kesehatan_employee, 0, ',', '.') }}</strong></span>
            <span>JHT 2%: <strong class="text-error recap-jht">Rp {{ number_format($slip->jht_employee, 0, ',', '.') }}</strong></span>
            <span>JPN 1%: <strong class="text-error recap-jpn">Rp {{ number_format($slip->jpn_employee, 0, ',', '.') }}</strong></span>
            <span>PPh 21: <strong class="text-error recap-pph21">Rp {{ number_format($slip->pph21, 0, ',', '.') }}</strong></span>
            <span>Kasbon: <strong class="text-error recap-kasbon">Rp {{ number_format($slip->kasbon_deduction, 0, ',', '.') }}</strong></span>
            <span class="ml-auto">Total Potongan: <strong class="text-error recap-total-deduction">Rp {{ number_format($slip->total_deduction, 0, ',', '.') }}</strong></span>
            <span>THP: <strong class="text-primary recap-net">Rp {{ number_format($slip->net_salary, 0, ',', '.') }}</strong></span>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan</label>
        <textarea name="notes" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            rows="2" placeholder="Catatan tambahan (opsional)">{{ old('notes', $slip->notes) }}</textarea>
    </div>
</x-modal>
