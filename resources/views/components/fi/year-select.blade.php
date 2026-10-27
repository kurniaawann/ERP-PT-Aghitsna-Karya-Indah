{{--
    Filter tahun bergaya Filament (name="year", id="{name}-select"), dari
    tahun berjalan mundur sampai startYear. Atribut lain (mis. onchange)
    diteruskan ke <select>. Tampilan daftar opsi dibuat
    resources/js/shared/fi-select.js (data-fi-select).
--}}
@props([
    'name' => 'year',
    'value' => null,
    'startYear' => 2020,
])

@php
    $years = [];
    for ($year = (int) date('Y'); $year >= $startYear; $year--) {
        $years[$year] = $year;
    }
@endphp

<x-fi.select :name="$name" :id="$name . '-select'" aria-label="Pilih Tahun" :options="$years" :value="$value"
    placeholder="Semua Tahun" data-fi-select {{ $attributes }} />
