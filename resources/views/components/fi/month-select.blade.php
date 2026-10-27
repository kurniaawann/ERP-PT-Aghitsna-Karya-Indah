{{--
    Filter bulan bergaya Filament (name="month", id="{name}-select").
    Atribut lain (mis. onchange) diteruskan ke <select>.
--}}
@props([
    'name' => 'month',
    'value' => null,
])

@php
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
@endphp

<x-fi.select :name="$name" :id="$name . '-select'" aria-label="Pilih Bulan" :options="$months" :value="$value"
    placeholder="Semua Bulan" prefix-icon="calendar-days" {{ $attributes }} />
