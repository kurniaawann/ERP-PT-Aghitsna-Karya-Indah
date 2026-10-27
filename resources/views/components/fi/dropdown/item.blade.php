{{--
    Item menu di dalam x-fi.dropdown.

    Props:
    - color    : gray | primary | success | danger | warning (default gray).
                 Pakai gray untuk aksi biasa; danger khusus aksi destruktif.
    - icon     : nama ikon x-fi.icon (opsional)
    - href     : bila diisi dirender sebagai <a>, selain itu <button type="button">
    - disabled : nonaktifkan item (hanya untuk <button>)
    - hint     : teks kecil di bawah label, mis. alasan item dinonaktifkan
    Atribut lain (onclick, title, ...) diteruskan ke elemen. Menu otomatis
    tertutup setelah item diklik (fi-dropdown.js).
--}}
@props([
    'color' => 'gray',
    'icon' => null,
    'href' => null,
    'disabled' => false,
    'hint' => null,
])

@php
    [$labelColor, $iconColor] = match ($color) {
        'primary' => ['text-primary', 'text-primary'],
        'success' => ['text-success', 'text-success'],
        'danger' => ['text-red-600', 'text-red-500'],
        'warning' => ['text-amber-700', 'text-amber-500'],
        default => ['text-gray-700', 'text-gray-400'],
    };

    $classes = "flex w-full items-center gap-2 whitespace-nowrap rounded-md p-2 text-start text-sm font-medium outline-none transition-colors duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-transparent {$labelColor}";
@endphp

@if ($href)
    <a href="{{ $href }}" role="menuitem" {{ $attributes->class($classes) }}>
        @if ($icon)
            <x-fi.icon :name="$icon" class="h-5 w-5 {{ $iconColor }}" />
        @endif
        <span class="truncate">{{ $slot }}</span>
    </a>
@else
    <button role="menuitem" @disabled($disabled) {{ $attributes->merge(['type' => 'button'])->class($classes) }}>
        @if ($icon)
            <x-fi.icon :name="$icon" class="h-5 w-5 {{ $iconColor }}" />
        @endif
        <span class="grid min-w-0">
            <span class="truncate">{{ $slot }}</span>
            @if ($hint)
                <span class="truncate font-normal text-gray-500">{{ $hint }}</span>
            @endif
        </span>
    </button>
@endif
