{{--
    Paginasi bergaya Filament untuk LengthAwarePaginator: ringkasan
    "Menampilkan x sampai y dari z data" + tombol halaman. Tombol halaman
    selalu tampil walau hanya 1 halaman (prev/next nonaktif). Query string
    (mis. ?search=) ikut dibawa ke setiap link.
--}}
@props(['paginator'])

@php
    $paginator->appends(request()->query());
    $links = array_slice($paginator->onEachSide(1)->linkCollection()->all(), 1, -1);

    $navButton = 'flex h-9 w-9 items-center justify-center text-gray-500 outline-none transition duration-75 hover:bg-gray-50 hover:text-gray-700 focus-visible:bg-gray-50';
    $navDisabled = 'flex h-9 w-9 items-center justify-center text-gray-300 cursor-not-allowed';
@endphp

<nav role="navigation" aria-label="Navigasi halaman"
    {{ $attributes->class('flex flex-col items-center justify-between gap-3 sm:flex-row') }}>
    <p class="text-sm text-gray-600">
        Menampilkan
        <span class="font-semibold text-gray-950">{{ $paginator->firstItem() ?? 0 }}</span>
        sampai
        <span class="font-semibold text-gray-950">{{ $paginator->lastItem() ?? 0 }}</span>
        dari
        <span class="font-semibold text-gray-950">{{ $paginator->total() }}</span>
        data
    </p>

    <ol class="flex overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 divide-x divide-gray-200">
        {{-- Sebelumnya --}}
        <li>
            @if ($paginator->onFirstPage())
                <span class="{{ $navDisabled }}" aria-disabled="true">
                    <x-fi.icon name="chevron-left" class="h-4 w-4" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $navButton }}" title="Sebelumnya">
                    <x-fi.icon name="chevron-left" class="h-4 w-4" />
                    <span class="sr-only">Sebelumnya</span>
                </a>
            @endif
        </li>

        {{-- Nomor halaman (disembunyikan di layar kecil) --}}
        @foreach ($links as $link)
            <li class="hidden sm:block">
                @if ($link['url'] === null)
                    <span class="flex h-9 min-w-[2.25rem] items-center justify-center px-2 text-sm text-gray-400">&hellip;</span>
                @elseif ($link['active'])
                    <span aria-current="page"
                        class="flex h-9 min-w-[2.25rem] items-center justify-center bg-gray-50 px-2 text-sm font-semibold text-primary">
                        {{ $link['label'] }}
                    </span>
                @else
                    <a href="{{ $link['url'] }}"
                        class="flex h-9 min-w-[2.25rem] items-center justify-center px-2 text-sm font-semibold text-gray-700 outline-none transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50">
                        {{ $link['label'] }}
                    </a>
                @endif
            </li>
        @endforeach

        {{-- Halaman aktif (layar kecil) --}}
        <li class="sm:hidden">
            <span class="flex h-9 items-center justify-center px-3 text-sm font-semibold text-gray-700">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>
        </li>

        {{-- Berikutnya --}}
        <li>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $navButton }}" title="Berikutnya">
                    <x-fi.icon name="chevron-right" class="h-4 w-4" />
                    <span class="sr-only">Berikutnya</span>
                </a>
            @else
                <span class="{{ $navDisabled }}" aria-disabled="true">
                    <x-fi.icon name="chevron-right" class="h-4 w-4" />
                </span>
            @endif
        </li>
    </ol>
</nav>
