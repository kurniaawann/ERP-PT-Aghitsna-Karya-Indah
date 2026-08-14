@props(['name' => 'tipe', 'value' => null, 'responsive' => 'xl', 'fill' => false])

@if($responsive === 'custom')
<div class="{{ $fill ? 'flex-1' : 'w-full min-[1530px]:w-auto' }}">
    <label for="{{ $name }}-select" class="sr-only">Filter Tipe Nota</label>
    <select name="{{ $name }}" id="{{ $name }}-select"
        onchange="this.form.requestSubmit()"
        class="block w-full {{ $fill ? '' : 'min-[1530px]:w-40' }} rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input 
               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
@else
<div class="{{ $fill ? 'flex-1' : 'w-full lg:w-auto' }}">
    <label for="{{ $name }}-select" class="sr-only">Filter Tipe Nota</label>
    <select name="{{ $name }}" id="{{ $name }}-select"
        onchange="this.form.requestSubmit()"
        class="block w-full {{ $fill ? '' : 'lg:w-40' }} rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input 
               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
@endif
        <option value="">Semua Tipe</option>
        <option value="sewa_jual" {{ $value === 'sewa_jual' ? 'selected' : '' }}>Sewa/Jual</option>
        <option value="proyek" {{ $value === 'proyek' ? 'selected' : '' }}>Proyek</option>
    </select>
</div>