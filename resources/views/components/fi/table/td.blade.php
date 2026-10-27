{{--
    Sel isi tabel bergaya Filament (warna teks diwarisi dari <tbody>).
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

<td {{ $attributes->class("px-3 py-4 text-sm sm:first-of-type:ps-6 sm:last-of-type:pe-6 {$alignClass}") }}>
    {{ $slot }}
</td>
