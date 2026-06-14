@props(['onclick' => '', 'size' => 'xs'])

<button type="button" onclick="{{ $onclick }}"
    class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-{{ $size }}"
    title="{{ config('button.edit') }}">
    <i class="fa-solid fa-pen w-3 h-3"></i>
    <span>{{ config('button.edit') }}</span>
</button>
