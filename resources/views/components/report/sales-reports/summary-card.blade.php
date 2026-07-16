{{--
    Komponen Kartu Ringkasan (Summary Card).

    Digunakan untuk menampilkan statistik ringkasan
    pada dashboard laporan penjualan.

    @param string $title      Judul kartu (contoh: "Total Penjualan")
    @param string $value      Nilai yang ditampilkan (contoh: "Rp 1.000.000")
    @param string $subtitle   Subtitle/tambahan info (contoh: "10 transaksi")
    @param string $icon       Class icon FontAwesome (contoh: "fa-chart-line")
    @param string $color      Warna tema: 'primary', 'warning', 'success', atau 'default'
--}}
@props([
    'title' => '',
    'value' => '',
    'subtitle' => '',
    'icon' => 'fa-chart-line',
    'color' => 'primary',
])

@php
    $iconBgClass = match ($color) {
        'primary' => 'bg-primary-light',
        'warning' => 'bg-warning-light',
        'success' => 'bg-success-light',
        default => 'bg-surface-secondary',
    };

    $iconTextClass = match ($color) {
        'primary' => 'text-primary',
        'warning' => 'text-warning',
        'success' => 'text-success',
        default => 'text-text-primary',
    };

    $valueClass = $color === 'success' ? 'text-success' : 'text-text-primary';
@endphp

<div class="bg-surface-base p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-text-secondary">{{ $title }}</p>
            <h3 class="text-2xl font-bold {{ $valueClass }} mt-2">
                {{ $value }}
            </h3>
            @if ($subtitle)
                <p class="text-xs text-text-secondary mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="p-4 {{ $iconBgClass }} rounded-full">
            <i class="fas {{ $icon }} {{ $iconTextClass }} text-2xl"></i>
        </div>
    </div>
</div>
