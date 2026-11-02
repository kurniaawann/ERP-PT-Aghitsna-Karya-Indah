{{--
    Detail Slip Gaji Modal (gaya Filament, read-only, semua status)

    Berisi data karyawan, periode & status, rekap absensi satu bulan
    (berwarna sesuai legend), perhitungan (penerimaan & potongan), iuran
    dibayar perusahaan, catatan, dan penanda tangan.
    Tanpa tombol "Tutup" (tutup lewat X / klik di luar / Escape).
--}}
@php
    $matrix = $slip->attendance_matrix;
    $slipSignatures = $slip->signatures ?: ['disetujui' => null, 'diperiksa' => null, 'dibuat' => null];
    $rupiah = fn ($value) => 'Rp ' . number_format($value ?? 0, 0, ',', '.');
    $dayClasses = [
        'H' => 'bg-success-light text-success border-success',
        'I' => 'bg-warning-light text-warning border-warning',
        'S' => 'bg-error-light text-error border-error',
        'C' => 'bg-purple-100 text-purple-700 border-purple-300',
        'A' => 'bg-surface-hover text-text-label border-border-strong',
        'L' => 'bg-primary-light text-primary border-primary',
    ];
@endphp

<x-fi.modal id="detailModal-{{ $slip->id }}" heading="Detail Slip Gaji"
    :description="($slip->employee->name ?? $slip->employee_code) . ' · ' . $slip->formatted_period" icon="document-text" width="6xl">
    <div class="grid gap-y-5 text-sm">

        {{-- Data karyawan & periode --}}
        <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-gray-500">Nama Karyawan</dt><dd class="font-medium text-gray-950">{{ $slip->employee->name ?? '-' }}</dd></div>
            <div><dt class="text-gray-500">Kode</dt><dd class="font-medium text-gray-950">{{ $slip->employee_code }}</dd></div>
            <div><dt class="text-gray-500">Jabatan</dt><dd class="font-medium text-gray-950">{{ $slip->employee->position ?? '-' }}</dd></div>
            <div><dt class="text-gray-500">Status Karyawan</dt><dd class="font-medium text-gray-950">{{ $slip->employee->status ?? '-' }}</dd></div>
            <div><dt class="text-gray-500">Periode</dt><dd class="font-medium text-gray-950">{{ $slip->formatted_period }}</dd></div>
            <div><dt class="text-gray-500">Status Slip</dt>
                <dd class="mt-0.5">
                    @if ($slip->status === 'draft')
                        <x-fi.badge color="warning">Draft</x-fi.badge>
                    @else
                        <x-fi.badge color="success">Paid · {{ $slip->payment_date ? $slip->payment_date->format('d M Y') : '' }}</x-fi.badge>
                    @endif
                </dd>
            </div>
            <div><dt class="text-gray-500">Uang Transport / Hari</dt><dd class="font-medium text-gray-950">{{ $rupiah($slip->transport_rate) }}</dd></div>
            <div><dt class="text-gray-500">Uang Makan / Hari</dt><dd class="font-medium text-gray-950">{{ $rupiah($slip->meal_rate) }}</dd></div>
        </dl>

        {{-- Rekap absensi satu bulan (read-only) --}}
        <div class="grid gap-y-2">
            <p class="font-medium text-gray-950">Rekap Absensi ({{ $slip->days_in_month }} Hari)</p>
            <div class="grid grid-cols-5 gap-1.5 sm:grid-cols-6 md:grid-cols-10">
                @foreach ($matrix as $day => $status)
                    @php $current = $status ?? 'H'; @endphp
                    <div class="flex flex-col items-center justify-center rounded-lg border py-1.5 status-{{ $current }} {{ $dayClasses[$current] ?? '' }}">
                        <span class="text-[10px] leading-none opacity-70">{{ $day }}</span>
                        <span class="day-letter text-sm font-semibold leading-none">{{ $current }}</span>
                    </div>
                @endforeach
            </div>
            @include('components.sdm.salary-slip.status-legend')
        </div>

        {{-- Perhitungan --}}
        <section class="rounded-lg ring-1 ring-gray-950/5">
            <h3 class="border-b border-gray-200 px-4 py-2.5 font-semibold text-gray-950">Perhitungan</h3>
            <div class="grid gap-y-3 p-4">
                <dl class="grid grid-cols-2 gap-3 md:grid-cols-5">
                    <div><dt class="text-gray-500">Gaji Pokok</dt><dd class="font-medium">{{ $rupiah($slip->base_salary) }}</dd></div>
                    <div><dt class="text-gray-500">Uang Transport ({{ $slip->present_days }} hari hadir)</dt><dd class="font-medium">{{ $rupiah($slip->transport_total) }}</dd></div>
                    <div><dt class="text-gray-500">Uang Makan ({{ $slip->present_days }} hari hadir)</dt><dd class="font-medium">{{ $rupiah($slip->meal_total) }}</dd></div>
                    <div><dt class="text-gray-500">Lembur</dt><dd class="font-medium">{{ $rupiah($slip->overtime_total) }}</dd></div>
                    <div><dt class="text-gray-500">Total Penerimaan</dt><dd class="font-semibold">{{ $rupiah($slip->total_income) }}</dd></div>
                </dl>
                <dl class="grid grid-cols-2 gap-3 border-t border-gray-200 pt-3 md:grid-cols-4">
                    <div><dt class="text-gray-500">BPJS Kesehatan 1% (gaji pokok)</dt><dd class="font-medium text-red-600">- {{ $rupiah($slip->bpjs_kesehatan_employee) }}</dd></div>
                    <div><dt class="text-gray-500">JHT 2% (UMP)</dt><dd class="font-medium text-red-600">- {{ $rupiah($slip->jht_employee) }}</dd></div>
                    <div><dt class="text-gray-500">JPN 1% (UMP)</dt><dd class="font-medium text-red-600">- {{ $rupiah($slip->jpn_employee) }}</dd></div>
                    <div><dt class="text-gray-500">PPh 21</dt><dd class="font-medium text-red-600">- {{ $rupiah($slip->pph21) }}</dd></div>
                </dl>
                <dl class="grid grid-cols-2 gap-3 border-t border-gray-200 pt-3 md:grid-cols-4">
                    <div><dt class="text-gray-500">Cicilan Kasbon</dt>
                        <dd class="font-medium text-red-600">- {{ $rupiah($slip->kasbon_deduction) }}</dd>
                        @if ($slip->kasbon_total > 0)
                            <dd class="text-xs text-gray-500">Total {{ $rupiah($slip->kasbon_total) }} · sisa {{ $rupiah($slip->kasbon_remaining) }}</dd>
                        @endif
                    </div>
                    <div><dt class="text-gray-500">Total Potongan</dt><dd class="font-medium text-red-600">- {{ $rupiah($slip->total_deduction) }}</dd></div>
                    <div><dt class="text-gray-500">Hari Kerja ({{ $slip->work_days }} / {{ $slip->days_in_month }})</dt><dd class="font-medium">{{ $slip->libur_days }} hari libur</dd></div>
                    <div><dt class="text-gray-500">THP</dt><dd class="text-base font-semibold text-primary">{{ $rupiah($slip->net_salary) }}</dd></div>
                </dl>
            </div>
        </section>

        {{-- Iuran dibayar perusahaan --}}
        <section class="rounded-lg ring-1 ring-gray-950/5">
            <h3 class="border-b border-gray-200 px-4 py-2.5 font-semibold text-gray-950">
                Iuran Dibayar Perusahaan <span class="font-normal text-gray-500">(informasi, % dari UMP {{ $rupiah($slip->ump) }})</span>
            </h3>
            <dl class="grid grid-cols-2 gap-3 p-4 md:grid-cols-4">
                <div><dt class="text-gray-500">BPJS Kesehatan 4%</dt><dd class="font-medium">{{ $rupiah($slip->bpjs_kesehatan_company) }}</dd></div>
                <div><dt class="text-gray-500">JHT 3,70%</dt><dd class="font-medium">{{ $rupiah($slip->jht_company) }}</dd></div>
                <div><dt class="text-gray-500">JKK 0,24%</dt><dd class="font-medium">{{ $rupiah($slip->jkk_company) }}</dd></div>
                <div><dt class="text-gray-500">JKM 0,30%</dt><dd class="font-medium">{{ $rupiah($slip->jkm_company) }}</dd></div>
            </dl>
        </section>

        @if ($slip->notes)
            <div class="grid gap-y-1">
                <p class="font-medium text-gray-950">Catatan</p>
                <p class="whitespace-pre-wrap rounded-lg bg-gray-50 p-3 text-gray-700 ring-1 ring-inset ring-gray-950/5">{{ $slip->notes }}</p>
            </div>
        @endif

        {{-- Penanda tangan --}}
        <section class="rounded-lg ring-1 ring-gray-950/5">
            <h3 class="border-b border-gray-200 px-4 py-2.5 font-semibold text-gray-950">Penanda Tangan</h3>
            <div class="grid grid-cols-3 gap-3 p-4 text-center">
                @foreach (['disetujui' => 'Disetujui', 'diperiksa' => 'Diperiksa', 'dibuat' => 'Dibuat'] as $roleKey => $roleLabel)
                    @php $signatory = $slipSignatures[$roleKey] ?? null; @endphp
                    <div>
                        <p class="text-xs text-gray-500">{{ $roleLabel }} oleh</p>
                        <p class="font-medium text-gray-950">{{ $signatory['name'] ?? '-' }}</p>
                        <p class="text-xs text-gray-500">{{ $signatory['position'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-fi.modal>
