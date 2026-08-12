{{-- 
    Searchable Select Component
    Usage:
    <x-forms.searchable-select 
        name="division" 
        label="Divisi" 
        :required="true"
        placeholder="Cari divisi..."
        :options="$divisions->map(fn($d) => ['value' => $d->name, 'label' => $d->name])"
        selected=""
        :extraData="['capital', 'selling']" 
    />
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
])

@php
    $selectId = $id ?? $name . '-searchable-select';
    $hasData = count($extraData) > 0;
@endphp

<div class="searchable-select-wrapper mb-3" data-select-id="{{ $selectId }}">
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
            class="searchable-select-input w-full border rounded p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light"
            placeholder="{{ $placeholder }}" autocomplete="off"
            @if ($required) required @endif
            value="{{ $selected ? (collect($options)->firstWhere('value', $selected)['label'] ?? '') : '' }}"
            oninvalid="this.setCustomValidity('{{ $invalidMessage ?: $label }} tidak boleh kosong')"
            oninput="this.setCustomValidity('')">

        <i class="fa-solid fa-chevron-down absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>

        <div class="searchable-dropdown absolute z-50 w-full bg-white border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">
            <div class="searchable-options">
                <div class="p-2 text-sm text-text-secondary hover:bg-surface-secondary cursor-pointer border-b border-border-light searchable-option"
                    data-value="">
                    -- {{ $placeholder ? 'Pilih ' . $placeholder : 'Pilih' }} --
                </div>
                @foreach ($options as $option)
                    @php
                        $value = $option['value'] ?? $option;
                        $label = $option['label'] ?? $option;
                        $searchText = strtolower($label);
                    @endphp
                    <div class="p-3 hover:bg-primary-light cursor-pointer border-b border-border-light searchable-option"
                        data-value="{{ $value }}"
                        data-search="{{ $searchText }}"
                        @if ($hasData)
                            @foreach ($extraData as $key)
                                {{ 'data-' . $key . '="' . ($option[$key] ?? '') . '"' }}
                            @endforeach
                        @endif
                        data-label="{{ $label }}">
                        <div class="font-medium text-text-heading">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
            <div class="searchable-no-results p-4 text-center text-sm text-text-secondary hidden">
                <i class="fa-solid fa-search mb-2 text-2xl text-text-placeholder"></i>
                <p>Tidak ada data ditemukan</p>
            </div>
        </div>
    </div>

    <input type="hidden" name="{{ $name }}" id="{{ $selectId }}" class="searchable-select-hidden"
        value="{{ $selected }}">
</div>
