{{--
    Aksi berbentuk link bergaya Filament (dipakai untuk aksi baris tabel).

    Props:
    - color : primary | gray | danger (default primary)
    - icon  : nama ikon x-fi.icon (opsional)
    - href  : bila diisi dirender sebagai <a>, selain itu <button type="button">
--}}
@props([
    'color' => 'primary',
    'icon' => null,
    'href' => null,
])

@php
    $colorClasses = match ($color) {
        'danger' => 'text-red-600 hover:text-red-500',
        'gray' => 'text-gray-700 hover:text-gray-500',
        default => 'text-primary hover:text-primary-hover',
    };

    $classes = "group/link relative inline-flex items-center justify-center gap-1 whitespace-nowrap text-sm font-semibold outline-none transition duration-75 {$colorClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <x-fi.icon :name="$icon" class="h-4 w-4" />
        @endif
        <span class="group-hover/link:underline group-focus-visible/link:underline">{{ $slot }}</span>
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>
        @if ($icon)
            <x-fi.icon :name="$icon" class="h-4 w-4" />
        @endif
        <span class="group-hover/link:underline group-focus-visible/link:underline">{{ $slot }}</span>
    </button>
@endif
