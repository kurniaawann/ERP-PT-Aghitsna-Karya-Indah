@props(['paginator'])

<div class="flex flex-col sm:flex-row justify-between items-center gap-3 mt-4 px-4 py-3 bg-surface-base border border-border-strong rounded-lg shadow-sm">
    {{-- Previous --}}
    <a href="{{ $paginator->appends(request()->query())->previousPageUrl() }}"
        class="w-full sm:w-auto text-center px-4 py-2 text-sm font-medium rounded-md border border-border-strong text-text-label transition-colors duration-200
        {{ $paginator->onFirstPage() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:bg-surface-hover hover:border-primary' }}">
        Previous
    </a>

    {{-- Page indicator --}}
    <span class="text-sm font-medium text-text-primary whitespace-nowrap">
        Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
    </span>

    {{-- Next --}}
    <a href="{{ $paginator->appends(request()->query())->nextPageUrl() }}"
        class="w-full sm:w-auto text-center px-4 py-2 text-sm font-medium rounded-md border border-border-strong text-text-label transition-colors duration-200
        {{ !$paginator->hasMorePages() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:bg-surface-hover hover:border-primary' }}">
        Next
    </a>
</div>
