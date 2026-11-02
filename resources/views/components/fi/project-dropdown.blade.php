{{--
    Dropdown proyek bergaya Filament — searchable + pagination 10 item
    (data dari Rekap Proyek via AJAX).

    Markup memakai hook yang sama dengan x-filters.project-filter sehingga
    tetap ditangani resources/js/components/project-dropdown.js:
    .project-dropdown (data-route / data-placeholder / data-all-option /
    data-auto-submit) > .project-dropdown-hidden, .project-dropdown-toggle,
    .project-dropdown-label, .project-dropdown-menu, .project-dropdown-search,
    .project-dropdown-list, .project-dropdown-clear.

    Props:
    - name, value, route, placeholder
    - allOption  : baris "Semua Proyek" di atas daftar (mode filter); null = tidak ada
    - autoSubmit : submit form terdekat setelah memilih (mode filter)
    - clearable  : tombol "Reset (- Tidak Ada Proyek -)" di bawah daftar (mode form)
    - inline     : daftar mendorong konten di bawahnya (dipakai di dalam modal agar
                   tidak terpotong area scroll modal); default melayang (absolute)
    - dropdownId, inputId, required
    Atribut lain (class, ...) diteruskan ke root .project-dropdown.
--}}
@props([
    'name' => 'project_name',
    'route' => null,
    'value' => null,
    'placeholder' => '-- Pilih Proyek --',
    'allOption' => null,
    'autoSubmit' => false,
    'clearable' => false,
    'inline' => false,
    'dropdownId' => null,
    'inputId' => null,
    'required' => false,
])

<div {{ $attributes->class('project-dropdown relative') }}
    data-route="{{ $route ?? route('employee.projects-dropdown') }}"
    data-placeholder="{{ $placeholder }}"
    @if ($allOption) data-all-option="{{ $allOption }}" @endif
    data-auto-submit="{{ $autoSubmit ? '1' : '0' }}"
    @if ($dropdownId) id="{{ $dropdownId }}" @endif>
    <input type="hidden" @if ($name) name="{{ $name }}" @endif class="project-dropdown-hidden" value="{{ $value }}"
        @if ($inputId) id="{{ $inputId }}" @endif @if ($required) required @endif>

    <button type="button" aria-haspopup="listbox"
        class="project-dropdown-toggle flex w-full items-center justify-between gap-2 rounded-lg bg-white py-1.5 pe-3 ps-3 text-start text-base text-gray-950 shadow-sm outline-none ring-1 ring-gray-950/10 transition duration-75 focus-visible:ring-2 focus-visible:ring-primary sm:text-sm sm:leading-6">
        <span class="project-dropdown-label truncate">{{ $value ?: $placeholder }}</span>
        <x-fi.icon name="chevron-down" class="h-5 w-5 shrink-0 text-gray-400" />
    </button>

    <div @class([
        'project-dropdown-menu z-20 mt-1 hidden w-full rounded-lg bg-white p-1 shadow-lg ring-1 ring-gray-950/5',
        'absolute left-0 min-w-[16rem]' => ! $inline,
        'relative' => $inline,
    ])>
        <div class="flex items-center gap-x-2 border-b border-gray-100 px-2 pb-1">
            <x-fi.icon name="magnifying-glass" class="h-4 w-4 shrink-0 text-gray-400" />
            <input type="text" aria-label="Cari nama proyek" placeholder="Cari nama proyek..."
                class="project-dropdown-search block w-full border-none bg-transparent px-0 py-1.5 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0">
        </div>
        <div class="project-dropdown-list max-h-60 overflow-y-auto pt-1">
            <div class="px-2 py-1.5 text-sm text-gray-500">Silakan klik untuk memuat data...</div>
        </div>
        @if ($clearable)
            <div class="border-t border-gray-100 pt-1">
                <button type="button"
                    class="project-dropdown-clear w-full rounded-md px-2 py-1.5 text-start text-sm text-red-600 hover:bg-red-50">
                    Reset (- Tidak Ada Proyek -)
                </button>
            </div>
        @endif
    </div>
</div>
