{{--
    Modal bergaya Filament. Dibuka/ditutup dengan openModal(id) / closeModal(id)
    dari layouts/app (toggle class hidden ↔ flex), sama seperti x-modal.

    Mode:
    - Form       : isi `action` → slot dibungkus <form> (+@csrf, @method),
                   footer [submit#submit-btn-{id}] [Batal].
    - Konfirmasi : isi `onConfirm` → tampilan tengah dengan ikon,
                   footer [Batal] [tombol#confirm-btn-{id} onclick=onConfirm].
    - Biasa      : tanpa keduanya → hanya isi slot.

    Props:
    - id, heading, description (opsional)
    - icon, color   : ikon & warna (primary | danger | success | warning)
    - action, method, enctype, formId
    - onConfirm
    - submitLabel, submitIcon, cancelLabel
    - width         : sm | md | lg | xl | 2xl | 4xl (default lg)
--}}
@props([
    'id',
    'heading' => null,
    'description' => null,
    'icon' => null,
    'color' => 'primary',
    'action' => null,
    'method' => 'POST',
    'enctype' => null,
    'formId' => null,
    'onConfirm' => null,
    'submitLabel' => 'Simpan',
    'submitIcon' => null,
    'cancelLabel' => 'Batal',
    'width' => 'lg',
])

@php
    $widthClass = match ($width) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '4xl' => 'max-w-4xl',
        default => 'max-w-lg',
    };

    $iconWrapClass = match ($color) {
        'danger' => 'bg-red-100 text-red-600',
        'success' => 'bg-emerald-100 text-emerald-600',
        'warning' => 'bg-amber-100 text-amber-600',
        default => 'bg-blue-100 text-primary',
    };
@endphp

<div id="{{ $id }}" role="dialog" aria-modal="true" @if ($heading) aria-labelledby="{{ $id }}-heading" @endif
    class="hidden fixed inset-0 z-50 items-center justify-center bg-gray-950/50 p-4 text-base">
    <div class="relative w-full {{ $widthClass }} max-h-[90vh] overflow-y-auto rounded-xl bg-white shadow-xl ring-1 ring-gray-950/5">

        {{-- Tombol tutup (X) --}}
        <button type="button" onclick="closeModal('{{ $id }}')" title="Tutup"
            class="absolute end-4 top-4 flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 outline-none transition duration-75 hover:bg-gray-50 hover:text-gray-500 focus-visible:ring-2 focus-visible:ring-primary/50">
            <x-fi.icon name="x-mark" class="h-5 w-5" />
            <span class="sr-only">Tutup</span>
        </button>

        @if ($onConfirm)
            {{-- Mode konfirmasi --}}
            <div class="px-6 pt-6 text-center">
                @if ($icon)
                    <div class="mx-auto mb-5 flex h-12 w-12 items-center justify-center rounded-full {{ $iconWrapClass }}">
                        <x-fi.icon :name="$icon" class="h-6 w-6" />
                    </div>
                @endif

                @if ($heading)
                    <h2 id="{{ $id }}-heading" class="text-base font-semibold leading-6 text-gray-950">{{ $heading }}</h2>
                @endif

                <div class="mt-2 text-sm text-gray-500">{{ $slot }}</div>
            </div>

            <div class="grid grid-cols-2 gap-3 p-6">
                <x-fi.button color="gray" onclick="closeModal('{{ $id }}')">{{ $cancelLabel }}</x-fi.button>
                <x-fi.button id="confirm-btn-{{ $id }}" :color="$color" :icon="$submitIcon" onclick="{{ $onConfirm }}">
                    {{ $submitLabel }}
                </x-fi.button>
            </div>
        @else
            {{-- Header --}}
            @if ($heading)
                <div class="flex items-start gap-x-4 px-6 pe-16 pt-6">
                    @if ($icon)
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $iconWrapClass }}">
                            <x-fi.icon :name="$icon" class="h-5 w-5" />
                        </div>
                    @endif
                    <div class="{{ $icon ? 'pt-2' : '' }}">
                        <h2 id="{{ $id }}-heading" class="text-base font-semibold leading-6 text-gray-950">{{ $heading }}</h2>
                        @if ($description)
                            <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
                        @endif
                    </div>
                </div>
            @endif

            @if ($action)
                {{-- Mode form --}}
                <form action="{{ $action }}" method="POST"
                    @if ($formId) id="{{ $formId }}" @endif
                    @if ($enctype) enctype="{{ $enctype }}" @endif>
                    @csrf
                    @if (in_array(strtoupper($method), ['PUT', 'PATCH', 'DELETE']))
                        @method($method)
                    @endif

                    <div class="flex flex-col gap-y-5 px-6 py-6">
                        {{ $slot }}
                    </div>

                    <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 px-6 py-4">
                        <x-fi.button type="submit" id="submit-btn-{{ $id }}" :color="$color" :icon="$submitIcon">
                            {{ $submitLabel }}
                        </x-fi.button>
                        <x-fi.button color="gray" onclick="closeModal('{{ $id }}')">{{ $cancelLabel }}</x-fi.button>
                    </div>
                </form>
            @else
                {{-- Mode biasa --}}
                <div class="px-6 py-6">
                    {{ $slot }}
                </div>
            @endif
        @endif
    </div>
</div>
