@extends('layouts.app')

{{-- Judul Halaman --}}
@section('title', 'PT Aghitsna Karya Indah - Manajemen User')

@section('content')
    {{-- Container Utama --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- Header Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Manajemen User</h1>

        {{-- Toolbar: Pencarian & Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('user-management.index') }}"
                class="w-full min-[1280px]:w-auto min-[1280px]:flex-1 flex flex-col min-[1280px]:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari nama atau email..." />
            </form>

            {{-- Tombol Aksi: Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 min-[1280px]:mt-0 w-full min-[1280px]:w-auto">
                <div class="flex flex-col min-[1280px]:flex-row gap-2 w-full min-[1280px]:w-auto">
                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah User --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah User" />
                </div>
            </div>
        </div>

        {{-- Tabel Data User --}}
        @include('components.user-management.table', ['users' => $users])
    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$users" />

    {{-- Modal Tambah User --}}
    @include('components.user-management.add-modal')

    {{-- Modal Edit User (satu modal per user) --}}
    @foreach ($users as $user)
        @include('components.user-management.edit-modal', ['user' => $user])
    @endforeach

    {{-- Modal Konfirmasi Hapus Massal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus user yang dipilih?
    </x-modal>

    {{-- JavaScript Modular --}}
    @push('scripts')
        @vite('resources/js/pages/user-management/index.js')
    @endpush
@endsection
