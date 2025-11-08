@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Kategori Transaksi')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-gray-700 mb-4">Kategori Transaksi</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <!-- Form Pencarian dan Filter -->
            <form method="GET" action="{{ route('transaction-category.index') }}" id="filterForm"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">

                <!-- Filter Tipe -->
                <div class="w-full lg:w-auto">
                    <label for="type-select" class="sr-only">Pilih Tipe</label>
                    <select name="type" id="type-select"
                        class="block w-full lg:w-40 rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 
                               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light"
                        onchange="document.getElementById('filterForm').submit()">
                        <option value="">Semua Tipe</option>
                        <option value="INCOME" {{ request('type') == 'INCOME' ? 'selected' : '' }}>Pemasukan</option>
                        <option value="EXPENSE" {{ request('type') == 'EXPENSE' ? 'selected' : '' }}>Pengeluaran</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-1">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 20 20" aria-hidden="true">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                            </svg>
                        </span>

                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kategori..."
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 pl-10 text-sm text-gray-900 
                                   focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light" />
                    </div>

                    <button type="submit"
                        class="w-full sm:w-auto rounded-lg bg-btn-search hover:bg-btn-search-hover px-4 lg:px-6 py-3.5 text-sm font-medium text-white 
                               focus:outline-none focus:ring-4 focus:ring-primary-light whitespace-nowrap transition-colors duration-200">
                        Cari
                    </button>
                </div>
            </form>

            <!-- Aksi di Kanan -->
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <!-- Tombol Hapus -->
                    <button type="button" onclick="openModal('deleteModal')"
                        class="w-full sm:w-auto flex items-center justify-center gap-2 bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-2 lg:py-1.5 rounded-lg transition-colors duration-200 text-sm font-medium">
                        <i class="fa-solid fa-trash w-4 h-4"></i>
                        <span>Hapus</span>
                    </button>

                    <!-- Tombol Tambah -->
                    <button type="button" onclick="openModal('addModal')"
                        class="w-full sm:w-auto flex items-center justify-center gap-2 rounded-lg bg-btn-add hover:bg-btn-add-hover px-4 py-2 text-sm font-medium text-white focus:outline-none focus:ring-4 focus:ring-success-light transition-colors duration-200">
                        <i class="fa-solid fa-plus"></i>
                        <span>Tambah Kategori</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Form Hapus Terpilih --}}
        <form id="deleteForm" method="POST" action="{{ route('transaction-category.destroySelected') }}">
            @csrf
            @method('DELETE')
            <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <div class="inline-block min-w-full align-middle">
                    <div class="border-2 border-gray-300 rounded-xl overflow-hidden shadow-sm">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                                    <th class="p-2 text-center">No</th>
                                    <th class="p-2 text-left">Nama Kategori</th>
                                    <th class="p-2 text-left">Kode</th>
                                    <th class="p-2 text-center">Tipe</th>
                                    <th class="p-2 text-center">Urutan</th>
                                    <th class="p-2 text-center">Status</th>
                                    <th class="p-2 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($categories as $index => $category)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="p-2 text-center">
                                            <input type="checkbox" name="selected_categories[]" value="{{ $category->id }}"
                                                class="category-checkbox w-4 h-4 accent-primary cursor-pointer">
                                        </td>
                                        <td class="p-2 text-center font-medium text-primary">
                                            {{ $categories->firstItem() + $index }}
                                        </td>
                                        <td class="p-2 text-gray-700 font-medium">
                                            {{ $category->name }}
                                        </td>
                                        <td class="p-2">
                                            <span
                                                class="inline-block px-2 py-1 text-xs font-mono bg-gray-100 text-gray-700 rounded">
                                                {{ $category->code }}
                                            </span>
                                        </td>
                                        <td class="p-2 text-center">
                                            @if ($category->type == 'INCOME')
                                                <span
                                                    class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-lg bg-success-light text-success gap-1">
                                                    <i class="fa-solid fa-arrow-down"></i>
                                                    Pemasukan
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-lg bg-error-light text-error gap-1">
                                                    <i class="fa-solid fa-arrow-up"></i>
                                                    Pengeluaran
                                                </span>
                                            @endif
                                        </td>
                                        <td class="p-2 text-center text-gray-600">
                                            {{ $category->sort_order }}
                                        </td>
                                        <td class="p-2 text-center">
                                            <form action="{{ route('transaction-category.toggleStatus', $category->id) }}"
                                                method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-lg gap-1 transition-colors duration-200
                                                    {{ $category->is_active ? 'bg-success-light text-success hover:bg-success hover:text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-400 hover:text-white' }}">
                                                    <i
                                                        class="fa-solid {{ $category->is_active ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                                                    {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="p-2 text-center">
                                            <button type="button" onclick="openModal('editModal-{{ $category->id }}')"
                                                class="flex items-center justify-center gap-2 bg-btn-edit hover:bg-btn-edit-hover text-white px-3 py-1 rounded-lg transition-colors duration-200 mx-auto">
                                                <i class="fa-solid fa-pen w-4 h-4"></i>
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center p-4 text-gray-500">Data tidak ditemukan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>

        {{-- Pagination --}}
        <div class="flex mt-4 justify-center">
            <div class="flex items-center gap-3 bg-white border border-gray-300 rounded-lg px-4 py-2 shadow-sm">
                <a href="{{ $categories->appends(request()->query())->previousPageUrl() }}"
                    class="flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors duration-200
                    {{ $categories->onFirstPage() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:border-primary' }}">
                    &lt;
                </a>

                <span class="text-sm font-medium text-gray-700">
                    {{ $categories->currentPage() }}
                    <span class="text-gray-400">/</span>
                    {{ $categories->lastPage() }}
                </span>

                <a href="{{ $categories->appends(request()->query())->nextPageUrl() }}"
                    class="flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors duration-200
                    {{ !$categories->hasMorePages() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:border-primary' }}">
                    &gt;
                </a>
            </div>
        </div>
    </div>

    {{-- Modal Tambah --}}
    <x-modal id="addModal" title="Tambah Kategori Transaksi" action="{{ route('transaction-category.store') }}"
        method="POST" buttonText="Simpan">

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Nama Kategori <span class="text-error">*</span></label>
            <input type="text" name="name" class="w-full border rounded p-2" placeholder="Contoh: Belanja ATK"
                required>
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Kode <span class="text-error">*</span></label>
            <input type="text" name="code" class="w-full border rounded p-2" placeholder="Contoh: BELANJA_ATK"
                required>
            <p class="text-xs text-gray-500 mt-1">Kode harus unik dan menggunakan format: HURUF_BESAR_UNDERSCORE</p>
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Tipe <span class="text-error">*</span></label>
            <select name="type" class="w-full border rounded p-2" required>
                <option value="">-- Pilih Tipe --</option>
                <option value="INCOME">Pemasukan</option>
                <option value="EXPENSE">Pengeluaran</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Urutan (Sort Order) <span class="text-error">*</span></label>
            <input type="number" name="sort_order" class="w-full border rounded p-2"
                placeholder="Kosongkan untuk urutan terakhir" min="1">
            <p class="text-xs text-gray-500 mt-1">Semakin kecil angka, semakin atas urutannya. Kosongkan untuk urutan
                terakhir.</p>
        </div>
    </x-modal>

    {{-- Modal Edit untuk setiap kategori --}}
    @foreach ($categories as $category)
        <x-modal id="editModal-{{ $category->id }}" title="Edit Kategori Transaksi"
            action="{{ route('transaction-category.update', $category->id) }}" method="PUT" buttonText="Update">

            <div class="mb-3">
                <label class="block text-gray-700 mb-1">Nama Kategori <span class="text-error">*</span></label>
                <input type="text" name="name" value="{{ $category->name }}" class="w-full border rounded p-2"
                    required>
            </div>

            <div class="mb-3">
                <label class="block text-gray-700 mb-1">Kode <span class="text-error">*</span></label>
                <input type="text" name="code" value="{{ $category->code }}" class="w-full border rounded p-2"
                    required>
                <p class="text-xs text-gray-500 mt-1">Kode harus unik dan menggunakan format: HURUF_BESAR_UNDERSCORE</p>
            </div>

            <div class="mb-3">
                <label class="block text-gray-700 mb-1">Tipe <span class="text-error">*</span></label>
                <select name="type" class="w-full border rounded p-2" required>
                    <option value="">-- Pilih Tipe --</option>
                    <option value="INCOME" {{ $category->type == 'INCOME' ? 'selected' : '' }}>Pemasukan</option>
                    <option value="EXPENSE" {{ $category->type == 'EXPENSE' ? 'selected' : '' }}>Pengeluaran</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="block text-gray-700 mb-1">Urutan (Sort Order) <span class="text-error">*</span></label>
                <input type="number" name="sort_order" value="{{ $category->sort_order }}"
                    class="w-full border rounded p-2" min="1" required>
                <p class="text-xs text-gray-500 mt-1">Ubah urutan akan menggeser kategori lain secara otomatis</p>
            </div>
        </x-modal>
    @endforeach

    {{-- Modal Delete Confirmation --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" action="#" method="POST" buttonText="Hapus"
        buttonClass="bg-btn-delete hover:bg-btn-delete-hover">
        <p class="text-gray-700 mb-4">Apakah Anda yakin ingin menghapus kategori yang dipilih?</p>
        <p class="text-sm text-error">
            <i class="fa-solid fa-exclamation-triangle"></i> Kategori yang sedang digunakan tidak akan dihapus.
        </p>
    </x-modal>

    @push('scripts')
        <script>
            // Select All Checkbox
            document.getElementById('selectAll').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.category-checkbox');
                checkboxes.forEach(cb => cb.checked = this.checked);
            });

            // Handle Delete Modal
            function openModal(modalId) {
                if (modalId === 'deleteModal') {
                    const selectedCheckboxes = document.querySelectorAll('.category-checkbox:checked');
                    if (selectedCheckboxes.length === 0) {
                        alert('Pilih minimal satu kategori untuk dihapus!');
                        return;
                    }
                    const modal = document.getElementById(modalId);
                    const form = document.getElementById('deleteForm');
                    modal.querySelector('form').action = form.action;

                    // Copy selected checkboxes to modal form
                    const modalForm = modal.querySelector('form');
                    modalForm.innerHTML = '@csrf @method('DELETE')';
                    selectedCheckboxes.forEach(cb => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'selected_categories[]';
                        input.value = cb.value;
                        modalForm.appendChild(input);
                    });
                }

                document.getElementById(modalId).classList.remove('hidden');
            }

            function closeModal(modalId) {
                document.getElementById(modalId).classList.add('hidden');
            }

            // Close modal when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal-backdrop')) {
                    e.target.querySelector('.modal-container').closest('[id$="Modal"]').classList.add('hidden');
                }
            });
        </script>
    @endpush
@endsection
