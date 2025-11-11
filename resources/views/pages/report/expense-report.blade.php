@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Laporan Pengeluaran')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-gray-700 mb-4">Laporan Pengeluaran</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <!-- Form Pencarian dan Filter -->
            <form method="GET" action="{{ route('expense-report.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">

                <!-- Filter Kategori -->
                <div class="w-full lg:w-auto">
                    <label for="category-select" class="sr-only">Pilih Kategori</label>
                    <select name="category" id="category-select"
                        class="block w-full lg:w-48 rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 
                               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Bulan -->
                <div class="w-full lg:w-auto">
                    <label for="month-select" class="sr-only">Pilih Bulan</label>
                    <select name="month" id="month-select"
                        class="block w-full lg:w-40 rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 
                               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
                        <option value="">Semua Bulan</option>
                        <option value="1" {{ request('month') == '1' ? 'selected' : '' }}>Januari</option>
                        <option value="2" {{ request('month') == '2' ? 'selected' : '' }}>Februari</option>
                        <option value="3" {{ request('month') == '3' ? 'selected' : '' }}>Maret</option>
                        <option value="4" {{ request('month') == '4' ? 'selected' : '' }}>April</option>
                        <option value="5" {{ request('month') == '5' ? 'selected' : '' }}>Mei</option>
                        <option value="6" {{ request('month') == '6' ? 'selected' : '' }}>Juni</option>
                        <option value="7" {{ request('month') == '7' ? 'selected' : '' }}>Juli</option>
                        <option value="8" {{ request('month') == '8' ? 'selected' : '' }}>Agustus</option>
                        <option value="9" {{ request('month') == '9' ? 'selected' : '' }}>September</option>
                        <option value="10" {{ request('month') == '10' ? 'selected' : '' }}>Oktober</option>
                        <option value="11" {{ request('month') == '11' ? 'selected' : '' }}>November</option>
                        <option value="12" {{ request('month') == '12' ? 'selected' : '' }}>Desember</option>
                    </select>
                </div>

                <!-- Filter Tahun -->
                <div class="w-full lg:w-auto">
                    <label for="year-select" class="sr-only">Pilih Tahun</label>
                    <select name="year" id="year-select"
                        class="block w-full lg:w-32 rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 
                               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
                        <option value="">Semua Tahun</option>
                        @for ($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                                {{ $y }}</option>
                        @endfor
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

                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Cari transaksi..."
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
                    <!-- Dropdown Print Laporan -->
                    <div class="relative inline-block text-left w-full sm:w-auto">
                        <button type="button" id="printDropdownButton"
                            class="w-full sm:w-auto flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-3 py-2 lg:py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium">
                            <i class="fa-solid fa-print w-4 h-4"></i>
                            <span>Print Laporan</span>
                            <i class="fa-solid fa-chevron-down text-xs ml-auto sm:ml-0"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="printDropdownMenu"
                            class="hidden absolute left-0 sm:right-0 sm:left-auto mt-2 w-full sm:w-48 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                            <div class="py-1" role="menu">
                                <a href="{{ route('expense-report.export.excel') }}?{{ http_build_query(array_filter(['search' => request('search'), 'category' => request('category'), 'month' => request('month'), 'year' => request('year')])) }}"
                                    class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150">
                                    <i class="fa-solid fa-file-excel text-success w-4"></i>
                                    <span>Export Excel</span>
                                </a>
                                <a href="{{ route('expense-report.export.pdf') }}?{{ http_build_query(array_filter(['search' => request('search'), 'category' => request('category'), 'month' => request('month'), 'year' => request('year')])) }}"
                                    class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150">
                                    <i class="fa-solid fa-file-pdf text-error w-4"></i>
                                    <span>Export PDF</span>
                                </a>
                            </div>
                        </div>
                    </div>

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
                        <span>Tambah Pengeluaran</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Table Component --}}
        @include('components.expense-report.table', [
            'expenseReports' => $expenseReports,
            'totals' => $totals,
        ])

        {{-- Pagination --}}
        <div class="flex mt-4 justify-center">
            <div class="flex items-center gap-3 bg-white border border-gray-300 rounded-lg px-4 py-2 shadow-sm">
                <a href="{{ $expenseReports->appends(request()->query())->previousPageUrl() }}"
                    class="flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors duration-200
                    {{ $expenseReports->onFirstPage() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:border-primary' }}">
                    &lt;
                </a>

                <span class="text-sm font-medium text-gray-700">
                    {{ $expenseReports->currentPage() }}
                    <span class="text-gray-400">/</span>
                    {{ $expenseReports->lastPage() }}
                </span>

                <a href="{{ $expenseReports->appends(request()->query())->nextPageUrl() }}"
                    class="flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors duration-200
                    {{ !$expenseReports->hasMorePages() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:border-primary' }}">
                    &gt;
                </a>
            </div>
        </div>
    </div>

    {{-- Modal Tambah --}}
    @include('components.expense-report.add-modal')

    {{-- Modal Edit untuk setiap expense (hanya yang manual) --}}
    @foreach ($expenseReports as $expense)
        @if (!$expense->isAutoGenerated())
            @include('components.expense-report.edit-modal', ['expense' => $expense])
        @endif
    @endforeach

    {{-- Modal Delete Confirmation --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        <p class="text-gray-700 mb-4">Apakah Anda yakin ingin menghapus data yang dipilih?</p>
        <p class="text-sm text-gray-500">
            <i class="fa-solid fa-info-circle"></i> Data yang auto-generated dari sales report tidak akan dihapus.
        </p>
    </x-modal>

    {{-- JavaScript --}}
    @include('partials.expense-report.expense-report-scripts')
@endsection
