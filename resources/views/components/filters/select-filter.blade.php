@props([
    'name' => 'category',
    'value' => null,
    'options' => [],
    'placeholder' => 'Semua Kategori',
    'autoSubmit' => false,
])

<div class="w-full lg:w-auto">
    <label for="{{ $name }}-select" class="sr-only">{{ $placeholder }}</label>
    <select name="{{ $name }}" id="{{ $name }}-select"
        class="block w-full lg:w-48 rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 
               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light"
        {{ $autoSubmit ? 'onchange=this.form.submit()' : '' }}>
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $option)
            <option value="{{ $option->id }}" {{ $value == $option->id ? 'selected' : '' }}>
                {{ $option->name }}
            </option>
        @endforeach
    </select>
</div>
