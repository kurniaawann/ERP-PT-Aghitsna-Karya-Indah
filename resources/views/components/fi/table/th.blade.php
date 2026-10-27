{{--
    Sel header tabel bergaya Filament.
    Props:
    - align : start | center | end (default start)
    - dense : padding rapat untuk tabel dengan banyak kolom
--}}
@props(['align' => 'start', 'dense' => false])

@php
    $alignClass = match ($align) {
        'center' => 'text-center',
        'end' => 'text-end',
        default => 'text-start',
    };

    $paddingClass = $dense
        ? 'whitespace-nowrap px-2 py-3 text-sm font-semibold text-gray-950 sm:first-of-type:ps-4 sm:last-of-type:pe-4'
        : 'whitespace-nowrap px-3 py-3.5 text-sm font-semibold text-gray-950 sm:first-of-type:ps-6 sm:last-of-type:pe-6';
@endphp

<th {{ $attributes->class("{$paddingClass} {$alignClass}") }}>
    {{ $slot }}
</th>
