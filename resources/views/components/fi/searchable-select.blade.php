{{--
    Select dengan pencarian bergaya Filament (satu pilihan).

    Markup memakai hook yang sama dengan x-forms.searchable-select sehingga
    tetap ditangani resources/js/shared/searchable-select.js dan JS halaman
    yang membaca hook ini: .searchable-select-wrapper (data-select-id),
    .searchable-select-input, .searchable-dropdown, .searchable-options >
    .searchable-option (data-value, data-search, data-label, data-{extra}),
    .searchable-no-results, input.searchable-select-hidden (name, id).

    Props:
    - name, id, label, required, placeholder, invalidMessage, hint
    - options   : [['value' => ..., 'label' => ..., ...extra], ...]
    - selected  : nilai terpilih awal
    - extraData : kunci tambahan opsi yang ditulis sebagai data-{kunci}
--}}
@props([
    'name' => '',
    'id' => null,
    'label' => '',
    'required' => false,
    'placeholder' => 'Cari...',
    'options' => [],
    'selected' => '',
    'extraData' => [],
    'invalidMessage' => '',
    'hint' => null,
])

@php
    $selectId = $id ?? $name . '-searchable-select';
    $selectedLabel = $selected ? (collect($options)->firstWhere('value', $selected)['label'] ?? '') : '';
@endphp

<div {{ $attributes->class('searchable-select-wrapper grid content-start gap-y-2') }} data-select-id="{{ $selectId }}">
    @if ($label)
        <label for="{{ $selectId }}-input" class="text-sm font-medium leading-6 text-gray-950">
            {{ $label }}@if ($required)<sup class="ms-0.5 font-medium text-red-600">*</sup>@endif
        </label>
    @endif

    <div class="relative">
        <div class="flex w-full rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 focus-within:ring-2 focus-within:ring-primary">
            <div class="pointer-events-none flex shrink-0 items-center ps-3">
                <x-fi.icon name="magnifying-glass" class="h-5 w-5 text-gray-400" />
            </div>
            <input type="text" id="{{ $selectId }}-input" placeholder="{{ $placeholder }}" autocomplete="off"
                value="{{ $selectedLabel }}" @if ($required) required @endif
                oninvalid="this.setCustomValidity('{{ $invalidMessage ?: $label }} tidak boleh kosong')"
                oninput="this.setCustomValidity('')"
                class="searchable-select-input block w-full min-w-0 border-none bg-transparent py-1.5 pe-9 ps-2 text-base text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6">
            <x-fi.icon name="chevron-down" class="pointer-events-none absolute end-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
        </div>

        <div class="searchable-dropdown absolute z-20 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-lg bg-white p-1 shadow-lg ring-1 ring-gray-950/5">
            <div class="searchable-options">
                <div class="searchable-option cursor-pointer rounded-md px-2 py-1.5 text-sm text-gray-500 hover:bg-gray-50" data-value="">
                    -- {{ $placeholder ? 'Pilih ' . $placeholder : 'Pilih' }} --
                </div>
                @foreach ($options as $option)
                    @php
                        $value = $option['value'] ?? $option;
                        $optionLabel = $option['label'] ?? $option;
                    @endphp
                    <div class="searchable-option cursor-pointer rounded-md px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
                        data-value="{{ $value }}" data-search="{{ strtolower($optionLabel) }}"
                        @foreach ((array) $extraData as $key) data-{{ $key }}="{{ $option[$key] ?? '' }}" @endforeach
                        data-label="{{ $optionLabel }}">
                        {{ $optionLabel }}
                    </div>
                @endforeach
            </div>
            <div class="searchable-no-results hidden px-2 py-4 text-center text-sm text-gray-500">
                Tidak ada data ditemukan
            </div>
        </div>
    </div>

    <input type="hidden" name="{{ $name }}" id="{{ $selectId }}" class="searchable-select-hidden" value="{{ $selected }}">

    @if ($hint)
        <p class="text-sm text-gray-500">{{ $hint }}</p>
    @endif
</div>
