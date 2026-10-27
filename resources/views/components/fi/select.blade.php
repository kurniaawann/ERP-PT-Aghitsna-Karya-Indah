{{--
    Select bergaya Filament (native <select> dengan ikon chevron).

    Props:
    - options     : [value => label] (opsional; bisa juga <option> lewat slot)
    - value       : nilai terpilih untuk `options`
    - placeholder : opsi kosong di paling atas (value "") (opsional)
    - prefixIcon  : nama ikon x-fi.icon di sisi kiri (opsional)
    Atribut lain (name, id, required, onchange, ...) diteruskan ke <select>.
    Tambah atribut data-fi-select → daftar opsi tampil sebagai dropdown
    bergaya Filament (resources/js/shared/fi-select.js), bukan popup native.
--}}
@props([
    'options' => null,
    'value' => null,
    'placeholder' => null,
    'prefixIcon' => null,
])

<div class="relative flex w-full rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 focus-within:ring-2 focus-within:ring-primary">
    @if ($prefixIcon)
        <div class="pointer-events-none flex shrink-0 items-center ps-3">
            <x-fi.icon :name="$prefixIcon" class="h-5 w-5 text-gray-400" />
        </div>
    @endif

    <select {{ $attributes->class([
        'block w-full min-w-0 cursor-pointer appearance-none border-none bg-transparent py-1.5 pe-9 text-base text-gray-950 outline-none sm:text-sm sm:leading-6',
        'ps-3' => ! $prefixIcon,
        'ps-2' => $prefixIcon,
    ]) }}>
        @if (! is_null($placeholder))
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options ?? [] as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach

        {{ $slot }}
    </select>

    <x-fi.icon name="chevron-down" class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
</div>
