@props([
    'name' => 'project_name',
    'route' => null,
    'value' => null,
    'placeholder' => '-- Pilih Proyek --',
    'allOption' => 'Semua Proyek',
    'fill' => false,
    'responsive' => 'xl',
    'autoSubmit' => true,
    'inputId' => null,
    'dropdownId' => null,
    'required' => false,
])

<div class="project-dropdown relative {{ $fill ? 'flex-1' : ($responsive === 'custom' ? 'w-full min-[1530px]:w-auto' : 'w-full xl:w-auto') }}"
    data-route="{{ $route }}"
    data-placeholder="{{ $placeholder }}"
    data-all-option="{{ $allOption }}"
    data-auto-submit="{{ $autoSubmit ? '1' : '0' }}"
    @if ($dropdownId) id="{{ $dropdownId }}" @endif>
    <input type="hidden" name="{{ $name }}" class="project-dropdown-hidden"
        value="{{ $value }}" @if ($inputId) id="{{ $inputId }}" @endif {{ $required ? 'required' : '' }}>
    <button type="button"
        class="project-dropdown-toggle w-full flex items-center justify-between gap-2 rounded-lg border border-border-strong bg-surface-secondary px-3 py-3 text-sm text-text-input focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
        <span class="project-dropdown-label truncate {{ $value ? 'text-text-input' : 'text-text-tertiary' }}">{{ $value ?: $placeholder }}</span>
        <i class="fa-solid fa-chevron-down text-xs text-text-tertiary"></i>
    </button>

    <div class="project-dropdown-menu absolute left-0 z-50 mt-1 w-full rounded-lg bg-surface-base border border-border-strong shadow-lg hidden">
        <div class="p-2 border-b border-border-light">
            <input type="text" class="project-dropdown-search w-full rounded border border-border-light px-2 py-1.5 text-sm bg-surface-base text-text-input"
                placeholder="Cari nama proyek...">
        </div>
        <div class="project-dropdown-list max-h-60 overflow-y-auto">
            <div class="p-2 text-sm text-text-secondary">Silakan klik untuk memuat data...</div>
        </div>
    </div>
</div>
