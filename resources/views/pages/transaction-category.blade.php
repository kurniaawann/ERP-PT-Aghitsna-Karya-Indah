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

        {{-- Table Component --}}
        @include('components.transaction-category.table')

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
    @include('components.transaction-category.add-modal')

    {{-- Modal Edit untuk setiap kategori --}}
    @foreach ($categories as $category)
        @include('components.transaction-category.edit-modal', ['category' => $category])
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()" buttonText="Hapus">
        <p class="text-gray-700 mb-4">Apakah Anda yakin ingin menghapus kategori yang dipilih?</p>
        <p class="text-sm text-error">
            <i class="fa-solid fa-exclamation-triangle"></i> Kategori yang sedang digunakan tidak akan dihapus.
        </p>
    </x-modal>

    @include('partials.transaction-category.transaction-category-scripts')
@endsection
