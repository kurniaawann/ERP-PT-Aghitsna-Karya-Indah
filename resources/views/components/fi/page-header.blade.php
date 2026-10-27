{{--
    Header halaman bergaya Filament: breadcrumb, judul, dan aksi di kanan.

    Props:
    - heading     : judul halaman
    - subheading  : teks kecil di bawah judul (opsional)
    - breadcrumbs : ['Label' => url|null, ...] (opsional)
    Slot `actions` untuk tombol header.
--}}
@props([
    'heading',
    'subheading' => null,
    'breadcrumbs' => [],
])

<header {{ $attributes->class('flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between') }}>
    <div>
        @if (count($breadcrumbs))
            <nav aria-label="Breadcrumb" class="mb-2 hidden sm:block">
                <ol class="flex flex-wrap items-center gap-x-2">
                    @foreach ($breadcrumbs as $label => $url)
                        <li class="flex items-center gap-x-2">
                            @unless ($loop->first)
                                <x-fi.icon name="chevron-right" class="h-4 w-4 text-gray-400" />
                            @endunless

                            @if ($url)
                                <a href="{{ $url }}" class="text-sm font-medium text-gray-500 transition duration-75 hover:text-gray-700">{{ $label }}</a>
                            @else
                                <span class="text-sm font-medium text-gray-500">{{ $label }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <h1 class="text-2xl font-bold tracking-tight text-gray-950 sm:text-3xl">{{ $heading }}</h1>

        @if ($subheading)
            <p class="mt-1 text-sm text-gray-500">{{ $subheading }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-3">
            {{ $actions }}
        </div>
    @endisset
</header>
