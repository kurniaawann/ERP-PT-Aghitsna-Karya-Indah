{{-- =====================================================================
     Halaman Utama Modul Nota Administrasi
     PT Aghitsna Karya Indah

     Komponen:
     - Header: Judul halaman
     - Toolbar: Form pencarian, tombol print, hapus massal, tambah
     - Tabel: Daftar nota dengan checkbox
     - Modal Tambah: Form tambah nota baru
     - Modal Edit: Form edit nota (satu per nota)
     - Modal Hapus: Konfirmasi hapus massal
     - Pagination: Navigasi halaman
     ===================================================================== --}}

@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Nota')

@section('content')
    {{-- ═══════════════════════════════════════════════════════════════
         HEADER: Container utama dengan background surface
         ═══════════════════════════════════════════════════════════════ --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- Header: Judul Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Nota</h1>

        {{-- ═══════════════════════════════════════════════════════════
             TOOLBAR: Pencarian & Tombol Aksi
             ═══════════════════════════════════════════════════════════ --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('nota.administrasi.index') }}"
                class="w-full min-[1280px]:w-auto min-[1280px]:flex-1 flex flex-col min-[1280px]:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari nota..." />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 min-[1280px]:mt-0 w-full min-[1280px]:w-auto">
                <div class="flex flex-col min-[1280px]:flex-row gap-2 w-full min-[1280px]:w-auto">

                    {{-- Dropdown Export PDF --}}
                    <x-buttons.print-dropdown-with-selected :pdfRoute="route('nota.administrasi.export.pdf')" :queryParams="['search' => request('search')]" responsive="custom" fill />

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah Nota --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Nota" />
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             TABEL: Komponen tabel daftar nota
             ═══════════════════════════════════════════════════════════ --}}
        @include('components.administrasi.nota.table', ['notas' => $notas])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$notas" />

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL TAMBAH: Form tambah nota baru
         ═══════════════════════════════════════════════════════════════ --}}
    @include('components.administrasi.nota.add-modal')

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL EDIT: Form edit nota (satu modal per nota)
         ═══════════════════════════════════════════════════════════════ --}}
    @foreach ($notas as $nota)
        @include('components.administrasi.nota.edit-modal', ['nota' => $nota])
    @endforeach

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL HAPUS: Konfirmasi hapus massal
         ═══════════════════════════════════════════════════════════════ --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- Hidden input untuk route print selected (digunakan oleh JS) --}}
    <input type="hidden" id="nota-print-selected-route" value="{{ route('nota.administrasi.export.pdf.selected') }}">

    {{-- ═══════════════════════════════════════════════════════════════
         JAVASCRIPT: Load via Vite (modular)
         ═══════════════════════════════════════════════════════════════ --}}
    @push('scripts')
        @vite('resources/js/pages/administrasi/nota/index.js')
    @endpush
@endsection
