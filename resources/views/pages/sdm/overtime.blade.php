@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Lembur')

@section('content')
    {{-- Page Header --}}
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Lembur</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Search Form --}}
            <form method="GET" action="{{ route('overtime.index') }}"
                class="w-full min-[1280px]:w-auto min-[1280px]:flex-1 flex flex-col min-[1280px]:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari lembur..." />
            </form>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-2 mt-2 min-[1280px]:mt-0 w-full min-[1280px]:w-auto">
                <div class="flex flex-col min-[1280px]:flex-row gap-2 w-full min-[1280px]:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Data" />
                </div>
            </div>
        </div>

        {{-- Overtime Table Component --}}
        @include('components.sdm.overtime.table', ['overtimes' => $overtimes])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$overtimes" />

    {{-- Add Modal --}}
    @include('components.sdm.overtime.add-modal', ['employees' => $employees])

    {{-- Edit Modals (one per overtime record on current page) --}}
    @foreach ($overtimes as $overtime)
        @include('components.sdm.overtime.edit-modal', ['overtime' => $overtime])
    @endforeach

    {{-- Bulk Delete Confirmation Modal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- Pass existing attendance data to JavaScript for client-side duplicate validation --}}
    <script>
        window.overtimeExistingAttendance = @json($existingAttendance ?? []);
    </script>

    {{-- JavaScript --}}
    @vite('resources/js/pages/sdm/overtime/index.js')
@endsection
