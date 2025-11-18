@props([
    'id',
    'title' => 'Modal Title',
    'action' => '#',
    'method' => 'POST',
    'buttonText' => 'Simpan',
    'readonly' => false,
    'confirmDelete' => false,
    'onConfirm' => null,
])

<div id="{{ $id }}" class="hidden fixed inset-0 z-50 bg-gray-900/60 items-center justify-center px-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6 relative max-h-[90vh] overflow-y-auto">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">{{ $title }}</h2>

        @if ($confirmDelete || (strtoupper($method) === 'DELETE' && $onConfirm))
            {{-- Delete Confirmation mode - tampilan warning dengan icon merah --}}
            <div class="text-center space-y-4">
                <svg class="mx-auto mb-4 text-red-600 w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <h3 class="text-lg font-normal text-gray-600">
                    {{ $slot }}
                </h3>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button type="button" class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300"
                    onclick="closeModal('{{ $id }}')">
                    Batal
                </button>

                <button type="button" onclick="{{ $onConfirm }}"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                    {{ $buttonText }}
                </button>
            </div>
        @elseif ($readonly)
            {{-- Readonly mode - tidak ada form, hanya konten --}}
            <div class="space-y-4">
                {{ $slot }}
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300"
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
                <button type="button" class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300"
                    onclick="closeModal('{{ $id }}')">
                    Batal
                </button>

                <button type="button" onclick="{{ $onConfirm }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    {{ $buttonText }}
                </button>
            </div>
        @else
            {{-- Normal form submission --}}
            <form action="{{ $action }}" method="POST" class="space-y-4">
                @csrf
                @if (in_array(strtoupper($method), ['PUT', 'PATCH', 'DELETE']))
                    @method($method)
                @endif

                {{-- Slot untuk isi form --}}
                {{ $slot }}

                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300"
                        onclick="closeModal('{{ $id }}')">
                        Batal
                    </button>

                    {{-- Warna tombol menyesuaikan method --}}
                    <button type="submit" id="submit-btn-{{ $id }}"
                        class="{{ strtoupper($method) === 'DELETE' ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700' }}
                            text-white px-4 py-2 rounded">
                        {{ $buttonText }}
                    </button>
                </div>
            </form>
        @endif

        {{-- Tombol X --}}
        <button type="button" onclick="closeModal('{{ $id }}')"
            class="absolute top-2 right-3 text-gray-400 hover:text-gray-600 text-xl font-bold">
            &times;
        </button>
    </div>
</div>
