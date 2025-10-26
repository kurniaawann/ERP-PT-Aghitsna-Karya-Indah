@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Barang')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-gray-700 mb-4">Data Barang</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <!-- Form Pencarian -->
            <form method="GET" action="{{ route('item.index') }}" class="w-full md:w-auto md:max-w-md md:flex-1">
                <label for="search-input" class="sr-only">Cari Barang</label>

                <div class="flex items-center gap-2">
                    <!-- Input dengan ikon -->
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 20 20" aria-hidden="true">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                            </svg>
                        </span>

                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari barang..."
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 pl-10 text-sm text-gray-900 
                       focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light" />
                    </div>

                    <!-- Tombol Cari -->
                    <button type="submit"
                        class="rounded-lg bg-btn-search hover:bg-btn-search-hover px-4 md:px-6 py-3.5 text-sm font-medium text-white 
                   focus:outline-none focus:ring-4 focus:ring-primary-light whitespace-nowrap transition-colors duration-200">
                        Cari
                    </button>
                </div>
            </form>


            <!-- Aksi di Kanan -->
            <div class="flex items-center gap-3 mt-2 sm:mt-0 flex-col sm:flex-row w-full sm:w-auto">
                <!-- Dropdown Format Print -->
                <div class="relative w-full sm:w-auto">
                    <select id="print-format"
                        class="block w-full appearance-none rounded-lg border border-gray-300 bg-white px-4 py-2 pr-8 text-sm text-gray-700 shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-light transition-all duration-200">
                        <option selected disabled hidden>Pilih Format Print</option>
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel</option>
                    </select>

                    <!-- Ikon panah ``dropdown`` -->
                    <span class="absolute inset-y-0 right-3 flex items-center text-gray-500 pointer-events-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </span>
                </div>

                <div class="flex gap-2 w-full sm:w-auto">
                    <!-- Tombol Hapus -->
                    <button type="button" onclick="openModal('deleteModal')"
                        class="flex items-center justify-center gap-2 bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-1.5 rounded-lg transition-colors duration-200 flex-1 sm:flex-initial">
                        <i class="fa-solid fa-trash w-4 h-4"></i>
                        Hapus
                    </button>

                    <!-- Tombol Tambah (kanan dari tombol hapus) -->
                    <button type="button" onclick="openModal('addModal')"
                        class="flex items-center justify-center gap-2 rounded-lg bg-btn-add hover:bg-btn-add-hover px-4 py-2 text-sm font-medium text-white focus:outline-none focus:ring-4 focus:ring-success-light flex-1 sm:flex-initial transition-colors duration-200">
                        <i class="fa-solid fa-plus"></i>
                        Tambah Data
                    </button>
                </div>

            </div>
        </div>

        {{-- Form Hapus Terpilih --}}
        <form id="deleteForm" method="POST" action="{{ route('items.destroySelected') }}">
            @csrf
            @method('DELETE')
            <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <div class="inline-block min-w-full align-middle">
                    <div class="border-2 border-gray-300 rounded-xl overflow-hidden shadow-sm">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th class=" p-2 text-center"><input type="checkbox" id="selectAll"></th>
                                    <th class="p-2 text-left">ID Barang</th>
                                    <th class="p-2 text-left">Nama Barang</th>
                                    <th class="p-2 text-left">Jumlah</th>
                                    <th class="p-2 text-center">Harga Modal</th>
                                    <th class="p-2 text-center">Harga Jual</th>
                                    <th class="p-2 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="p-2 text-center">
                                            <input type="checkbox" name="selected_items[]" value="{{ $item->id_item }}"
                                                class="w-4 h-4 accent-primary cursor-pointer">
                                        </td>

                                        <td class="p-2">{{ $item->id_item }}</td>
                                        <td class="p-2">{{ $item->name_item }}</td>
                                        <td class="p-2 text-center">{{ $item->quantity }}</td>

                                        {{-- Harga Modal --}}
                                        <td class="p-2 text-right">
                                            {{ 'Rp ' . number_format($item->capital_price, 0, ',', '.') }}
                                        </td>

                                        {{-- Harga Jual --}}
                                        <td class="p-2 text-right">
                                            {{ 'Rp ' . number_format($item->selling_price, 0, ',', '.') }}
                                        </td>

                                        {{-- Aksi --}}
                                        <td class="p-2 text-center flex justify-center gap-2">
                                            {{-- Tombol Edit --}}
                                            <button type="button" onclick="openModal('editModal-{{ $item->id_item }}')"
                                                class="flex items-center gap-2 bg-btn-edit hover:bg-btn-edit-hover text-white px-3 py-1 rounded-lg transition-colors duration-200">
                                                <i class="fa-solid fa-pen w-4 h-4"></i>
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center p-4 text-gray-500">Data tidak ditemukan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- End border luar table -->
                </div>
            </div>

        </form>

        {{-- Modal Edit untuk setiap item --}}
        @foreach ($items as $item)
            <x-modal id="editModal-{{ $item->id_item }}" title="Edit Barang"
                action="{{ route('item.update', $item->id_item) }}" method="PUT" buttonText="Update">

                <div class="mb-3">
                    <label class="block text-gray-700 mb-1">Nama Barang <span class="text-error">*</span></label>
                    <input type="text" name="name_item" value="{{ $item->name_item }}" class="w-full border rounded p-2"
                        required>
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700 mb-1">Jumlah</label>
                    <input type="number" name="quantity" value="{{ $item->quantity }}"
                        class="w-full border rounded p-2" required min="0">
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700 mb-1">Harga Modal</label>
                    <input type="number" name="capital_price" value="{{ $item->capital_price ?? 0 }}"
                        class="w-full border rounded p-2" required min="0">
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700 mb-1">Harga Jual</label>
                    <input type="number" name="selling_price" value="{{ $item->selling_price ?? 0 }}"
                        class="w-full border rounded p-2" required min="0">
                </div>
            </x-modal>
        @endforeach

        <div class="flex mt-4 justify-center">
            <div class="flex items-center gap-3 bg-white border border-gray-300 rounded-lg px-4 py-2 shadow-sm">

                {{-- Tombol Sebelumnya --}}
                <a href="{{ $items->appends(request()->query())->previousPageUrl() }}"
                    class="flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors duration-200
                    {{ $items->onFirstPage() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:border-primary' }}">
                    &lt;
                </a>

                {{-- Info Halaman --}}
                <span class="text-sm font-medium text-gray-700">
                    {{ $items->currentPage() }}
                    <span class="text-gray-400">/</span>
                    {{ $items->lastPage() }}
                </span>

                {{-- Tombol Berikutnya --}}
                <a href="{{ $items->appends(request()->query())->nextPageUrl() }}"
                    class="flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors duration-200
                    {{ !$items->hasMorePages() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:border-primary' }}">
                    &gt;
                </a>
            </div>
        </div>


    </div>


    {{-- Modal Tambah --}}
    <x-modal id="addModal" title="Tambah Barang" action="{{ route('item.store') }}" method="POST"
        buttonText="Simpan">

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Nama Barang <span class="text-error">*</span></label>
            <input type="text" name="name_item" class="w-full border rounded p-2" required>
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Jumlah</label>
            <input type="number" name="quantity" value="0" class="w-full border rounded p-2" required
                min="0">
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Harga Modal</label>
            <input type="number" name="capital_price" value="0" class="w-full border rounded p-2" required
                min="0">
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Harga Jual</label>
            <input type="number" name="selling_price" value="0" class="w-full border rounded p-2" required
                min="0">
        </div>
    </x-modal>


    {{-- Modal Hapus --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    <script>
        // Function untuk submit form delete
        function submitDeleteForm() {
            const form = document.getElementById('deleteForm');
            const checkboxes = form.querySelectorAll('input[name="selected_items[]"]:checked');
            form.submit();
        }

        // Select All Checkbox functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const itemCheckboxes = document.querySelectorAll('input[name="selected_items[]"]');

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    itemCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }

            // Handle Print Format Dropdown
            const printFormatSelect = document.getElementById('print-format');
            if (printFormatSelect) {
                printFormatSelect.addEventListener('change', function() {
                    const format = this.value;
                    if (format === 'pdf') {
                        window.location.href = "{{ route('item.export.pdf') }}";
                    } else if (format === 'excel') {
                        window.location.href = "{{ route('item.export.excel') }}";
                    }
                    // Reset dropdown setelah redirect
                    setTimeout(() => {
                        this.selectedIndex = 0;
                    }, 100);
                });
            }
        });
    </script>


@endsection
