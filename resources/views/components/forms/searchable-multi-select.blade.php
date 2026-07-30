{{--
    Searchable Multi-Select Component
    Usage:
    <x-forms.searchable-multi-select
        name="employee_ids"
        label="Pilih Karyawan"
        :required="true"
        placeholder="Cari karyawan..."
        :options="$employees->map(fn($e) => ['value' => $e->employee_code, 'label' => $e->name . ' - ' . $e->employee_code])->values()"
    />
--}}
@props([
    'name' => '',
    'id' => null,
    'label' => '',
    'required' => false,
    'placeholder' => 'Cari...',
    'options' => [],
])

@php
    $selectId = $id ?? $name . '-searchable-multi-select';
@endphp

<div class="searchable-multi-select-wrapper mb-3" data-select-id="{{ $selectId }}">
    @if ($label)
        <label class="block text-text-primary mb-1" for="{{ $selectId }}-input">
            {{ $label }}
            @if ($required)
                <span class="text-error">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <input type="text" id="{{ $selectId }}-input"
            class="searchable-multi-select-input w-full border rounded p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light"
            placeholder="{{ $placeholder }}" autocomplete="off"
            oninput="this.setCustomValidity('')">

        <i class="fa-solid fa-chevron-down absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>

        <div class="searchable-multi-dropdown absolute z-50 w-full bg-white border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">
            <div class="p-2 hover:bg-surface-secondary cursor-pointer border-b border-border-light searchable-multi-option"
                data-value="__select_all__">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" class="searchable-multi-select-all w-4 h-4 accent-primary">
                    <span class="font-semibold text-sm">Pilih Semua</span>
                </label>
            </div>

            <div class="searchable-multi-options">
                @foreach ($options as $option)
                    @php
                        $value = $option['value'] ?? $option;
                        $label = $option['label'] ?? $option;
                        $searchText = strtolower($label);
                    @endphp
                    <div class="p-3 hover:bg-primary-light cursor-pointer border-b border-border-light searchable-multi-option"
                        data-value="{{ $value }}"
                        data-search="{{ $searchText }}"
                        data-label="{{ $label }}">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" value="{{ $value }}"
                                class="searchable-multi-checkbox w-4 h-4 accent-primary">
                            <span class="font-medium text-sm text-text-heading">{{ $label }}</span>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="searchable-multi-no-results p-4 text-center text-sm text-text-secondary hidden">
                <i class="fa-solid fa-search mb-2 text-2xl text-text-placeholder"></i>
                <p>Tidak ada data ditemukan</p>
            </div>
        </div>
    </div>

    <div class="searchable-multi-tags flex flex-wrap gap-1 mt-2"></div>

    <div class="searchable-multi-hidden-inputs" data-name="{{ $name }}"></div>

    @if ($required)
        <p class="text-xs text-red-600 mt-1 hidden searchable-multi-error">Silakan pilih minimal 1 opsi!</p>
    @endif
</div>
