{{--
    Dropdown aksi bergaya Filament (ActionGroup): tombol ikon (default ⋮)
    yang membuka panel berisi x-fi.dropdown.list / x-fi.dropdown.item.
    Buka/tutup & posisi diatur resources/js/shared/fi-dropdown.js; saat
    terbuka panel dipindah ke <body> supaya tidak terpotong pembungkus
    tabel (overflow) maupun kolom sticky.

    Props:
    - icon  : ikon tombol pemicu (default ellipsis-vertical)
    - label : label aksesibilitas / tooltip tombol (default "Aksi")
    - width : class lebar panel (default w-56)
--}}
@props([
    'icon' => 'ellipsis-vertical',
    'label' => 'Aksi',
    'width' => 'w-56',
])

<div {{ $attributes->class('relative inline-flex') }} data-fi-dropdown>
    <button type="button" data-fi-dropdown-trigger aria-haspopup="menu" aria-expanded="false" title="{{ $label }}"
        class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 outline-none transition duration-75 hover:bg-gray-100 hover:text-gray-600 focus-visible:ring-2 focus-visible:ring-primary/50 aria-expanded:bg-gray-100 aria-expanded:text-gray-600">
        <x-fi.icon :name="$icon" class="h-5 w-5" />
        <span class="sr-only">{{ $label }}</span>
    </button>

    <div data-fi-dropdown-panel role="menu"
        class="hidden fixed z-40 {{ $width }} divide-y divide-gray-100 rounded-lg bg-white text-start shadow-lg ring-1 ring-gray-950/5">
        {{ $slot }}
    </div>
</div>
