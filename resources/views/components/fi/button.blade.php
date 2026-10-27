{{--
    Tombol bergaya Filament.

    Props:
    - color    : primary | gray | danger | success (default primary)
    - size     : sm | md | lg (default md)
    - icon     : nama ikon x-fi.icon (opsional)
    - outlined : true untuk varian garis (tanpa latar)
    - href     : bila diisi dirender sebagai <a>
    Atribut lain (id, onclick, disabled, type, ...) diteruskan ke elemen;
    type default "button" agar tidak ikut submit form.
--}}
@props([
    'color' => 'primary',
    'size' => 'md',
    'icon' => null,
    'outlined' => false,
    'href' => null,
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'gap-1 px-2.5 py-1.5 text-sm',
        'lg' => 'gap-1.5 px-3.5 py-2.5 text-sm',
        default => 'gap-1.5 px-3 py-2 text-sm',
    };

    $iconSize = $size === 'sm' ? 'h-4 w-4' : 'h-5 w-5';

    $colorClasses = $outlined
        ? match ($color) {
            'danger' => 'text-red-600 ring-1 ring-red-600 hover:bg-red-50 focus-visible:ring-red-500/50 disabled:hover:bg-transparent',
            'success' => 'text-success ring-1 ring-success hover:bg-success-light/50 focus-visible:ring-success/50 disabled:hover:bg-transparent',
            'gray' => 'text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 focus-visible:ring-primary/50 disabled:hover:bg-transparent',
            default => 'text-primary ring-1 ring-primary hover:bg-primary-light/50 focus-visible:ring-primary/50 disabled:hover:bg-transparent',
        }
        : match ($color) {
            'danger' => 'bg-red-600 text-white shadow-sm hover:bg-red-500 focus-visible:ring-red-500/50 disabled:hover:bg-red-600',
            'success' => 'bg-success text-white shadow-sm hover:bg-emerald-500 focus-visible:ring-success/50 disabled:hover:bg-success',
            'gray' => 'bg-white text-gray-950 shadow-sm ring-1 ring-gray-950/10 hover:bg-gray-50 focus-visible:ring-primary/50 disabled:hover:bg-white',
            default => 'bg-primary text-white shadow-sm hover:bg-primary-hover focus-visible:ring-primary/50 disabled:hover:bg-primary',
        };

    $classes = "relative inline-flex items-center justify-center whitespace-nowrap rounded-lg font-semibold outline-none transition duration-75 focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-50 {$sizeClasses} {$colorClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <x-fi.icon :name="$icon" class="{{ $iconSize }}" />
        @endif
        <span>{{ $slot }}</span>
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>
        @if ($icon)
            <x-fi.icon :name="$icon" class="{{ $iconSize }}" />
        @endif
        <span>{{ $slot }}</span>
    </button>
@endif
