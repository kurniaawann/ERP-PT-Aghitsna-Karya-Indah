@props(['url' => '#', 'size' => 'xs'])

<a href="{{ $url }}"
    class="flex items-center gap-1 bg-error hover:bg-error/90 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-{{ $size }}"
    title="{{ config('button.pdf') }}">
    <i class="fa-solid fa-file-pdf w-3 h-3"></i>
    <span>PDF</span>
</a>
