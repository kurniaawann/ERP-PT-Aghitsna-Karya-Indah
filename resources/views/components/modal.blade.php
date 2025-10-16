@props(['id', 'title' => 'Modal Title', 'action' => '#', 'method' => 'POST', 'buttonText' => 'Simpan'])

<div id="{{ $id }}" class="hidden fixed inset-0 z-50 bg-gray-900/60 items-center justify-center px-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6 relative">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">{{ $title }}</h2>

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
                <button type="submit"
                    class="{{ strtoupper($method) === 'DELETE' ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700' }}
                        text-white px-4 py-2 rounded">
                    {{ $buttonText }}
                </button>
            </div>
        </form>

        {{-- Tombol X --}}
        <button type="button" onclick="closeModal('{{ $id }}')"
            class="absolute top-2 right-3 text-gray-400 hover:text-gray-600 text-xl font-bold">
            &times;
        </button>
    </div>
</div>
