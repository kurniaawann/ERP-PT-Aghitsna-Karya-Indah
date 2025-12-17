@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Absensi')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Absensi</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('attendance.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari absensi..." />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Data" />
                </div>
            </div>
        </div>

        {{-- Table Component --}}
        @include('components.sdm.attendance.table', ['attendances' => $attendances])

        {{-- Pagination --}}
        <x-pagination :paginator="$attendances" />
    </div>

    {{-- Modal Tambah --}}
    @include('components.sdm.attendance.add-modal', ['employees' => $employees])

    {{-- Modal Edit untuk setiap attendance --}}
    @foreach ($attendances as $attendance)
        @include('components.sdm.attendance.edit-modal', ['attendance' => $attendance])
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- JavaScript --}}
    @include('partials.sdm.attendance.attendance-scripts')
@endsection
