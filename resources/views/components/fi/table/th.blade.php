{{--
    Sel header tabel bergaya Filament.
    Props: align = start | center | end (default start)
--}}
@props(['align' => 'start'])

@php
    $alignClass = match ($align) {
        'center' => 'text-center',
        'end' => 'text-end',
        default => 'text-start',
    };
@endphp

<th {{ $attributes->class("whitespace-nowrap px-3 py-3.5 text-sm font-semibold text-gray-950 sm:first-of-type:ps-6 sm:last-of-type:pe-6 {$alignClass}") }}>
    {{ $slot }}
</th>
