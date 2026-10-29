{{--
    Tab navigasi bergaya Filament (di antara header halaman dan tabel).
    Setiap tab adalah link ke halamannya sendiri, sehingga route, filter,
    dan JS tiap halaman tetap terpisah.

    Props:
    - tabs   : [key => ['label' => ..., 'icon' => nama x-fi.icon (opsional), 'url' => ...]]
    - active : key tab yang sedang aktif
--}}
@props(['tabs' => [], 'active' => null])

<nav {{ $attributes->class('mx-auto flex w-fit max-w-full gap-x-1 overflow-x-auto rounded-xl bg-white p-2 shadow-sm ring-1 ring-gray-950/5') }}
    aria-label="Tab">
    @foreach ($tabs as $key => $tab)
        @php $isActive = $active === $key; @endphp

        <a href="{{ $tab['url'] }}" @if ($isActive) aria-current="page" @endif
            @class([
                'flex shrink-0 items-center justify-center gap-x-2 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium outline-none transition duration-75 focus-visible:ring-2 focus-visible:ring-primary/50',
                'bg-gray-50 text-primary' => $isActive,
                'text-gray-500 hover:bg-gray-50 hover:text-gray-700' => ! $isActive,
            ])>
            @if (! empty($tab['icon']))
                <x-fi.icon :name="$tab['icon']" :class="'h-5 w-5 ' . ($isActive ? 'text-primary' : 'text-gray-400')" />
            @endif
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
