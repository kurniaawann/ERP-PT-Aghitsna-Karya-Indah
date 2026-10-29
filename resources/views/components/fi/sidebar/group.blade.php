{{--
    Grup menu sidebar yang bisa dibuka/tutup (gaya Filament), berisi
    x-fi.sidebar.item (atau x-fi.sidebar.group bertingkat). Submenu diberi
    garis vertikal di kiri; buka/tutup lewat toggleDropdown(id) di
    layouts/sidebar.blade.php.

    Props:
    - id    : id <ul> submenu (ikon panah: id + "Icon")
    - icon  : nama ikon x-fi.icon
    - label : nama grup
    - open  : true bila salah satu menu di dalamnya sedang dibuka (grup
              terbuka & ikon grup diberi warna primary)
--}}
@props([
    'id',
    'icon',
    'label',
    'open' => false,
])

<li>
    <button type="button" onclick="toggleDropdown('{{ $id }}')" aria-controls="{{ $id }}"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
        class="flex w-full items-center gap-x-3 rounded-lg px-2.5 py-2 text-start text-sm font-medium outline-none transition duration-75 hover:bg-gray-100 hover:text-gray-950 focus-visible:bg-gray-100 focus-visible:ring-2 focus-visible:ring-primary/50 {{ $open ? 'text-gray-950' : 'text-gray-700' }}">
        <x-fi.icon :name="$icon" :class="'h-5 w-5 ' . ($open ? 'text-primary' : 'text-gray-400')" />
        <span class="min-w-0 flex-1">{{ $label }}</span>
        <x-fi.icon name="chevron-down" id="{{ $id }}Icon"
            :class="'h-4 w-4 text-gray-400 transition-transform duration-200 ' . ($open ? 'rotate-180' : '')" />
    </button>

    <ul id="{{ $id }}" class="ms-5 mt-1 flex flex-col gap-y-0.5 border-s border-gray-200 ps-2 {{ $open ? '' : 'hidden' }}">
        {{ $slot }}
    </ul>
</li>
