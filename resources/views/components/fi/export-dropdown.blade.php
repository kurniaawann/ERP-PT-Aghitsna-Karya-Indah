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
    - pdfSelectedRoute, excelSelectedRoute : export data yang dicentang (POST
                             ids[] lewat sharedPrintSelected → pratinjau dulu).
                             Grup #printSelectedItem tersembunyi sampai JS
                             halaman menampilkannya (ada baris dicentang);
                             jumlahnya di #selectedCountText.
    - selectedQueryParams  : filter halaman yang ikut dikirim saat Export Dipilih
--}}
@props([
    'excelRoute' => null,
    'pdfRoute' => null,
    'queryParams' => [],
    'label' => 'Export',
    'disabled' => false,
    'requireSigner' => false,
    'pdfSelectedRoute' => null,
    'excelSelectedRoute' => null,
    'selectedQueryParams' => [],
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
    $hasSelected = $pdfSelectedRoute || $excelSelectedRoute;
    $selectedExtra = array_filter((array) $selectedQueryParams, fn ($value) => $value !== null && $value !== '');
    $itemClass = 'flex w-full items-center gap-2 whitespace-nowrap rounded-md p-2 text-start text-sm text-gray-700 outline-none transition-colors duration-75 hover:bg-gray-50 focus-visible:bg-gray-50';
@endphp

<div {{ $attributes->class('relative inline-block text-left') }}>
    <x-fi.button id="printDropdownButton" color="gray" icon="arrow-down-tray" :disabled="$disabled"
        aria-haspopup="menu">
        {{ $label }}
        <x-fi.icon name="chevron-down" class="-me-0.5 ms-0.5 inline-block h-4 w-4 align-[-2px] text-gray-400" />
    </x-fi.button>

    <div id="printDropdownMenu" role="menu"
        class="hidden absolute left-0 z-20 mt-2 w-56 divide-y divide-gray-100 rounded-lg bg-white shadow-lg ring-1 ring-gray-950/5 sm:left-auto sm:right-0">
        <div class="p-1">
            @if ($hasSelected)
                <p class="px-2 pb-1 pt-1.5 text-xs font-medium text-gray-500">Semua data (sesuai filter)</p>
            @endif

            @if ($excelRoute)
                <a href="{{ $excelRoute }}?{{ $query }}" data-preview role="menuitem"
                    @if ($requireSigner) data-require-signer data-signer-format="Excel" @endif
                    class="{{ $itemClass }}">
                    <x-fi.icon name="table-cells" class="h-5 w-5 text-emerald-600" />
                    <span>Export Excel</span>
                </a>
            @endif

            @if ($pdfRoute)
                <a href="{{ $pdfRoute }}?{{ $query }}" data-preview role="menuitem"
                    @if ($requireSigner) data-require-signer data-signer-format="PDF" @endif
                    class="{{ $itemClass }}">
                    <x-fi.icon name="document-text" class="h-5 w-5 text-red-600" />
                    <span>Export PDF</span>
                </a>
            @endif
        </div>

        {{-- Export data dicentang (ditampilkan JS halaman saat ada pilihan) --}}
        @if ($hasSelected)
            <div id="printSelectedItem" class="hidden p-1">
                <p class="px-2 pb-1 pt-1.5 text-xs font-medium text-gray-500">Data dipilih (<span id="selectedCountText">0</span>)</p>

                @if ($excelSelectedRoute)
                    <button type="button" role="menuitem" class="{{ $itemClass }}"
                        onclick="sharedPrintSelected(@js($excelSelectedRoute), this, undefined, undefined, @js((object) $selectedExtra))">
                        <x-fi.icon name="table-cells" class="h-5 w-5 text-emerald-600" />
                        <span>Export Excel Dipilih</span>
                    </button>
                @endif

                @if ($pdfSelectedRoute)
                    <button type="button" role="menuitem" class="{{ $itemClass }}"
                        onclick="sharedPrintSelected(@js($pdfSelectedRoute), this, undefined, undefined, @js((object) $selectedExtra))">
                        <x-fi.icon name="document-text" class="h-5 w-5 text-red-600" />
                        <span>Export PDF Dipilih</span>
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>
