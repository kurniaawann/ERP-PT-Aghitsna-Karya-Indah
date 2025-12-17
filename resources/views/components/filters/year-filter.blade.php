@props(['name' => 'year', 'value' => null, 'startYear' => 2020])

<div class="w-full lg:w-auto">
    <label for="{{ $name }}-select" class="sr-only">Pilih Tahun</label>
    <select name="{{ $name }}" id="{{ $name }}-select"
        class="block w-full lg:w-32 rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-gray-900 
               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
        <option value="">Semua Tahun</option>
        @for ($y = date('Y'); $y >= $startYear; $y--)
            <option value="{{ $y }}" {{ $value == $y ? 'selected' : '' }}>
                {{ $y }}
            </option>
        @endfor
    </select>
</div>
