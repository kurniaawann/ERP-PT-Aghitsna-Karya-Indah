{{--
    Pembungkus field form bergaya Filament: label, input (slot), helper text.

    Props:
    - label    : teks label
    - for      : id input yang dirujuk label (opsional)
    - required : tampilkan tanda bintang merah
    - hint     : helper text abu-abu di bawah input (opsional)
--}}
@props([
    'label' => null,
    'for' => null,
    'required' => false,
    'hint' => null,
])

<div {{ $attributes->class('grid content-start gap-y-2') }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="inline-flex items-center gap-x-3 text-sm font-medium leading-6 text-gray-950">
            <span>{{ $label }}@if ($required)<sup class="ms-0.5 font-medium text-red-600">*</sup>@endif</span>
        </label>
    @endif

    {{ $slot }}

    @if ($hint)
        <p class="text-sm text-gray-500">{{ $hint }}</p>
    @endif
</div>
