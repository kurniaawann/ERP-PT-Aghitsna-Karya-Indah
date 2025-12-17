@props(['excelRoute' => null, 'pdfRoute' => null, 'queryParams' => []])

<div class="relative inline-block text-left w-full sm:w-auto">
    <button type="button" id="printDropdownButton"
        class="w-full sm:w-auto flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-3 py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium">
        <i class="fa-solid fa-print w-4 h-4"></i>
        <span>Print Laporan</span>
        <i class="fa-solid fa-chevron-down text-xs ml-auto sm:ml-0"></i>
    </button>

    <!-- Dropdown Menu -->
    <div id="printDropdownMenu"
        class="hidden absolute left-0 sm:right-0 sm:left-auto mt-2 w-full sm:w-48 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
        <div class="py-1" role="menu">
            @if ($excelRoute)
                <a href="{{ $excelRoute }}?{{ http_build_query(array_filter($queryParams)) }}"
                    class="flex items-center gap-3 px-4 py-2 text-sm text-text-primary hover:bg-surface-hover transition-colors duration-150">
                    <i class="fa-solid fa-file-excel text-success w-4"></i>
                    <span>Export Excel</span>
                </a>
            @endif

            @if ($pdfRoute)
                <a href="{{ $pdfRoute }}?{{ http_build_query(array_filter($queryParams)) }}"
                    class="flex items-center gap-3 px-4 py-2 text-sm text-text-primary hover:bg-surface-hover transition-colors duration-150">
                    <i class="fa-solid fa-file-pdf text-error w-4"></i>
                    <span>Export PDF</span>
                </a>
            @endif
        </div>
    </div>
</div>
