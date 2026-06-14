@props(['onclick' => '', 'icon' => 'eye', 'size' => 'xs'])

<button type="button" onclick="{{ $onclick }}"
    class="flex items-center gap-1 bg-info hover:bg-info/90 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-{{ $size }}"
    title="{{ config('button.detail') }}">
    <i class="fa-solid fa-{{ $icon }} w-3 h-3"></i>
    <span>{{ config('button.detail') }}</span>
</button>
