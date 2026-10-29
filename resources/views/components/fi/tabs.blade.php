{{--
    Tab navigasi bergaya Filament: garis bawah, rata kiri sejajar judul halaman
    dan tabel (di antara header halaman dan tabel). Setiap tab adalah link ke
    halamannya sendiri, sehingga route, filter, dan JS tiap halaman tetap
    terpisah. Di layar sempit tab bisa digeser ke samping.

    Props:
    - tabs   : [key => ['label' => ..., 'icon' => nama x-fi.icon (opsional), 'url' => ...]]
    - active : key tab yang sedang aktif
--}}
@props(['tabs' => [], 'active' => null])

<nav {{ $attributes->class('flex gap-x-6 overflow-x-auto border-b border-gray-200') }} aria-label="Tab">
    @foreach ($tabs as $key => $tab)
        @php $isActive = $active === $key; @endphp

        <a href="{{ $tab['url'] }}" @if ($isActive) aria-current="page" @endif
            @class([
                '-mb-px flex shrink-0 items-center gap-x-2 whitespace-nowrap border-b-2 px-1 pb-3 pt-1 text-sm font-medium outline-none transition duration-75 focus-visible:text-primary',
                'border-primary text-primary' => $isActive,
                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => ! $isActive,
            ])>
            @if (! empty($tab['icon']))
                <x-fi.icon :name="$tab['icon']" :class="'h-5 w-5 ' . ($isActive ? 'text-primary' : 'text-gray-400')" />
            @endif
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
