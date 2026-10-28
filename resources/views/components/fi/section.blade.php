{{--
    Section form bergaya Filament: kartu dengan judul, deskripsi (opsional),
    aksi di kanan judul (slot `actions`, opsional), lalu isi.

    Props:
    - heading     : judul section (opsional; tanpa judul hanya kartu berisi)
    - description : teks bantu di bawah judul (opsional)
    - flush       : isi tanpa padding & terpotong sudut kartu (mis. tabel
                    repeater selebar kartu)
--}}
@props([
    'heading' => null,
    'description' => null,
    'flush' => false,
])

<section {{ $attributes->class(['rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5', 'overflow-hidden' => $flush]) }}>
    @if ($heading)
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
            <div>
                <h2 class="text-base font-semibold leading-6 text-gray-950">{{ $heading }}</h2>
                @if ($description)
                    <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex shrink-0 items-center gap-3">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['p-6' => ! $flush])>
        {{ $slot }}
    </div>
</section>
