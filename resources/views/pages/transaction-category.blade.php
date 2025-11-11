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
                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Kategori" />
                </div>
            </div>
        </div>

        {{-- Table Component --}}
        @include('components.transaction-category.table')

        {{-- Pagination --}}
        <x-pagination :paginator="$categories" />
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
