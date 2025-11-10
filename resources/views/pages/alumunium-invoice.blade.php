@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Invoice Alumunium')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-gray-700 mb-4">Invoice Alumunium</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('alumunium-invoice.index') }}"
                class="w-full md:w-auto md:max-w-md md:flex-1">
                <label for="search-input" class="sr-only">Cari Invoice</label>

                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 20 20" aria-hidden="true">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                            </svg>
                        </span>

                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Cari no invoice atau kepada..."
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 pl-10 text-sm text-gray-900 
                       focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light" />
                    </div>

                    <button type="submit"
                        class="rounded-lg bg-btn-search hover:bg-btn-search-hover px-4 md:px-6 py-3.5 text-sm font-medium text-white 
                   focus:outline-none focus:ring-4 focus:ring-primary-light whitespace-nowrap transition-colors duration-200">
                        Cari
                    </button>
                </div>
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-3 mt-2 sm:mt-0 flex-col sm:flex-row w-full sm:w-auto">
                <div class="flex gap-2 w-full sm:w-auto">
                    <button type="button" onclick="openModal('deleteModal')"
                        class="flex items-center justify-center gap-2 bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-1.5 rounded-lg transition-colors duration-200 flex-1 sm:flex-initial">
                        <i class="fa-solid fa-trash w-4 h-4"></i>
                        Hapus
                    </button>

                    <button type="button" onclick="openModal('addModal')"
                        class="flex items-center justify-center gap-2 rounded-lg bg-btn-add hover:bg-btn-add-hover px-4 py-2 text-sm font-medium text-white focus:outline-none focus:ring-4 focus:ring-success-light flex-1 sm:flex-initial transition-colors duration-200">
                        <i class="fa-solid fa-plus"></i>
                        Tambah Invoice
                    </button>
                </div>
            </div>
        </div>

        {{-- Table Component --}}
        @include('components.invoice.alumunium.table', ['invoices' => $invoices])

        {{-- Pagination --}}
        <div class="flex mt-4 justify-center">
            <div class="flex items-center gap-3 bg-white border border-gray-300 rounded-lg px-4 py-2 shadow-sm">
                <a href="{{ $invoices->appends(request()->query())->previousPageUrl() }}"
                    class="flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors duration-200
                    {{ $invoices->onFirstPage() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:border-primary' }}">
                    &lt;
                </a>

                <span class="text-sm font-medium text-gray-700">
                    {{ $invoices->currentPage() }}
                    <span class="text-gray-400">/</span>
                    {{ $invoices->lastPage() }}
                </span>

                <a href="{{ $invoices->appends(request()->query())->nextPageUrl() }}"
                    class="flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors duration-200
                    {{ !$invoices->hasMorePages() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:border-primary' }}">
                    &gt;
                </a>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Invoice --}}
    @include('components.invoice.alumunium.add-modal')

    {{-- Modal Edit & Detail untuk setiap invoice --}}
    @foreach ($invoices as $invoice)
        @include('components.invoice.alumunium.edit-modal', ['invoice' => $invoice])
        @include('components.invoice.alumunium.detail-modal', ['invoice' => $invoice])
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- JavaScript --}}
    @include('partials.invoice.alumunium-scripts')
@endsection
