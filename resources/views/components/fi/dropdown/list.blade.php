{{--
    Kelompok item di dalam x-fi.dropdown (antar kelompok diberi garis).
    Props: label = judul kecil kelompok (opsional).
--}}
@props(['label' => null])

<div {{ $attributes->class('p-1') }}>
    @if ($label)
        <div class="px-2 pb-1 pt-1.5 text-sm font-medium text-gray-500">{{ $label }}</div>
    @endif

    {{ $slot }}
</div>
