{{--
    Item menu sidebar bergaya Filament (dipakai layouts/sidebar.blade.php).

    Props:
    - href   : URL tujuan
    - icon   : nama ikon x-fi.icon
    - active : true bila halaman yang dibuka milik menu ini
    - badge  : angka notifikasi (opsional; tidak tampil bila kosong / 0)
--}}
@props([
    'href',
    'icon',
    'active' => false,
    'badge' => null,
])

<li>
    <a href="{{ $href }}" @if ($active) aria-current="page" @endif
        {{ $attributes->class([
            'flex items-center gap-x-3 rounded-lg px-2.5 py-2 text-sm font-medium outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 focus-visible:ring-2 focus-visible:ring-primary/50',
            'bg-gray-100 text-primary' => $active,
            'text-gray-700 hover:text-gray-950' => !$active,
        ]) }}>
        <x-fi.icon :name="$icon" :class="'h-5 w-5 ' . ($active ? 'text-primary' : 'text-gray-400')" />
        <span class="min-w-0 flex-1">{{ $slot }}</span>

        @if ($badge)
            <span class="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-red-600 px-1.5 text-xs font-semibold leading-5 text-white">
                {{ $badge }}
            </span>
        @endif
    </a>
</li>
