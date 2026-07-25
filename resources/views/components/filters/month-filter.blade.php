@props(['name' => 'month', 'value' => null, 'responsive' => 'xl'])

@if($responsive === 'custom')
<div class="w-full min-[1530px]:w-auto">
    <label for="{{ $name }}-select" class="sr-only">Pilih Bulan</label>
    <select name="{{ $name }}" id="{{ $name }}-select"
        onchange="this.form.requestSubmit()"
        class="block w-full min-[1530px]:w-40 rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input 
               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light"
        {{ $attributes }}>
@else
<div class="w-full xl:w-auto">
    <label for="{{ $name }}-select" class="sr-only">Pilih Bulan</label>
    <select name="{{ $name }}" id="{{ $name }}-select"
        onchange="this.form.requestSubmit()"
        class="block w-full xl:w-40 rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input 
               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light"
        {{ $attributes }}>
@endif
        <option value="">Semua Bulan</option>
        <option value="1" {{ $value == '1' ? 'selected' : '' }}>Januari</option>
        <option value="2" {{ $value == '2' ? 'selected' : '' }}>Februari</option>
        <option value="3" {{ $value == '3' ? 'selected' : '' }}>Maret</option>
        <option value="4" {{ $value == '4' ? 'selected' : '' }}>April</option>
        <option value="5" {{ $value == '5' ? 'selected' : '' }}>Mei</option>
        <option value="6" {{ $value == '6' ? 'selected' : '' }}>Juni</option>
        <option value="7" {{ $value == '7' ? 'selected' : '' }}>Juli</option>
        <option value="8" {{ $value == '8' ? 'selected' : '' }}>Agustus</option>
        <option value="9" {{ $value == '9' ? 'selected' : '' }}>September</option>
        <option value="10" {{ $value == '10' ? 'selected' : '' }}>Oktober</option>
        <option value="11" {{ $value == '11' ? 'selected' : '' }}>November</option>
        <option value="12" {{ $value == '12' ? 'selected' : '' }}>Desember</option>
    </select>
</div>
