@props(['url' => '#', 'size' => 'xs'])

<a href="{{ $url }}"
    class="flex items-center gap-1 bg-success hover:bg-success/90 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-{{ $size }}"
    title="{{ config('button.excel') }}">
    <i class="fa-solid fa-file-excel w-3 h-3"></i>
    <span>Excel</span>
</a>
