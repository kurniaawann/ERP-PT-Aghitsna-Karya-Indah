@props(['pdfRoute' => null, 'queryParams' => []])

<div class="relative inline-block text-left w-full sm:w-auto">
    <button type="button" id="printDropdownButton"
        class="w-full sm:w-auto flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-3 py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium">
        <i class="fa-solid fa-print w-4 h-4"></i>
        <span>Print Laporan</span>
        <i class="fa-solid fa-chevron-down text-xs ml-auto sm:ml-0"></i>
    </button>

    <!-- Dropdown Menu -->
    <div id="printDropdownMenu"
        class="hidden absolute left-0 sm:right-0 sm:left-auto mt-2 w-full sm:w-56 rounded-lg shadow-lg bg-surface-base border border-border-strong z-50">
        <div class="py-1" role="menu">
            @if ($pdfRoute)
                {{-- Export All --}}
                <a href="{{ $pdfRoute }}?{{ http_build_query(array_filter($queryParams)) }}"
                    class="flex items-center gap-3 px-4 py-2 text-sm text-text-primary hover:bg-surface-hover transition-colors duration-150">
                    <i class="fa-solid fa-file-pdf text-error w-4"></i>
                    <span>Export Semua (PDF)</span>
                </a>

                {{-- Export Selected --}}
                <button type="button" onclick="printSelected(this)"
                    class="w-full flex items-center gap-3 px-4 py-2 text-sm text-text-primary hover:bg-surface-hover transition-colors duration-150 text-left">
                    <i class="fa-solid fa-check-square text-primary w-4"></i>
                    <span>Export Dipilih (<span id="selectedCountText">0</span>)</span>
                </button>
            @endif
        </div>
    </div>
</div>
