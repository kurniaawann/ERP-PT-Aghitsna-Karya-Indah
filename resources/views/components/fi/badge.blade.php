{{--
    Badge bergaya Filament.

    Props:
    - color : gray | primary | success | warning | danger (default gray)
    - icon  : nama ikon x-fi.icon (opsional)
    - href  : bila diisi dirender sebagai <a> (badge yang bisa diklik)
--}}
@props([
    'color' => 'gray',
    'icon' => null,
    'href' => null,
])

@php
    $colorClasses = match ($color) {
        'primary' => 'bg-blue-50 text-blue-700 ring-blue-700/10',
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'danger' => 'bg-red-50 text-red-700 ring-red-600/10',
        default => 'bg-gray-50 text-gray-600 ring-gray-600/10',
    };

    $classes = "inline-flex min-w-[1.5rem] items-center justify-center gap-x-1 whitespace-nowrap rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {$colorClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class("{$classes} transition duration-75 hover:brightness-95") }}>
        @if ($icon)
            <x-fi.icon :name="$icon" class="h-4 w-4" />
        @endif
        <span>{{ $slot }}</span>
    </a>
@else
    <span {{ $attributes->class($classes) }}>
        @if ($icon)
            <x-fi.icon :name="$icon" class="h-4 w-4" />
        @endif
        <span>{{ $slot }}</span>
    </span>
@endif
