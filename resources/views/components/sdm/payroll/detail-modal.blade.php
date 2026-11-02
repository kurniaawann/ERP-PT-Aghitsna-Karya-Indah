{{--
    Detail Payroll Modal (gaya Filament, read-only)

    Sections:
    1. Informasi Karyawan  - nama, kode, jabatan, periode, proyek
    2. Rekap Kehadiran     - total hari kerja, hadir, izin, sakit, cuti, lembur
    3. Perhitungan Upah    - upah/hari, hari masuk, total upah, lembur, kasbon, upah bersih
    4. Informasi Pembayaran (bila paid) - tanggal bayar & status

    Tanpa tombol "Tutup" (tutup lewat X / klik di luar / Escape).
    Included from: pages/sdm/payroll.blade.php (inside @foreach loop)
--}}
@php
    $rupiah = fn ($value) => 'Rp ' . number_format($value ?? 0, 0, ',', '.');
    if ($payroll->period_start_date && $payroll->period_end_date) {
        $periodText = \Carbon\Carbon::parse($payroll->period_start_date)->format('d M Y') . ' - '
            . \Carbon\Carbon::parse($payroll->period_end_date)->format('d M Y');
    } else {
        $periodText = \Carbon\Carbon::create($payroll->period_year, $payroll->period_month, 1)->format('F Y')
            . ($payroll->week_number ? ' - Minggu ' . $payroll->week_number : '');
    }
@endphp

<x-fi.modal id="detailModal-{{ $payroll->id }}" heading="Detail Payroll" :description="$payroll->employee->name"
    icon="credit-card" width="2xl">
    <div class="grid gap-y-4 text-sm">

        {{-- Informasi Karyawan --}}
        <section class="rounded-lg ring-1 ring-gray-950/5">
            <h3 class="border-b border-gray-200 px-4 py-2.5 font-semibold text-gray-950">Informasi Karyawan</h3>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 p-4 sm:grid-cols-2">
                <div><dt class="text-gray-500">Nama</dt><dd class="font-medium text-gray-950">{{ $payroll->employee->name }}</dd></div>
                <div><dt class="text-gray-500">Kode Karyawan</dt><dd class="font-medium text-gray-950">{{ $payroll->employee->employee_code }}</dd></div>
                <div><dt class="text-gray-500">Jabatan</dt><dd class="font-medium text-gray-950">{{ $payroll->employee->position }}</dd></div>
                <div><dt class="text-gray-500">Periode</dt><dd class="font-medium text-gray-950">{{ $periodText }}</dd></div>
                @if ($payroll->project_name)
                    <div class="sm:col-span-2"><dt class="text-gray-500">Proyek</dt><dd class="font-medium text-gray-950">{{ $payroll->project_name }}</dd></div>
                @endif
            </dl>
        </section>

        {{-- Rekap Kehadiran --}}
        <section class="rounded-lg ring-1 ring-gray-950/5">
            <h3 class="border-b border-gray-200 px-4 py-2.5 font-semibold text-gray-950">Rekapitulasi Kehadiran</h3>
            <dl class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3">
                @foreach ([
                    'Total Hari Kerja' => $payroll->total_work_days,
                    'Hadir' => $payroll->present_days,
                    'Izin' => $payroll->permission_days,
                    'Sakit' => $payroll->sick_days,
                    'Cuti' => $payroll->leave_days,
                    'Lembur' => $payroll->overtime_days,
                ] as $label => $days)
                    <div class="rounded-lg bg-gray-50 px-3 py-2">
                        <dt class="text-gray-500">{{ $label }}</dt>
                        <dd class="font-semibold text-gray-950">{{ $days }} hari</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Perhitungan Upah --}}
        <section class="rounded-lg ring-1 ring-gray-950/5">
            <h3 class="border-b border-gray-200 px-4 py-2.5 font-semibold text-gray-950">Perhitungan Upah</h3>
            <dl class="grid gap-y-2 p-4">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Upah Per Hari</dt><dd class="font-medium tabular-nums">{{ $rupiah($payroll->base_salary) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Hari Masuk</dt><dd class="font-medium">{{ $payroll->present_days }} hari</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Total Upah</dt><dd class="font-medium tabular-nums">{{ $rupiah($payroll->base_salary * $payroll->present_days) }}</dd></div>
                <div class="flex justify-between gap-4 text-emerald-700"><dt>Lembur</dt><dd class="font-medium tabular-nums">+ {{ $rupiah($payroll->overtime_total) }}</dd></div>
                @if ($payroll->kasbon_deduction)
                    <div class="flex justify-between gap-4 text-red-600"><dt>Potongan Kasbon</dt><dd class="font-medium tabular-nums">- {{ $rupiah($payroll->kasbon_deduction) }}</dd></div>
                @endif
                <div class="mt-1 flex justify-between gap-4 border-t border-gray-200 pt-3 text-base font-semibold text-primary">
                    <dt>Upah Bersih</dt><dd class="tabular-nums">{{ $rupiah($payroll->net_salary) }}</dd>
                </div>
            </dl>
        </section>

        {{-- Informasi Pembayaran --}}
        @if ($payroll->status === 'paid')
            <section class="rounded-lg bg-emerald-50 ring-1 ring-inset ring-emerald-600/20">
                <h3 class="border-b border-emerald-600/10 px-4 py-2.5 font-semibold text-gray-950">Informasi Pembayaran</h3>
                <dl class="grid gap-y-2 p-4">
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">Tanggal Bayar</dt>
                        <dd class="font-medium">{{ \Carbon\Carbon::parse($payroll->payment_date)->format('d/m/Y') }}</dd></div>
                    <div class="flex items-center justify-between gap-4"><dt class="text-gray-600">Status</dt>
                        <dd><x-fi.badge color="success">Paid</x-fi.badge></dd></div>
                </dl>
            </section>
        @endif
    </div>
</x-fi.modal>
