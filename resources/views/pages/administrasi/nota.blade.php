@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Nota')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Nota</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('nota.administrasi.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari nota..." />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.print-dropdown-with-selected :pdfRoute="route('nota.administrasi.export.pdf')" :queryParams="['search' => request('search')]" />

                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Nota" />
                </div>
            </div>
        </div>

        {{-- Table Component --}}
        @include('components.administrasi.nota.table', ['notas' => $notas])

        {{-- Pagination --}}
        <x-pagination :paginator="$notas" />
    </div>

    {{-- Modal Tambah --}}
    @include('components.administrasi.nota.add-modal')

    {{-- Modal Edit untuk setiap nota --}}
    @foreach ($notas as $nota)
        @include('components.administrasi.nota.edit-modal', ['nota' => $nota])
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- JavaScript --}}
    @include('partials.administrasi.nota-scripts')
    @include('partials.shared.print-dropdown-script')
@endsection
