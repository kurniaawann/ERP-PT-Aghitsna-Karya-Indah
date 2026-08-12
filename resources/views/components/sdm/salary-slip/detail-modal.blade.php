{{--
    Detail Slip Gaji Modal

    Menampilkan rincian slip gaji secara read-only (semua status). Berisi
    data karyawan, periode, rekap absensi satu bulan, perhitungan lengkap
    (penerimaan & potongan), catatan, dan penanda tangan.
--}}

@php
    $matrix = $slip->attendance_matrix;
    $slipSignatures = $slip->signatures ?: [
        'disetujui' => null,
        'diperiksa' => null,
        'dibuat' => null,
    ];
@endphp

<x-modal id="detailModal-{{ $slip->id }}" title="Detail Slip Gaji" :readonly="true" size="6xl">

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
        <div>
            <label class="block text-text-primary mb-1">Nama Karyawan</label>
            <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-secondary"
                value="{{ $slip->employee->name ?? '-' }}" disabled>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Kode</label>
            <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-secondary"
                value="{{ $slip->employee_code }}" disabled>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Jabatan</label>
            <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-secondary"
                value="{{ $slip->employee->position ?? '-' }}" disabled>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Status Karyawan</label>
            <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-secondary"
                value="{{ $slip->employee->status ?? '-' }}" disabled>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
        <div>
            <label class="block text-text-primary mb-1">Periode</label>
            <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-secondary"
                value="{{ $slip->formatted_period }}" disabled>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Status Slip</label>
            <div class="p-2">
                @if ($slip->status === 'draft')
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-warning-light text-warning">Draft</span>
                @else
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-success-light text-success">
                        Paid · {{ $slip->payment_date ? $slip->payment_date->format('d M Y') : '' }}
                    </span>
                @endif
            </div>
        </div>

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
    </div>

    {{-- Grid absensi satu bulan (read-only) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-2">Rekap Absensi ({{ $slip->days_in_month }} Hari)</label>
        <div class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-10 gap-1.5">
            @foreach ($matrix as $day => $status)
                @php
                    $current = $status ?? 'H';
                @endphp
                <div class="flex flex-col items-center justify-center py-1.5 rounded-lg border status-{{ $current }}">
                    <span class="text-[10px] leading-none opacity-70">{{ $day }}</span>
                    <span class="day-letter font-semibold text-sm leading-none">{{ $current }}</span>
                </div>
            @endforeach
        </div>

        <div class="mt-2 flex items-center gap-3 text-xs text-text-label flex-wrap">
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-success-light text-success font-semibold">H</span> Hadir</span>
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-warning-light text-warning font-semibold">I</span> Izin</span>
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-error-light text-error font-semibold">S</span> Sakit</span>
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-purple-100 text-purple-700 font-semibold">C</span> Cuti</span>
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-surface-hover text-text-label font-semibold">A</span> Alpha</span>
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-primary-light text-primary font-semibold">L</span> Libur</span>
        </div>
    </div>

    {{-- Perhitungan --}}
    <div class="mb-3 p-3 bg-surface-secondary border border-border rounded-lg">
        <p class="text-sm font-semibold text-text-primary mb-2">Perhitungan</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
            <div>
                <span class="text-text-label block">Gaji Pokok</span>
                <span class="font-medium text-text-primary">Rp {{ number_format($slip->base_salary, 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="text-text-label block">Uang Transport ({{ $slip->present_days }} hari hadir)</span>
                <span class="font-medium text-text-primary">Rp {{ number_format($slip->transport_total, 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="text-text-label block">Uang Makan ({{ $slip->present_days }} hari hadir)</span>
                <span class="font-medium text-text-primary">Rp {{ number_format($slip->meal_total, 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="text-text-label block">Total Penerimaan</span>
                <span class="font-medium text-text-primary">Rp {{ number_format($slip->total_income, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3 text-sm border-t border-border pt-3">
            <div>
                <span class="text-text-label block">BPJS Kesehatan 1% (gaji pokok)</span>
                <span class="font-medium text-error">- Rp {{ number_format($slip->bpjs_kesehatan_employee, 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="text-text-label block">JHT 2% (UMP)</span>
                <span class="font-medium text-error">- Rp {{ number_format($slip->jht_employee, 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="text-text-label block">JPN 1% (UMP)</span>
                <span class="font-medium text-error">- Rp {{ number_format($slip->jpn_employee, 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="text-text-label block">PPh 21</span>
                <span class="font-medium text-error">- Rp {{ number_format($slip->pph21, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3 text-sm border-t border-border pt-3">
            <div>
                <span class="text-text-label block">Kasbon</span>
                <span class="font-medium text-error">- Rp {{ number_format($slip->kasbon_deduction, 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="text-text-label block">Total Potongan</span>
                <span class="font-medium text-error">- Rp {{ number_format($slip->total_deduction, 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="text-text-label block">Hari Kerja ({{ $slip->work_days }} / {{ $slip->days_in_month }})</span>
                <span class="font-medium text-text-primary">{{ $slip->libur_days }} hari libur</span>
            </div>
            <div>
                <span class="text-text-label block">THP</span>
                <span class="font-semibold text-primary">Rp {{ number_format($slip->net_salary, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- Iuran dibayar perusahaan --}}
    <div class="mb-3 p-3 bg-surface-secondary border border-border rounded-lg">
        <p class="text-sm font-semibold text-text-primary mb-2">Iuran Dibayar Perusahaan (informasi, % dari UMP Rp {{ number_format($slip->ump, 0, ',', '.') }})</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
            <div>
                <span class="text-text-label block">BPJS Kesehatan 4%</span>
                <span class="font-medium text-text-primary">Rp {{ number_format($slip->bpjs_kesehatan_company, 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="text-text-label block">JHT 3,70%</span>
                <span class="font-medium text-text-primary">Rp {{ number_format($slip->jht_company, 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="text-text-label block">JKK 0,24%</span>
                <span class="font-medium text-text-primary">Rp {{ number_format($slip->jkk_company, 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="text-text-label block">JKM 0,30%</span>
                <span class="font-medium text-text-primary">Rp {{ number_format($slip->jkm_company, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    @if ($slip->notes)
        <div class="mb-3">
            <label class="block text-text-primary mb-1">Catatan</label>
            <div class="p-2 border border-border-strong rounded bg-surface-base text-text-primary text-sm whitespace-pre-wrap">
                {{ $slip->notes }}
            </div>
        </div>
    @endif

    {{-- Penanda tangan --}}
    <div class="mb-3 p-3 bg-surface-secondary border border-border rounded-lg">
        <p class="text-sm font-semibold text-text-primary mb-2">Penanda Tangan</p>
        <div class="grid grid-cols-3 gap-3 text-center">
            @foreach (['disetujui' => 'Disetujui', 'diperiksa' => 'Diperiksa', 'dibuat' => 'Dibuat'] as $roleKey => $roleLabel)
                @php $signatory = $slipSignatures[$roleKey] ?? null; @endphp
                <div>
                    <p class="text-xs text-text-label">{{ $roleLabel }} oleh</p>
                    <p class="font-medium text-text-primary text-sm">
                        {{ $signatory['name'] ?? '-' }}
                    </p>
                    <p class="text-xs text-text-label">{{ $signatory['position'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</x-modal>
