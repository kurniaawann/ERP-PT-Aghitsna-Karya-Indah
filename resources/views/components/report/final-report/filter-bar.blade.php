{{-- Bar filter tab Laporan Akhir (gaya Filament): label "Filter" di kiri,
     kontrol filter + pencarian di kanan (pencarian paling akhir), tombol Reset
     saat ada filter aktif. Form GET ke route('report.final') dengan hidden
     input "tab" agar tetap di tab yang sama; select filter langsung submit
     saat diganti (onchange), pencarian saat Enter.

     Props:
     - tab    : key tab aktif (stock|sales|expense|cement|recap|financial)
     - keys   : nama parameter filter yang dicek untuk tombol Reset
     - formId : id form (opsional) --}}
@props(['tab', 'keys' => ['month', 'year', 'status', 'search'], 'formId' => null])

@php
    $isFiltered = collect($keys)->contains(fn ($key) => request()->filled($key));
@endphp

<x-fi.card class="flex flex-wrap items-center gap-3 px-4 py-3 sm:px-6">
    <span class="flex items-center gap-x-2 text-sm font-medium text-gray-500">
        <x-fi.icon name="funnel" class="h-5 w-5 text-gray-400" />
        Filter
    </span>

    <form method="GET" action="{{ route('report.final') }}" @if ($formId) id="{{ $formId }}" @endif
        class="grid w-full grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-end lg:ms-auto lg:w-auto">
        {{-- Hidden: pertahankan tab aktif --}}
        <input type="hidden" name="tab" value="{{ $tab }}">

        {{ $slot }}

        @if ($isFiltered)
            <x-fi.link :href="route('report.final', ['tab' => $tab])" color="gray" icon="x-mark"
                class="col-span-2 justify-self-start whitespace-nowrap" title="Hapus semua filter & pencarian">
                Reset
            </x-fi.link>
        @endif
    </form>
</x-fi.card>
