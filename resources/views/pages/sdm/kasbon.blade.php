@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Kasbon')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Kasbon</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian dan Filter --}}
            <form method="GET" action="{{ route('kasbon.index') }}" id="filterForm"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">

                {{-- Filter Bulan --}}
                <x-filters.month-filter :value="request('month')" onchange="document.getElementById('filterForm').submit()" />

                {{-- Filter Tahun --}}
                <x-filters.year-filter :value="request('year')" onchange="document.getElementById('filterForm').submit()" />

                {{-- Filter Status --}}
                <select name="status" onchange="document.getElementById('filterForm').submit()"
                    class="border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Belum Dipotong</option>
                    <option value="deducted" {{ request('status') == 'deducted' ? 'selected' : '' }}>Sudah Dipotong</option>
                </select>

                {{-- Filter Jenis --}}
                <select name="type" onchange="document.getElementById('filterForm').submit()"
                    class="border border-border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                    <option value="">Semua Jenis</option>
                    <option value="personal" {{ request('type') == 'personal' ? 'selected' : '' }}>Per Orang</option>
                    <option value="team" {{ request('type') == 'team' ? 'selected' : '' }}>Per Tim</option>
                </select>

                {{-- Search Input (expanded like Lembur) --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari kasbon..." />

                {{-- Tombol Reset Filter --}}
                @if (request()->hasAny(['search', 'month', 'year', 'status', 'type']))
                    <a href="{{ route('kasbon.index') }}"
                        class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                        <i class="fa-solid fa-rotate-left mr-2"></i>
                        Reset
                    </a>
                @endif
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Kasbon" />
                </div>
            </div>
        </div>

        {{-- Table Component --}}
        @include('components.sdm.kasbon.table', ['kasbons' => $kasbons])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$kasbons" />

    {{-- Modal Tambah --}}
    @include('components.sdm.kasbon.add-modal', ['employees' => $employees])

    {{-- Modal Edit untuk setiap kasbon --}}
    @foreach ($kasbons as $kasbon)
        @include('components.sdm.kasbon.edit-modal', [
            'kasbon' => $kasbon,
            'employees' => $employees,
            'divisions' => $divisions,
        ])
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data kasbon yang dipilih? <br>
        <span class="text-error text-sm">Catatan: Kasbon yang sudah dipotong tidak bisa dihapus.</span>
    </x-modal>

    {{-- JavaScript --}}
    @include('partials.sdm.kasbon-scripts')
@endsection
