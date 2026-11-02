{{--
    Multi-select dengan pencarian bergaya Filament.

    Markup memakai hook yang sama dengan x-forms.searchable-multi-select
    sehingga tetap ditangani resources/js/shared/searchable-multi-select.js
    (dan JS halaman yang membaca hook ini): .searchable-multi-select-wrapper
    (data-select-id, data-selected), .searchable-multi-select-input,
    .searchable-multi-dropdown, .searchable-multi-select-all,
    .searchable-multi-options > .searchable-multi-option (data-value,
    data-search, data-label) > .searchable-multi-checkbox,
    .searchable-multi-no-results, .searchable-multi-tags,
    .searchable-multi-hidden-inputs (data-name), .searchable-multi-error.

    Props:
    - name, id, label, required, placeholder
    - options  : [['value' => ..., 'label' => ...], ...]
    - selected : nilai terpilih awal (mis. old input)
    - hint     : teks bantu di bawah field (opsional)
--}}
@props([
    'name' => '',
    'id' => null,
    'label' => '',
    'required' => false,
    'placeholder' => 'Cari...',
    'options' => [],
    'selected' => [],
    'hint' => null,
])

@php
    $selectId = $id ?? $name . '-searchable-multi-select';
@endphp

<div {{ $attributes->class('searchable-multi-select-wrapper grid content-start gap-y-2') }} data-select-id="{{ $selectId }}"
    data-selected="{{ json_encode(array_values((array) $selected)) }}">
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
                oninput="this.setCustomValidity('')"
                class="searchable-multi-select-input block w-full min-w-0 border-none bg-transparent py-1.5 pe-9 ps-2 text-base text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6">
            <x-fi.icon name="chevron-down" class="pointer-events-none absolute end-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
        </div>

        <div class="searchable-multi-dropdown absolute z-20 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-lg bg-white p-1 shadow-lg ring-1 ring-gray-950/5">
            <div class="searchable-multi-option cursor-pointer rounded-md border-b border-gray-100 px-2 py-2 hover:bg-gray-50"
                data-value="__select_all__">
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" class="searchable-multi-select-all fi-checkbox">
                    <span class="text-sm font-semibold text-gray-950">Pilih Semua</span>
                </label>
            </div>

            <div class="searchable-multi-options">
                @foreach ($options as $option)
                    @php
                        $value = $option['value'] ?? $option;
                        $optionLabel = $option['label'] ?? $option;
                    @endphp
                    <div class="searchable-multi-option cursor-pointer rounded-md px-2 py-2 hover:bg-gray-50"
                        data-value="{{ $value }}" data-search="{{ strtolower($optionLabel) }}" data-label="{{ $optionLabel }}">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" value="{{ $value }}" class="searchable-multi-checkbox fi-checkbox">
                            <span class="text-sm text-gray-700">{{ $optionLabel }}</span>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="searchable-multi-no-results hidden px-2 py-4 text-center text-sm text-gray-500">
                Tidak ada data ditemukan
            </div>
        </div>
    </div>

    <div class="searchable-multi-tags flex flex-wrap gap-1.5 empty:hidden"></div>

    <div class="searchable-multi-hidden-inputs hidden" data-name="{{ $name }}"></div>

    @if ($hint)
        <p class="text-sm text-gray-500">{{ $hint }}</p>
    @endif

    @if ($required)
        <p class="searchable-multi-error hidden text-sm text-red-600">Silakan pilih minimal 1 opsi!</p>
    @endif
</div>
