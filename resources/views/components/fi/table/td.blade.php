{{--
    Sel isi tabel bergaya Filament (warna teks diwarisi dari <tbody>).
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
        ? 'px-2 py-3 text-sm sm:first-of-type:ps-4 sm:last-of-type:pe-4'
        : 'px-3 py-4 text-sm sm:first-of-type:ps-6 sm:last-of-type:pe-6';
@endphp

<td {{ $attributes->class("{$paddingClass} {$alignClass}") }}>
    {{ $slot }}
</td>
