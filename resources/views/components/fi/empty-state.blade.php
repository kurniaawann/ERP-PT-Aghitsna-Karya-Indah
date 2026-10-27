{{--
    Empty state tabel bergaya Filament: ikon dalam lingkaran, judul, deskripsi.
    Slot (opsional) untuk tombol aksi di bawah deskripsi.
--}}
@props([
    'icon' => 'x-mark',
    'heading',
    'description' => null,
])

<div {{ $attributes->class('px-6 py-12') }}>
    <div class="mx-auto grid max-w-lg justify-items-center text-center">
        <div class="mb-4 rounded-full bg-gray-100 p-3">
            <x-fi.icon :name="$icon" class="h-6 w-6 text-gray-500" />
        </div>

        <h4 class="text-base font-semibold leading-6 text-gray-950">{{ $heading }}</h4>

        @if ($description)
            <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
        @endif

        @if ($slot->isNotEmpty())
            <div class="mt-6">{{ $slot }}</div>
        @endif
    </div>
</div>
