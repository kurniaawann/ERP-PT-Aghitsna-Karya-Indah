@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Manajemen User')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Manajemen User</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('user-management.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari nama atau email..." />
            </form>

            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" />
                    <x-buttons.add-button modalId="addModal" text="Tambah User" />
                </div>
            </div>
        </div>

        @include('components.user-management.table', ['users' => $users])

        <x-pagination :paginator="$users" />
    </div>

    @include('components.user-management.add-modal')

    @foreach ($users as $user)
        @include('components.user-management.edit-modal', ['user' => $user])
    @endforeach

    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus user yang dipilih?
    </x-modal>

    @include('partials.user-management.user-scripts')
@endsection
