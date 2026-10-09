{{-- Props:
     - pdfRoute / excelRoute           : export semua (GET, ikut queryParams)
     - pdfSelectedRoute / excelSelectedRoute : export data terpilih (POST ids[]) via
       sharedPrintSelected → pratinjau dulu baru download. Bila pdfSelectedRoute
       kosong, tombol "Export Dipilih" memanggil printSelected(this) milik halaman.
     - selectedQueryParams : filter halaman yang ikut dikirim saat Export Dipilih
       (mis. reimburse: teks "Disetujui x dari y yang diajukan" tetap memakai
       seluruh pengajuan sesuai filter sebagai pembanding).
     - pdfAttributes : atribut tambahan pada link "Export Semua (PDF)"
       (mis. data-nota-sign untuk nota yang wajib ditandatangani dulu). --}}
@props(['pdfRoute' => null, 'excelRoute' => null, 'pdfSelectedRoute' => null, 'excelSelectedRoute' => null, 'queryParams' => [], 'selectedQueryParams' => [], 'pdfAttributes' => [], 'responsive' => 'xl', 'fill' => false])
@php
    $selectedExtra = array_filter((array) $selectedQueryParams, fn ($value) => $value !== null && $value !== '');
@endphp

@if($responsive === 'custom')
    @php
        $wrapperClass = $fill ? 'relative inline-block text-left flex-1' : 'relative inline-block text-left w-full min-[1530px]:w-auto';
        $buttonClass = 'w-full flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-3 py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium';
    @endphp
@else
    @php
        $wrapperClass = $fill ? 'relative inline-block text-left flex-1' : 'relative inline-block text-left w-full sm:w-auto';
        $buttonClass = 'w-full sm:w-auto flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-3 py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium';
    @endphp
@endif

<div class="{{ $wrapperClass }}">
    <button type="button" id="printDropdownButton"
        class="{{ $buttonClass }}">
        <i class="fa-solid fa-print w-4 h-4"></i>
        <span>Print Laporan</span>
        <i class="fa-solid fa-chevron-down text-xs ml-auto sm:ml-0"></i>
    </button>

    <!-- Dropdown Menu -->
    @if($responsive === 'custom')
    <div id="printDropdownMenu"
        class="hidden absolute left-0 min-[1530px]:right-0 min-[1530px]:left-auto mt-2 w-full min-[1530px]:w-56 rounded-lg shadow-lg bg-surface-base border border-border-strong z-50">
    @else
    <div id="printDropdownMenu"
        class="hidden absolute left-0 sm:right-0 sm:left-auto mt-2 w-full sm:w-56 rounded-lg shadow-lg bg-surface-base border border-border-strong z-50">
    @endif
        <div class="py-1" role="menu">
            {{-- Export All --}}
            @if ($pdfRoute)
                <a href="{{ $pdfRoute }}?{{ http_build_query(array_filter($queryParams)) }}" data-preview
                    {{ new \Illuminate\View\ComponentAttributeBag((array) $pdfAttributes) }}
                    class="flex items-center gap-3 px-4 py-2 text-sm text-text-primary hover:bg-surface-hover transition-colors duration-150">
                    <i class="fa-solid fa-file-pdf text-error w-4"></i>
                    <span>Export Semua (PDF)</span>
                </a>
            @endif
            @if ($excelRoute)
                <a href="{{ $excelRoute }}?{{ http_build_query(array_filter($queryParams)) }}" data-preview
                    class="flex items-center gap-3 px-4 py-2 text-sm text-text-primary hover:bg-surface-hover transition-colors duration-150">
                    <i class="fa-solid fa-file-excel text-success w-4"></i>
                    <span>Export Semua (Excel)</span>
                </a>
            @endif

            {{-- Export Selected (muncul saat ada data dicentang) --}}
            @if ($pdfRoute || $pdfSelectedRoute || $excelSelectedRoute)
                <div id="printSelectedItem" class="hidden">
                    @if ($pdfSelectedRoute || $pdfRoute)
                        <button type="button"
                            @if ($pdfSelectedRoute && $selectedExtra)
                                onclick="sharedPrintSelected(@js($pdfSelectedRoute), this, undefined, undefined, @js($selectedExtra))"
                            @elseif ($pdfSelectedRoute)
                                onclick="sharedPrintSelected(@js($pdfSelectedRoute), this)"
                            @else
                                onclick="printSelected(this)"
                            @endif
                            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-text-primary hover:bg-surface-hover transition-colors duration-150 text-left">
                            <i class="fa-solid fa-check-square text-primary w-4"></i>
                            <span>Export Dipilih{{ $excelSelectedRoute ? ' (PDF)' : '' }} (<span id="selectedCountText">0</span>)</span>
                        </button>
                    @endif
                    @if ($excelSelectedRoute)
                        <button type="button"
                            @if ($selectedExtra)
                                onclick="sharedPrintSelected(@js($excelSelectedRoute), this, undefined, undefined, @js($selectedExtra))"
                            @else
                                onclick="sharedPrintSelected(@js($excelSelectedRoute), this)"
                            @endif
                            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-text-primary hover:bg-surface-hover transition-colors duration-150 text-left">
                            <i class="fa-solid fa-file-excel text-success w-4"></i>
                            <span>Export Dipilih (Excel)</span>
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
