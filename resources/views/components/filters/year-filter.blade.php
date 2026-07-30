@props(['name' => 'year', 'value' => null, 'startYear' => 2020, 'responsive' => 'xl', 'fill' => false])

@if($responsive === 'custom')
<div class="{{ $fill ? 'flex-1' : 'w-full min-[1530px]:w-auto' }}">
    <label for="{{ $name }}-select" class="sr-only">Pilih Tahun</label>
    <select name="{{ $name }}" id="{{ $name }}-select"
        onchange="this.form.requestSubmit()"
        class="block w-full {{ $fill ? '' : 'min-[1530px]:w-32' }} rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input 
               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light"
        {{ $attributes }}>
@else
<div class="{{ $fill ? 'flex-1' : 'w-full xl:w-auto' }}">
    <label for="{{ $name }}-select" class="sr-only">Pilih Tahun</label>
    <select name="{{ $name }}" id="{{ $name }}-select"
        onchange="this.form.requestSubmit()"
        class="block w-full {{ $fill ? '' : 'xl:w-32' }} rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input 
               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light"
        {{ $attributes }}>
@endif
        <option value="">Semua Tahun</option>
        @for ($y = date('Y'); $y >= $startYear; $y--)
            <option value="{{ $y }}" {{ $value == $y ? 'selected' : '' }}>
                {{ $y }}
            </option>
        @endfor
    </select>
</div>
