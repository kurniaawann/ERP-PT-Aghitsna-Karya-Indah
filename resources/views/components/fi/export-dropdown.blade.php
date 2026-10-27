{{--
    Dropdown export (Excel / PDF) bergaya Filament.

    Memakai id #printDropdownButton / #printDropdownMenu agar tetap ditangani
    resources/js/shared/print.js (buka/tutup), dan link ber-atribut
    data-preview agar tetap lewat pratinjau dokumen (shared/document-preview.js).
    Hanya boleh satu per halaman (id tetap).

    Props:
    - excelRoute, pdfRoute : URL export (yang kosong tidak ditampilkan)
    - queryParams          : parameter query tambahan untuk kedua link
    - label                : teks tombol (default "Export")
    - disabled             : nonaktifkan tombol
    - requireSigner        : minta Penandatangan Laporan dulu (print-signer)
--}}
@props([
    'excelRoute' => null,
    'pdfRoute' => null,
    'queryParams' => [],
    'label' => 'Export',
    'disabled' => false,
    'requireSigner' => false,
])

@if ($requireSigner)
    @once
        @push('modals')
            <x-print-signer-modal />
        @endpush
    @endonce
@endif

@php
    $query = http_build_query(array_filter($queryParams));
@endphp

<div {{ $attributes->class('relative inline-block text-left') }}>
    <x-fi.button id="printDropdownButton" color="gray" icon="arrow-down-tray" :disabled="$disabled"
        aria-haspopup="menu">
        {{ $label }}
        <x-fi.icon name="chevron-down" class="-me-0.5 ms-0.5 inline-block h-4 w-4 align-[-2px] text-gray-400" />
    </x-fi.button>

    <div id="printDropdownMenu" role="menu"
        class="hidden absolute left-0 z-20 mt-2 w-56 rounded-lg bg-white shadow-lg ring-1 ring-gray-950/5 sm:left-auto sm:right-0">
        <div class="p-1">
            @if ($excelRoute)
                <a href="{{ $excelRoute }}?{{ $query }}" data-preview role="menuitem"
                    @if ($requireSigner) data-require-signer data-signer-format="Excel" @endif
                    class="flex w-full items-center gap-2 whitespace-nowrap rounded-md p-2 text-sm text-gray-700 outline-none transition-colors duration-75 hover:bg-gray-50 focus-visible:bg-gray-50">
                    <x-fi.icon name="table-cells" class="h-5 w-5 text-emerald-600" />
                    <span>Export Excel</span>
                </a>
            @endif

            @if ($pdfRoute)
                <a href="{{ $pdfRoute }}?{{ $query }}" data-preview role="menuitem"
                    @if ($requireSigner) data-require-signer data-signer-format="PDF" @endif
                    class="flex w-full items-center gap-2 whitespace-nowrap rounded-md p-2 text-sm text-gray-700 outline-none transition-colors duration-75 hover:bg-gray-50 focus-visible:bg-gray-50">
                    <x-fi.icon name="document-text" class="h-5 w-5 text-red-600" />
                    <span>Export PDF</span>
                </a>
            @endif
        </div>
    </div>
</div>
