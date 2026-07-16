{{-- ═══════════════════════════════════════════════════════════════════════
     Kasbon Index Page
     Main page for managing cash advances (kasbon).
     Displays filtered/searched kasbon list with add, edit, and bulk delete.
     ═══════════════════════════════════════════════════════════════════════ --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Kasbon')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        {{-- Page Header --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Kasbon</h1>

        {{-- Search, Filter & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Filter & Search Form --}}
            <form method="GET" action="{{ route('kasbon.index') }}" id="filterForm"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">

                <x-filters.month-filter :value="request('month')" onchange="document.getElementById('filterForm').submit()" />
                <x-filters.year-filter :value="request('year')" onchange="document.getElementById('filterForm').submit()" />

                {{-- Status Filter --}}
                <select name="status" onchange="document.getElementById('filterForm').submit()"
                    class="border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Belum Dipotong</option>
                    <option value="deducted" {{ request('status') == 'deducted' ? 'selected' : '' }}>Sudah Dipotong</option>
                </select>

                {{-- Type Filter --}}
                <select name="type" onchange="document.getElementById('filterForm').submit()"
                    class="border border-border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                    <option value="">Semua Jenis</option>
                    <option value="personal" {{ request('type') == 'personal' ? 'selected' : '' }}>Per Orang</option>
                    <option value="team" {{ request('type') == 'team' ? 'selected' : '' }}>Per Tim</option>
                </select>

                <x-filters.search-input :value="request('search')" placeholder="Cari kasbon..." />

                {{-- Reset Filter Button --}}
                @if (request()->hasAny(['search', 'month', 'year', 'status', 'type']))
                    <a href="{{ route('kasbon.index') }}"
                        class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                        <i class="fa-solid fa-rotate-left mr-2"></i>
                        Reset
                    </a>
                @endif
            </form>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Kasbon" />
                </div>
            </div>
        </div>

        {{-- Data Table --}}
        @include('components.human-resource.cash-advances.table', ['kasbons' => $kasbons])
    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$kasbons" />

    {{-- Add Kasbon Modal --}}
    @include('components.human-resource.cash-advances.add-modal', ['employees' => $employees])

    {{-- Edit Kasbon Modals (one per row) --}}
    @foreach ($kasbons as $kasbon)
        @include('components.human-resource.cash-advances.edit-modal', [
            'kasbon' => $kasbon,
            'employees' => $employees,
            'divisions' => $divisions,
        ])
    @endforeach

    {{-- Bulk Delete Confirmation Modal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data kasbon yang dipilih? <br>
        <span class="text-error text-sm">Catatan: Kasbon yang sudah dipotong tidak bisa dihapus.</span>
    </x-modal>

    {{-- Page container with data attributes for JavaScript module --}}
    <div id="kasbon-page"
        data-csrf-token="{{ csrf_token() }}"
        data-url-check-max="{{ route('kasbon.check-max') }}"
        data-url-get-weeks="{{ route('payroll.get-weeks') }}"
        class="hidden">
    </div>

    {{-- JavaScript Module (loaded via Vite) --}}
    @vite('resources/js/pages/human-resource/cash-advances/index.js')
@endsection
