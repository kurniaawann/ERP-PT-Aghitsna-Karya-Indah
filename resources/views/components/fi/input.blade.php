{{--
    Input teks bergaya Filament (wrapper ber-ring + affix opsional).

    Props:
    - prefixIcon : nama ikon x-fi.icon di sisi kiri (opsional)
    - prefix     : teks affix kiri, mis. "Rp" (opsional)
    - suffix     : teks affix kanan (opsional)
    Atribut lain (name, id, value, required, readonly, oninvalid, ...)
    diteruskan ke elemen <input>; type default "text".
--}}
@props([
    'prefixIcon' => null,
    'prefix' => null,
    'suffix' => null,
])

@php
    $locked = $attributes->has('readonly') || $attributes->has('disabled');
@endphp

<div @class([
    'flex w-full overflow-hidden rounded-lg shadow-sm ring-1 ring-gray-950/10 transition duration-75',
    'bg-gray-50' => $locked,
    'bg-white focus-within:ring-2 focus-within:ring-primary' => ! $locked,
])>
    @if ($prefixIcon || $prefix)
        <div @class([
            'flex shrink-0 items-center gap-x-2 ps-3',
            'border-e border-gray-200 pe-2' => $prefix,
        ])>
            @if ($prefixIcon)
                <x-fi.icon :name="$prefixIcon" class="h-5 w-5 text-gray-400" />
            @endif
            @if ($prefix)
                <span class="whitespace-nowrap text-sm text-gray-500">{{ $prefix }}</span>
            @endif
        </div>
    @endif

    <input {{ $attributes->merge(['type' => 'text'])->class([
        'block w-full min-w-0 border-none bg-transparent py-1.5 pe-3 text-base outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6',
        'ps-3' => ! $prefixIcon || $prefix,
        'ps-2' => $prefixIcon && ! $prefix,
        'text-gray-950' => ! $locked,
        'cursor-not-allowed text-gray-500' => $locked,
    ]) }}>

    @if ($suffix)
        <div class="flex shrink-0 items-center border-s border-gray-200 pe-3 ps-2">
            <span class="whitespace-nowrap text-sm text-gray-500">{{ $suffix }}</span>
        </div>
    @endif
</div>
