{{--
    Fieldset bergaya Filament: kotak bergaris dengan judul (legend) untuk
    mengelompokkan field di dalam form/modal.

    Props:
    - label : judul kelompok (opsional)
--}}
@props(['label' => null])

<fieldset {{ $attributes->class('rounded-xl border border-gray-200 p-4 sm:p-6') }}>
    @if ($label)
        <legend class="-ms-2 px-2 text-sm font-medium leading-6 text-gray-950">{{ $label }}</legend>
    @endif

    {{ $slot }}
</fieldset>
