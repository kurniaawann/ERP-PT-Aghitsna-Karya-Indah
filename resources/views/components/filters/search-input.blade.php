@props(['value' => '', 'placeholder' => 'Cari...'])

<div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-1">
    <div class="relative flex-1">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <svg class="w-4 h-4 text-text-secondary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20"
                aria-hidden="true">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
            </svg>
        </span>

        <input type="text" name="search" value="{{ $value }}" placeholder="{{ $placeholder }}"
            data-search-debounce
            class="block w-full rounded-lg border border-border-strong bg-surface-secondary p-3 pl-10 text-sm text-text-input 
                   focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light" />
    </div>
</div>
