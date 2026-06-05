@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Kategori Transaksi')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Kategori Transaksi</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <!-- Form Pencarian dan Filter -->
            <form method="GET" action="{{ route('transaction-category.index') }}" id="filterForm"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">

                <!-- Filter Tipe -->
                <x-filters.select-filter name="type" :value="request('type')" :options="collect([
                    (object) ['id' => 'INCOME', 'name' => 'Pemasukan'],
                    (object) ['id' => 'EXPENSE', 'name' => 'Pengeluaran'],
                ])" placeholder="Semua Tipe"
                    :autoSubmit="true" />

                <!-- Search Input -->
                <x-filters.search-input :value="request('search')" placeholder="Cari kategori..." />
            </form>

            <!-- Aksi di Kanan -->
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <button type="button" id="delete-button" onclick="checkAndDelete()" disabled
                        class="w-full sm:w-auto flex items-center justify-center gap-2 bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-btn-delete">
                        <i class="fa-solid fa-trash w-4 h-4"></i>
                        <span>Hapus</span>
                    </button>

                    <x-buttons.add-button modalId="addModal" text="Tambah Kategori" />
                </div>
            </div>
        </div>

        {{-- Table Component --}}
        @include('components.report.transaction-category.table')

        {{-- Pagination --}}
        <x-pagination :paginator="$categories" />
    </div>

    {{-- Modal Tambah --}}
    @include('components.report.transaction-category.add-modal')

    {{-- Modal Edit untuk setiap kategori --}}
    @foreach ($categories as $category)
        @include('components.report.transaction-category.edit-modal', ['category' => $category])
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()" buttonText="Hapus">
        <p class="text-text-primary mb-4">Apakah Anda yakin ingin menghapus kategori yang dipilih?</p>
        <p class="text-sm text-error">
            <i class="fa-solid fa-exclamation-triangle"></i> Kategori yang sedang digunakan tidak akan dihapus.
        </p>
    </x-modal>

    {{-- Modal Peringatan Kategori Digunakan --}}
    <div id="warningUsedModal"
        class="fixed inset-0 bg-surface-hover bg-opacity-50 hidden items-center justify-center p-4 z-50 transition-opacity duration-300">
        <div
            class="bg-surface-base rounded-2xl shadow-2xl max-w-md w-full transform transition-all duration-300 scale-95 opacity-0">
            {{-- Header --}}
            <div class="bg-error p-6 rounded-t-2xl">
                <div class="flex items-center gap-3">
                    <div class="bg-surface-base bg-opacity-20 p-3 rounded-full">
                        <i class="fa-solid fa-exclamation-circle text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Kategori Sedang Digunakan</h3>
                </div>
            </div>

            {{-- Body --}}
            <div class="p-6">
                <p class="text-text-primary mb-4">
                    Kategori berikut sedang digunakan dalam transaksi dan <strong>tidak dapat dihapus</strong>:
                </p>

                <div class="bg-error-light border-2 border-error rounded-lg p-4 mb-4 max-h-48 overflow-y-auto">
                    <ul id="usedCategoriesList" class="list-disc list-inside space-y-1 text-sm text-error">
                        {{-- Will be populated by JavaScript --}}
                    </ul>
                </div>

                <p class="text-sm text-text-label">
                    <i class="fa-solid fa-info-circle text-primary"></i> Untuk menghapus kategori ini, pastikan tidak
                    ada transaksi yang menggunakannya.
                </p>
            </div>

            {{-- Footer --}}
            <div class="bg-surface-secondary px-6 py-4 rounded-b-2xl flex justify-end">
                <button type="button" onclick="closeWarningModal()"
                    class="px-6 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-lg font-medium transition-colors duration-200 focus:outline-none focus:ring-4 focus:ring-primary-light">
                    <i class="fa-solid fa-check mr-2"></i> Mengerti
                </button>
            </div>
        </div>
    </div>

    @include('partials.report.transaction-category-scripts')
@endsection
