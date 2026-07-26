@props(['excelRoute' => null, 'pdfRoute' => null, 'queryParams' => [], 'size' => 'default', 'responsive' => 'xl', 'fill' => false])

@if($responsive === 'custom')
    @php
        $wrapperClass = match($size) {
            'sm' => 'relative inline-block text-left w-full',
            default => $fill ? 'relative inline-block text-left flex-1' : 'relative inline-block text-left w-full min-[1530px]:w-auto',
        };

        $buttonClass = match($size) {
            'sm' => 'w-full flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 py-2.5 border border-border-strong rounded-lg transition-colors duration-200 text-sm font-medium',
            default => 'w-full flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium',
        };
    @endphp
@else
    @php
        $wrapperClass = match($size) {
            'sm' => 'relative inline-block text-left w-full',
            default => $fill ? 'relative inline-block text-left flex-1' : 'relative inline-block text-left w-full xl:w-auto',
        };

        $buttonClass = match($size) {
            'sm' => 'w-full flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 py-2.5 border border-border-strong rounded-lg transition-colors duration-200 text-sm font-medium',
            default => 'w-full flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium',
        };
    @endphp
@endif

<div class="{{ $wrapperClass }}">
    <button type="button" id="printDropdownButton"
        class="{{ $buttonClass }}">
        <i class="fa-solid fa-print w-4 h-4"></i>
        <span>Print Laporan</span>
        <i class="fa-solid fa-chevron-down text-xs ml-auto"></i>
    </button>

    <!-- Dropdown Menu -->
    @if($responsive === 'custom')
    <div id="printDropdownMenu"
        class="hidden absolute left-0 min-[1530px]:right-0 min-[1530px]:left-auto mt-2 w-full min-[1530px]:w-48 rounded-lg shadow-lg bg-surface-base border border-border-strong z-50">
    @else
    <div id="printDropdownMenu"
        class="hidden absolute left-0 xl:right-0 xl:left-auto mt-2 w-full xl:w-48 rounded-lg shadow-lg bg-surface-base border border-border-strong z-50">
    @endif
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
