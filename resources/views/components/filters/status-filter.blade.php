@props(['name' => 'status', 'value' => null, 'responsive' => 'xl', 'fill' => false])

@if($responsive === 'custom')
<div class="{{ $fill ? 'flex-1' : 'w-full min-[1530px]:w-auto' }}">
    <label for="{{ $name }}-select" class="sr-only">Filter Status</label>
    <select name="{{ $name }}" id="{{ $name }}-select"
        onchange="this.form.requestSubmit()"
        class="block w-full {{ $fill ? '' : 'min-[1530px]:w-36' }} rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input 
               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light"
        {{ $attributes }}>
@else
<div class="{{ $fill ? 'flex-1' : 'w-full xl:w-auto' }}">
    <label for="{{ $name }}-select" class="sr-only">Filter Status</label>
    <select name="{{ $name }}" id="{{ $name }}-select"
        onchange="this.form.requestSubmit()"
        class="block w-full {{ $fill ? '' : 'xl:w-36' }} rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input 
               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light"
        {{ $attributes }}>
@endif
        <option value="">Semua Status</option>
        <option value="lunas" {{ $value === 'lunas' ? 'selected' : '' }}>Sudah Lunas</option>
        <option value="belum" {{ $value === 'belum' ? 'selected' : '' }}>Belum Lunas</option>
    </select>
</div>
