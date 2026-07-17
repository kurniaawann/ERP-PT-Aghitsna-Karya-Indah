@props(['paginator'])

<div class="mt-4">
    <div class="flex items-center justify-between bg-surface-base border border-border-strong rounded-lg px-4 py-2 shadow-sm">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-text-tertiary border border-border-strong rounded-md opacity-50 select-none">
                &laquo; Previous
            </span>
        @else
            <a href="{{ $paginator->appends(request()->query())->previousPageUrl() }}"
               class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-text-label border border-border-strong rounded-md hover:bg-surface-hover hover:border-primary transition-colors duration-200">
                &laquo; Previous
            </a>
        @endif

        {{-- Page indicator --}}
        <span class="text-sm font-medium text-text-primary px-2 select-none">
            {{ $paginator->currentPage() }}<span class="text-text-tertiary">/</span>{{ $paginator->lastPage() }}
        </span>

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->appends(request()->query())->nextPageUrl() }}"
               class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-text-label border border-border-strong rounded-md hover:bg-surface-hover hover:border-primary transition-colors duration-200">
                Next &gt;&gt;
            </a>
        @else
            <span class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-text-tertiary border border-border-strong rounded-md opacity-50 select-none">
                Next &gt;&gt;
            </span>
        @endif
    </div>
</div>
