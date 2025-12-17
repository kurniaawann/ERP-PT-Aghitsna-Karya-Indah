@props(['paginator'])

<div class="flex mt-4 justify-center">
    <div class="flex items-center gap-3 bg-white border border-border-strong rounded-lg px-4 py-2 shadow-sm">
        <a href="{{ $paginator->appends(request()->query())->previousPageUrl() }}"
            class="flex items-center justify-center w-8 h-8 rounded-md border border-border-strong text-text-label hover:bg-surface-hover transition-colors duration-200
            {{ $paginator->onFirstPage() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:border-primary' }}">
            &lt;
        </a>

        <span class="text-sm font-medium text-text-primary">
            {{ $paginator->currentPage() }}
            <span class="text-text-tertiary">/</span>
            {{ $paginator->lastPage() }}
        </span>

        <a href="{{ $paginator->appends(request()->query())->nextPageUrl() }}"
            class="flex items-center justify-center w-8 h-8 rounded-md border border-border-strong text-text-label hover:bg-surface-hover transition-colors duration-200
            {{ !$paginator->hasMorePages() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:border-primary' }}">
            &gt;
        </a>
    </div>
</div>
