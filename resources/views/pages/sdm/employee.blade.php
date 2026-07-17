@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Karyawan')

@section('content')
    {{-- Page Header --}}
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Karyawan</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Search Form --}}
            <form method="GET" action="{{ route('employee.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari karyawan..." />
            </form>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Data" />
                </div>
            </div>
        </div>

        {{-- Employee Table Component --}}
        @include('components.sdm.employee.table', ['employees' => $employees])
    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$employees" />

    {{-- Add Modal --}}
    @include('components.sdm.employee.add-modal')

    {{-- Edit Modals --}}
    @foreach ($employees as $employee)
        @include('components.sdm.employee.edit-modal', ['employee' => $employee])
    @endforeach

    {{-- Bulk Delete Confirmation Modal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- JavaScript --}}
    @vite('resources/js/pages/sdm/employee/index.js')
@endsection
