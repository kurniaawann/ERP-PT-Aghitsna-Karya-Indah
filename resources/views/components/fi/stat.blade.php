{{--
    Kartu statistik bergaya Filament (stats overview): label + ikon kecil,
    nilai besar (slot), deskripsi opsional di bawahnya.

    Props:
    - label       : judul kartu
    - icon        : nama ikon x-fi.icon di samping label (opsional)
    - color       : warna nilai & ikon: gray | primary | success | danger | warning
    - description : teks kecil di bawah nilai (opsional)
    - size        : md (default, text-2xl) | sm (text-xl, untuk baris berisi
                    banyak kartu nominal Rupiah agar angka tidak terpotong)
--}}
@props([
    'label' => '',
    'icon' => null,
    'color' => 'gray',
    'description' => null,
    'size' => 'md',
])

@php
    [$valueColor, $iconColor] = match ($color) {
        'primary' => ['text-primary', 'text-primary'],
        'success' => ['text-emerald-600', 'text-emerald-500'],
        'danger' => ['text-red-600', 'text-red-500'],
        'warning' => ['text-amber-600', 'text-amber-500'],
        default => ['text-gray-950', 'text-gray-400'],
    };
@endphp

<div {{ $attributes->class('grid content-start gap-y-2 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5') }}>
    <div class="flex items-center gap-x-2 text-sm font-medium text-gray-500">
        @if ($icon)
            <x-fi.icon :name="$icon" class="h-5 w-5 shrink-0 {{ $iconColor }}" />
        @endif
        <span>{{ $label }}</span>
    </div>

    <p @class([
        'font-semibold tracking-tight tabular-nums [overflow-wrap:anywhere]',
        $valueColor,
        'text-2xl' => $size !== 'sm',
        'text-xl' => $size === 'sm',
    ])>{{ $slot }}</p>

    @if ($description)
        <p class="text-sm text-gray-500">{{ $description }}</p>
    @endif
</div>
