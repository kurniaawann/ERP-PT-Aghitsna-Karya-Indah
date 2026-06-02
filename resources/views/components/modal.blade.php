@props([
    'id',
    'title' => 'Modal Title',
    'action' => '#',
    'method' => 'POST',
    'buttonText' => 'Simpan',
    'readonly' => false,
    'confirmDelete' => false,
    'onConfirm' => null,
    'size' => 'lg',
    'formId' => null,
    'onsubmit' => null,
    'enctype' => null,
])

@php
    $maxWidthClass = match ($size) {
        '4xl' => 'max-w-4xl',
        '6xl' => 'max-w-6xl',
        'xl' => 'max-w-xl',
        'md' => 'max-w-md',
        default => 'max-w-lg',
    };
@endphp

<div id="{{ $id }}"
    class="hidden fixed inset-0 z-50 bg-surface-overlay items-center justify-center px-4 text-base">
    <div
        class="bg-surface-base rounded-xl shadow-lg w-full {{ $maxWidthClass }} p-6 relative max-h-[90vh] overflow-y-auto">
        <h2 class="text-lg font-semibold text-text-primary mb-4">{{ $title }}</h2>

        @if ($confirmDelete || (strtoupper($method) === 'DELETE' && $onConfirm))
            {{-- Delete Confirmation mode - tampilan warning dengan icon merah --}}
            <div class="text-center space-y-4">
                <svg class="mx-auto mb-4 text-error w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <h3 class="text-lg font-normal text-text-label">
                    {{ $slot }}
                </h3>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button type="button" class="bg-button-cancel px-4 py-2 rounded hover:bg-button-cancel-hover"
                    onclick="closeModal('{{ $id }}')">
                    Batal
                </button>

                <button type="button" id="confirm-btn-{{ $id }}" onclick="{{ $onConfirm }}"
                    class="bg-error hover:bg-error text-white px-4 py-2 rounded">
                    {{ $buttonText }}
                </button>
            </div>
        @elseif ($readonly)
            {{-- Readonly mode - tidak ada form, hanya konten --}}
            <div class="space-y-4">
                {{ $slot }}
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" class="bg-button-cancel px-4 py-2 rounded hover:bg-button-cancel-hover"
                    onclick="closeModal('{{ $id }}')">
                    Tutup
                </button>
            </div>
        @elseif ($onConfirm)
            {{-- Confirmation mode dengan custom callback (non-delete) --}}
            <div class="space-y-4">
                {{ $slot }}
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" class="bg-button-cancel px-4 py-2 rounded hover:bg-button-cancel-hover"
                    onclick="closeModal('{{ $id }}')">
                    Batal
                </button>

                <button type="button" id="confirm-btn-{{ $id }}" onclick="{{ $onConfirm }}"
                    class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded">
                    {{ $buttonText }}
                </button>
            </div>
        @else
            {{-- Normal form submission --}}
            <form action="{{ $action }}" method="POST" class="space-y-4"
                @if ($formId) id="{{ $formId }}" @endif
                @if ($onsubmit) onsubmit="{{ $onsubmit }}" @endif
                @if ($enctype) enctype="{{ $enctype }}" @endif>
                @csrf
                @if (in_array(strtoupper($method), ['PUT', 'PATCH', 'DELETE']))
                    @method($method)
                @endif

                {{-- Slot untuk isi form --}}
                {{ $slot }}

                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" class="bg-button-cancel px-4 py-2 rounded hover:bg-button-cancel-hover"
                        onclick="closeModal('{{ $id }}')">
                        Batal
                    </button>

                    {{-- Warna tombol menyesuaikan method atau id modal --}}
                    <button type="submit" id="submit-btn-{{ $id }}"
                        class="{{ strtoupper($method) === 'DELETE'
                            ? 'bg-error hover:bg-error'
                            : ($id === 'generateModal'
                                ? 'bg-success hover:bg-success'
                                : 'bg-primary hover:bg-primary-hover') }}
                            text-white px-4 py-2 rounded">
                        {{ $buttonText }}
                    </button>
                </div>
            </form>
        @endif

        {{-- Tombol X --}}
        <button type="button" onclick="closeModal('{{ $id }}')"
            class="absolute top-2 right-3 text-text-tertiary hover:text-text-label text-xl font-bold">
            &times;
        </button>
    </div>
</div>
