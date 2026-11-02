{{-- Legend status absensi slip gaji (H/I/S/C/A/L); warna sama dengan STATUS_CLASSES
     di resources/js/pages/sdm/salary-slip/index.js. Dipakai modal Generate, Edit, Detail. --}}
<div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
    @foreach ([
        'H' => ['Hadir', 'bg-success-light text-success'],
        'I' => ['Izin', 'bg-warning-light text-warning'],
        'S' => ['Sakit', 'bg-error-light text-error'],
        'C' => ['Cuti', 'bg-purple-100 text-purple-700'],
        'A' => ['Alpha', 'bg-surface-hover text-text-label'],
        'L' => ['Libur', 'bg-primary-light text-primary'],
    ] as $letter => [$label, $classes])
        <span class="inline-flex items-center gap-1">
            <span class="inline-flex h-5 w-5 items-center justify-center rounded font-semibold {{ $classes }}">{{ $letter }}</span>
            {{ $label }}
        </span>
    @endforeach
</div>
