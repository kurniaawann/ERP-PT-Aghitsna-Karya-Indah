{{--
    Input pencarian tabel bergaya Filament (name="search").
    Atribut data-search-debounce membuat Enter langsung submit form induknya
    (lihat resources/js/app.js).
--}}
@props([
    'value' => '',
    'placeholder' => 'Cari',
])

<div {{ $attributes->class('w-full') }}>
    <x-fi.input name="search" :value="$value" :placeholder="$placeholder" :aria-label="$placeholder"
        prefix-icon="magnifying-glass" autocomplete="off" data-search-debounce />
</div>
